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
    public function jalankan(
        Produksi $produksi,
        float $berat,
        array $files,
        int $userId
    ): PrediksiAi {
        if ($produksi->status !== StatusProduksi::Berjalan) {
            throw new DomainException(
                'Prediksi AI hanya bisa dijalankan pada batch yang sedang berjalan.'
            );
        }

        // AI tetap menggunakan API Flask yang sudah deployed.
        $hasil = $this->client->prediksi($berat, $files);

        return DB::transaction(function () use (
            $produksi,
            $berat,
            $files,
            $hasil,
            $userId
        ) {
            return $this->terapkan(
                $produksi,
                $berat,
                count($files),
                $hasil,
                $userId
            );
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
            throw new DomainException(
                'Batch sudah tidak berjalan. Hasil AI tidak dapat diterapkan.'
            );
        }

        $yolo = $hasil['yolo_classification'];
        $rf = $hasil['random_forest_prediction'];

        $menitBaru = (int) $rf['waktu_memasak_menit'];

        // Task pemasakan menggunakan nama tahap "perebusan".
        $tahap = $produksi->tahapan()
            ->where('tahap', TahapProduksi::Perebusan->value)
            ->lockForUpdate()
            ->first();

        if (! $tahap) {
            throw new DomainException(
                'Task pemasakan (perebusan) tidak ditemukan pada batch ini.'
            );
        }

        $menitLama = $tahap->durasi_rencana_menit;

        if ($menitLama === null || $menitLama < 1) {
            throw new DomainException(
                'Durasi awal pemasakan belum ditentukan manajer. Atur durasi saat membuat batch.'
            );
        }

        // AI hanya boleh memperbarui task yang belum dimulai.
        $diterapkan = $tahap->status === StatusTahap::Menunggu;

        // Simpan satu baris untuk setiap scan.
        $prediksi = PrediksiAi::create([
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

        if ($diterapkan) {
            // Simpan durasi awal dari manajer hanya sekali.
            $tahap->durasi_awal_menit ??= $menitLama;

            // Hitung seluruh durasi prediksi dalam batch ini.
            $totalDurasiMenit = (int) PrediksiAi::query()
                ->where('produksi_id', $produksi->id)
                ->sum('waktu_memasak_menit');

            // Task pemasakan diisi dengan total durasi seluruh scan.
            $tahap->durasi_rencana_menit = $totalDurasiMenit;
            $tahap->disesuaikan_ai = true;
            $tahap->save();
        }

        return $prediksi;
    }
}
