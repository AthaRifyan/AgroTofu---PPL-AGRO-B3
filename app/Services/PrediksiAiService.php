<?php

namespace App\Services;

use App\Enums\StatusProduksi;
use App\Models\PrediksiAi;
use App\Models\Produksi;
use DomainException;
use Illuminate\Support\Facades\DB;

class PrediksiAiService
{
    public function __construct(
        private readonly SoybeanAiClient $client
    ) {
    }

    /**
     * Prediksi satu batch menggunakan seluruh berat kedelai batch.
     *
     * @param array<int, \Illuminate\Http\UploadedFile> $files
     */
    public function jalankan(
        Produksi $produksi,
        float $berat,
        array $files,
        int $userId
    ): PrediksiAi {
        if (! $produksi->mode_laporan) {
            throw new DomainException(
                'Prediksi AI hanya tersedia untuk laporan batch.'
            );
        }

        if ($produksi->status !== StatusProduksi::Berjalan) {
            throw new DomainException(
                'Prediksi AI hanya dapat dilakukan pada batch berjalan.'
            );
        }

        $beratTercatat = (float) $produksi->berat_kedelai_kg;

        if ($berat <= 0 || abs($berat - $beratTercatat) > 0.01) {
            throw new DomainException(
                'Berat prediksi harus sama dengan total berat kedelai pada batch.'
            );
        }

        if (empty($files)) {
            throw new DomainException(
                'Upload minimal satu gambar kedelai.'
            );
        }

        // Panggil Flask di luar transaksi database.
        $hasil = $this->client->prediksi($beratTercatat, $files);

        return DB::transaction(function () use (
            $produksi,
            $beratTercatat,
            $files,
            $hasil,
            $userId
        ) {
            $batch = Produksi::query()
                ->lockForUpdate()
                ->findOrFail($produksi->id);

            if (
                ! $batch->mode_laporan ||
                $batch->status !== StatusProduksi::Berjalan
            ) {
                throw new DomainException(
                    'Batch sudah tidak berjalan. Hasil AI tidak dapat disimpan.'
                );
            }

            return PrediksiAi::create([
                'produksi_id' => $batch->id,
                'user_id' => $userId,

                'berat_kedelai_kg' => $beratTercatat,
                'jumlah_gambar' => count($files),

                'good_percent' =>
                    $hasil['yolo_classification']['good_percent'],

                'bad_percent' =>
                    $hasil['yolo_classification']['bad_percent'],

                'uncertain_count' =>
                    $hasil['yolo_classification']['uncertain_count'] ?? 0,

                'coverage_percent' =>
                    $hasil['yolo_classification']['coverage_percent'] ?? 0,

                'hasil_tahu_kg' =>
                    $hasil['random_forest_prediction']['hasil_tahu_kg'],

                'waktu_memasak_menit' =>
                    $hasil['random_forest_prediction']['waktu_memasak_menit'],

                'durasi_sebelumnya_menit' => null,
                'diterapkan' => false,

                'response_json' => $hasil,
            ]);
        }, 3);
    }
}
