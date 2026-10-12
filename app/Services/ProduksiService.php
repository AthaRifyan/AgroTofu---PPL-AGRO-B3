<?php
// app/Services/ProduksiService.php

namespace App\Services;

use App\Enums\StatusProduksi;
use App\Enums\StatusTahap;
use App\Enums\TahapProduksi;
use App\Models\BahanBaku;
use App\Models\DetailProduksi;
use App\Models\Produk;
use App\Models\Produksi;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProduksiService
{
    public function __construct(private readonly StokService $stok)
    {
    }

    /**
     * Buat jadwal + batch. Stok TIDAK berkurang sampai batch dimulai (kecuali $langsungMulai).
     *
     * @param array $data   tanggal_jadwal, jam_rencana?, catatan?
     * @param array $bahan  [['bahan_baku_id' => 1, 'jumlah' => 25], ...]
     */
    public function jadwalkan(array $data, array $bahan, int $userId, bool $langsungMulai = false): Produksi
    {
        return DB::transaction(function () use ($data, $bahan, $userId, $langsungMulai) {
            $models = BahanBaku::whereIn('id', collect($bahan)->pluck('bahan_baku_id'))->get()->keyBy('id');

            // berat kedelai batch = total bahan berkategori "utama" (satuan kg)
            $berat = collect($bahan)
                ->filter(fn ($b) => $models[$b['bahan_baku_id']]->kategori === 'utama')
                ->sum(fn ($b) => (float) $b['jumlah']);

            if ($berat <= 0) {
                throw new DomainException('Pilih minimal satu bahan berkategori utama (kedelai).');
            }

            $produksi = Produksi::create([
                'kode_batch'          => 'BTH-' . now()->format('ymd') . '-' . Str::upper(Str::random(4)),
                'tanggal_jadwal'      => $data['tanggal_jadwal'],
                'jam_rencana'         => $data['jam_rencana'] ?? null,
                'berat_kedelai_kg'    => $berat,
                'status'              => StatusProduksi::Terjadwal,
                'penanggung_jawab_id' => $userId,
                'catatan'             => $data['catatan'] ?? null,
            ]);

            foreach (TahapProduksi::cases() as $tahap) {
                $produksi->tahapan()->create([
                    'tahap'  => $tahap,
                    'urutan' => $tahap->urutan(),
                    'status' => StatusTahap::Menunggu,
                ]);
            }

            foreach ($bahan as $b) {
                $produksi->bahanBaku()->attach($b['bahan_baku_id'], ['jumlah_dipakai' => $b['jumlah']]);
            }

            return $langsungMulai ? $this->mulai($produksi, $userId) : $produksi;
        });
    }

    /** Mulai batch: stok bahan baku berkurang otomatis. Jika ada yang kurang, SEMUA dibatalkan (rollback). */
    public function mulai(Produksi $produksi, int $userId): Produksi
    {
        return DB::transaction(function () use ($produksi, $userId) {
            $produksi = Produksi::lockForUpdate()->findOrFail($produksi->id);

            if ($produksi->status !== StatusProduksi::Terjadwal) {
                throw new DomainException('Hanya batch berstatus terjadwal yang bisa dimulai.');
            }

            $biaya = 0.0;

            // urut berdasarkan id agar urutan penguncian konsisten (mencegah deadlock)
            foreach ($produksi->bahanBaku()->orderBy('bahan_baku.id')->get() as $bahan) {
                $jumlah = (float) $bahan->pivot->jumlah_dipakai;
                $harga  = (float) $bahan->harga_terakhir;

                $this->stok->keluarBahan($bahan, $jumlah, $userId, $produksi, "Dipakai batch {$produksi->kode_batch}");
                $produksi->bahanBaku()->updateExistingPivot($bahan->id, ['harga_satuan' => $harga]);

                $biaya += $jumlah * $harga;
            }

            $produksi->update([
                'status'      => StatusProduksi::Berjalan,
                'waktu_mulai' => now(),
                'biaya_bahan' => round($biaya, 2),
            ]);

            return $produksi;
        });
    }

    public function updateTahap(Produksi $produksi, TahapProduksi $tahap, StatusTahap $status, int $userId): DetailProduksi
    {
        if ($produksi->status !== StatusProduksi::Berjalan) {
            throw new DomainException('Tahapan hanya bisa diperbarui saat batch sedang berjalan.');
        }

        $detail = $produksi->tahapan()->where('tahap', $tahap->value)->firstOrFail();
        $data   = ['status' => $status, 'petugas_id' => $userId];
        $mulai  = $detail->waktu_mulai ?? now();

        if ($status === StatusTahap::Berjalan) {
            $data['waktu_mulai'] = $mulai;
        }

        if ($status === StatusTahap::Selesai) {
            $selesai = now();
            $data['waktu_mulai']   = $mulai;
            $data['waktu_selesai'] = $selesai;
            $data['durasi_menit']  = (int) round($mulai->diffInMinutes($selesai));
        }

        $detail->update($data);

        return $detail;
    }

    /**
     * Catat hasil produksi: simpan hasil per produk, stok tahu (jumlah baik) bertambah, batch selesai.
     *
     * @param array $hasil [['produk_id' => 1, 'jumlah_baik' => 40, 'jumlah_reject' => 2], ...]
     */
    public function catatHasil(Produksi $produksi, array $hasil, int $userId, ?string $catatan = null): Produksi
    {
        return DB::transaction(function () use ($produksi, $hasil, $userId, $catatan) {
            $produksi = Produksi::lockForUpdate()->findOrFail($produksi->id);

            if ($produksi->status !== StatusProduksi::Berjalan) {
                throw new DomainException('Hasil hanya bisa dicatat untuk batch yang sedang berjalan.');
            }

            usort($hasil, fn ($a, $b) => $a['produk_id'] <=> $b['produk_id']);

            foreach ($hasil as $row) {
                $baik   = (float) $row['jumlah_baik'];
                $reject = (float) ($row['jumlah_reject'] ?? 0);
                $produk = Produk::findOrFail($row['produk_id']);

                $produksi->hasil()->create([
                    'produk_id'     => $produk->id,
                    'dicatat_oleh'  => $userId,
                    'jumlah_baik'   => $baik,
                    'jumlah_reject' => $reject,
                    'catatan'       => $catatan,
                ]);

                if ($baik > 0) {
                    $this->stok->masukProduk($produk, $baik, $userId, $produksi, "Hasil batch {$produksi->kode_batch}");
                }
            }

            // tahapan yang belum ditandai selesai dianggap selesai bersama batch
            $produksi->tahapan()->where('status', '!=', StatusTahap::Selesai->value)
                ->update(['status' => StatusTahap::Selesai->value, 'waktu_selesai' => now()]);

            $produksi->update(['status' => StatusProduksi::Selesai, 'waktu_selesai' => now()]);

            return $produksi;
        });
    }

    /** Hanya jadwal yang belum dimulai yang bisa dibatalkan (bahan baku sudah terpakai jika sudah berjalan). */
    public function batal(Produksi $produksi): Produksi
    {
        return DB::transaction(function () use ($produksi) {
            $produksi = Produksi::lockForUpdate()->findOrFail($produksi->id);

            if ($produksi->status !== StatusProduksi::Terjadwal) {
                throw new DomainException('Hanya jadwal yang belum dimulai yang bisa dibatalkan.');
            }

            $produksi->update(['status' => StatusProduksi::Batal]);

            return $produksi;
        });
    }
}