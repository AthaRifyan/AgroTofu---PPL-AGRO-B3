<?php

namespace App\Services;

use App\Enums\StatusProduksi;
use App\Enums\StatusTahap;
use App\Enums\TahapProduksi;
use App\Models\PrediksiAi;
use App\Models\Produksi;
use DomainException;
use Illuminate\Support\Facades\DB;

class PrediksiAiService
{
    public function __construct(private readonly SoybeanAiClient $client)
    {
    }

    /** @param array<int, \Illuminate\Http\UploadedFile> $files */
    public function jalankan(Produksi $produksi, float $berat, array $files, int $userId): PrediksiAi
    {
        if ($produksi->status !== StatusProduksi::Berjalan) {
            throw new DomainException('Prediksi AI hanya bisa dijalankan pada batch yang sedang berjalan.');
        }

        // Jangan menahan transaksi database selama model YOLO/RF sedang bekerja.
        $hasil = $this->client->prediksi($berat, $files);

        return DB::transaction(function () use ($produksi, $berat, $files, $hasil, $userId) {
            return $this->terapkan($produksi, $berat, count($files), $hasil, $userId);
        });
    }

    private function terapkan(
        Produksi $produksi,
        float $berat,
        int $jumlahGambar,
        array $hasil,
        int $userId
    ): PrediksiAi {
        $produksi = Produksi::query()
            ->lockForUpdate()
            ->findOrFail($produksi->id);

        if ($produksi->status !== StatusProduksi::Berjalan) {
            throw new DomainException('Batch sudah tidak berjalan. Hasil AI tidak dapat diterapkan.');
        }

        $yolo = $hasil['yolo_classification'];
        $rf = $hasil['random_forest_prediction'];
        $menitBaru = (int) $rf['waktu_memasak_menit'];

        // Di database, nama tahap pemasakan yang digunakan saat ini adalah "perebusan".
        $tahap = $produksi->tahapan()
            ->where('tahap', TahapProduksi::Perebusan->value)
            ->lockForUpdate()
            ->first();

        if (! $tahap) {
            throw new DomainException('Task pemasakan (perebusan) tidak ditemukan pada batch ini.');
        }

        $menitLama = $tahap->durasi_rencana_menit;

        // Durasi awal harus sudah ditentukan manajer saat batch dibuat.
        if ($menitLama === null || $menitLama < 1) {
            throw new DomainException('Durasi awal pemasakan belum ditentukan manajer. Atur durasi saat membuat batch.');
        }

        // AI hanya boleh mengubah task yang masih menunggu. Timer berjalan tidak disentuh.
        $diterapkan = $tahap->status === StatusTahap::Menunggu;

        if ($diterapkan) {
            $tahap->durasi_awal_menit ??= $menitLama;

            if ($menitLama !== $menitBaru) {
                $tahap->durasi_rencana_menit = $menitBaru;
                $tahap->disesuaikan_ai = true;
                $tahap->save();
            }
        }

        return PrediksiAi::create([
            'produksi_id' => $produksi->id,
            'user_id' => $userId,
            'berat_kedelai_kg' => $berat,
            'jumlah_gambar' => $jumlahGambar,
            'good_percent' => $yolo['good_percent'],
            'bad_percent' => $yolo['bad_percent'],
            'uncertain_count' => $yolo['uncertain_count'] ?? 0,
            'coverage_percent' => $yolo['coverage_percent'] ?? 0,
            'hasil_tahu_kg' => $rf['hasil_tahu_kg'],
            'waktu_memasak_menit' => $menitBaru,
            'durasi_sebelumnya_menit' => $menitLama,
            'diterapkan' => $diterapkan,
            'response_json' => $hasil,
        ]);
    }
}
