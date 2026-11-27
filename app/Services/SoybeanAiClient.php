<?php

namespace App\Services;

use DomainException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class SoybeanAiClient
{
    /**
     * @param array<int, \Illuminate\Http\UploadedFile> $files
     */
    public function prediksi(
        float $berat,
        array $files
    ): array {
        if ($berat <= 0) {
            throw new DomainException(
                'Berat kedelai harus lebih dari nol.'
            );
        }

        if (empty($files)) {
            throw new DomainException(
                'Minimal satu gambar harus dikirim.'
            );
        }

        $url = rtrim(
            (string) config('services.soybean_ai.url'),
            '/'
        );

        $apiKey = (string) config('services.soybean_ai.key');

        if ($url === '') {
            throw new DomainException(
                'URL server AI belum dikonfigurasi.'
            );
        }

        if ($apiKey === '') {
            throw new DomainException(
                'API key server AI belum dikonfigurasi.'
            );
        }

        $handles = [];

        try {
            $request = Http::baseUrl($url)
                ->timeout(
                    (int) config('services.soybean_ai.timeout', 120)
                )
                ->withHeaders([
                    'X-API-Key' => $apiKey,
                ])
                ->acceptJson()
                ->asMultipart();

            foreach ($files as $file) {
                if (! $file->isValid()) {
                    throw new DomainException(
                        'Ada file gambar yang gagal diunggah.'
                    );
                }

                $handle = fopen($file->getRealPath(), 'r');

                if ($handle === false) {
                    throw new DomainException(
                        'Salah satu gambar tidak dapat dibaca.'
                    );
                }

                $handles[] = $handle;

                $request = $request->attach(
                    'images',
                    $handle,
                    $file->getClientOriginalName()
                );
            }

            $response = $request->post('/predict', [
                'berat_kedelai_kg' => (string) $berat,
            ]);
        } catch (ConnectionException) {
            throw new DomainException(
                'Server AI Flask tidak dapat dihubungi. Pastikan Flask berjalan dan URL API benar.'
            );
        } finally {
            foreach ($handles as $handle) {
                if (is_resource($handle)) {
                    fclose($handle);
                }
            }
        }

        if ($response->failed()) {
            $message = $response->json('message')
                ?? $response->json('error')
                ?? $response->json('detail');

            if (is_array($message)) {
                $message = $message['message'] ?? null;
            }

            if (
                $response->status() === 401 ||
                $response->status() === 403
            ) {
                throw new DomainException(
                    'API key Laravel dan Flask tidak cocok.'
                );
            }

            throw new DomainException(
                is_string($message)
                    ? $message
                    : 'Server AI gagal memproses gambar (HTTP ' .
                        $response->status() . ').'
            );
        }

        $data = $response->json();

        if (
            ! is_array($data) ||
            ($data['success'] ?? true) !== true
        ) {
            throw new DomainException(
                'Respons Flask bukan JSON prediksi yang valid.'
            );
        }

        $fieldWajib = [
            'yolo_classification.good_percent',
            'yolo_classification.bad_percent',
            'random_forest_prediction.hasil_tahu_kg',
            'random_forest_prediction.waktu_memasak_jam',
        ];

        foreach ($fieldWajib as $key) {
            if (! is_numeric(data_get($data, $key))) {
                throw new DomainException(
                    "Respons Flask tidak valid: {$key} harus berupa angka."
                );
            }
        }

        $good = (float) data_get(
            $data,
            'yolo_classification.good_percent'
        );

        $bad = (float) data_get(
            $data,
            'yolo_classification.bad_percent'
        );

        $hasilTahu = (float) data_get(
            $data,
            'random_forest_prediction.hasil_tahu_kg'
        );

        $jam = (float) data_get(
            $data,
            'random_forest_prediction.waktu_memasak_jam'
        );

        if (
            $good < 0 ||
            $good > 100 ||
            $bad < 0 ||
            $bad > 100 ||
            abs(($good + $bad) - 100) > 1.0
        ) {
            throw new DomainException(
                'Persentase Good dan Bad dari YOLO tidak valid atau tidak berjumlah sekitar 100%.'
            );
        }

        if (
            $hasilTahu < 0 ||
            $jam <= 0 ||
            $jam > 24
        ) {
            throw new DomainException(
                'Prediksi hasil tahu atau waktu memasak berada di luar rentang yang valid.'
            );
        }

        // Laravel menyimpan durasi dalam menit.
        $data['random_forest_prediction']['waktu_memasak_menit'] =
            (int) ceil($jam * 60);

        return $data;
    }
}
