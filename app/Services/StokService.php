<?php
// app/Services/StokService.php

namespace App\Services;

use App\Enums\TipeMutasi;
use App\Exceptions\StokTidakCukupException;
use App\Models\BahanBaku;
use App\Models\Pengeluaran;
use App\Models\Produk;
use App\Models\StokBahanBaku;
use App\Models\StokProduk;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StokService
{
    /** Stok masuk dari pembelian: otomatis tercatat sebagai pengeluaran. */
    public function masukBahan(
        BahanBaku $bahan, float $jumlah, float $hargaSatuan, int $userId,
        ?int $supplierId = null, ?string $catatan = null, ?string $tanggal = null
    ): StokBahanBaku {
        return DB::transaction(function () use ($bahan, $jumlah, $hargaSatuan, $userId, $supplierId, $catatan, $tanggal) {
            $tanggal ??= today()->toDateString();

            $mutasi = $this->mutasi(
                BahanBaku::class, $bahan->id, StokBahanBaku::class, 'bahan_baku_id',
                TipeMutasi::Masuk, $jumlah, $userId, null, $catatan,
                ['supplier_id' => $supplierId, 'harga_satuan' => $hargaSatuan, 'tanggal' => $tanggal]
            );

            $bahan->update(['harga_terakhir' => $hargaSatuan]);

            if ($hargaSatuan > 0) {
                Pengeluaran::create([
                    'user_id'        => $userId,
                    'kategori'       => 'bahan_baku',
                    'keterangan'     => "Pembelian {$bahan->nama} ({$jumlah} {$bahan->satuan})",
                    'jumlah'         => round($jumlah * $hargaSatuan, 2),
                    'tanggal'        => $tanggal,
                    'referensi_type' => $mutasi->getMorphClass(),
                    'referensi_id'   => $mutasi->id,
                ]);
            }

            return $mutasi;
        });
    }

    public function keluarBahan(BahanBaku $bahan, float $jumlah, int $userId, ?Model $ref = null, ?string $catatan = null): StokBahanBaku
    {
        return $this->mutasi(
            BahanBaku::class, $bahan->id, StokBahanBaku::class, 'bahan_baku_id',
            TipeMutasi::Keluar, $jumlah, $userId, $ref, $catatan,
            ['harga_satuan' => $bahan->harga_terakhir, 'tanggal' => today()->toDateString()]
        );
    }

    /** Stok opname: stok sistem disamakan dengan stok fisik hasil hitung. */
    public function opnameBahan(BahanBaku $bahan, float $stokFisik, int $userId, ?string $catatan = null): StokBahanBaku
    {
        return $this->mutasi(
            BahanBaku::class, $bahan->id, StokBahanBaku::class, 'bahan_baku_id',
            TipeMutasi::Penyesuaian, $stokFisik, $userId, null, $catatan,
            ['tanggal' => today()->toDateString()]
        );
    }

    public function masukProduk(Produk $produk, float $jumlah, int $userId, ?Model $ref = null, ?string $catatan = null): StokProduk
    {
        return $this->mutasi(Produk::class, $produk->id, StokProduk::class, 'produk_id',
            TipeMutasi::Masuk, $jumlah, $userId, $ref, $catatan);
    }

    public function keluarProduk(Produk $produk, float $jumlah, int $userId, ?Model $ref = null, ?string $catatan = null): StokProduk
    {
        return $this->mutasi(Produk::class, $produk->id, StokProduk::class, 'produk_id',
            TipeMutasi::Keluar, $jumlah, $userId, $ref, $catatan);
    }

    public function opnameProduk(Produk $produk, float $stokFisik, int $userId, ?string $catatan = null): StokProduk
    {
        return $this->mutasi(Produk::class, $produk->id, StokProduk::class, 'produk_id',
            TipeMutasi::Penyesuaian, $stokFisik, $userId, null, $catatan);
    }

    /**
     * Inti semua mutasi: kunci baris → hitung → tolak jika negatif → update stok → catat riwayat.
     * lockForUpdate membuat transaksi lain menunggu, sehingga aman saat produksi dan penjualan berjalan bersamaan.
     */
    private function mutasi(
        string $modelClass, int $id, string $ledgerClass, string $fk,
        TipeMutasi $tipe, float $jumlah, int $userId, ?Model $ref, ?string $catatan, array $extra = []
    ): Model {
        if ($jumlah < 0 || ($jumlah == 0 && $tipe !== TipeMutasi::Penyesuaian)) {
            throw new InvalidArgumentException('Jumlah harus lebih besar dari 0.');
        }

        return DB::transaction(function () use ($modelClass, $id, $ledgerClass, $fk, $tipe, $jumlah, $userId, $ref, $catatan, $extra) {
            $item    = $modelClass::lockForUpdate()->findOrFail($id);
            $kolom   = $item instanceof BahanBaku ? 'stok_saat_ini' : 'stok';
            $sebelum = (float) $item->{$kolom};

            $sesudah = round(match ($tipe) {
                TipeMutasi::Masuk       => $sebelum + $jumlah,
                TipeMutasi::Keluar      => $sebelum - $jumlah,
                TipeMutasi::Penyesuaian => $jumlah,
            }, 2);

            if ($sesudah < 0) {
                throw new StokTidakCukupException(
                    sprintf('Stok %s tidak cukup (tersisa %s, diminta %s).', $item->nama, $sebelum, $jumlah)
                );
            }

            $item->update([$kolom => $sesudah]);

            return $ledgerClass::create([
                $fk              => $id,
                'user_id'        => $userId,
                'tipe'           => $tipe,
                'jumlah'         => $jumlah,
                'stok_sebelum'   => $sebelum,
                'stok_sesudah'   => $sesudah,
                'referensi_type' => $ref?->getMorphClass(),
                'referensi_id'   => $ref?->getKey(),
                'catatan'        => $catatan,
                ...$extra,
            ]);
        });
    }
}