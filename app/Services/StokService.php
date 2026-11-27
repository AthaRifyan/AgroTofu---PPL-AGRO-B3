<?php

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
    /**
     * Stok masuk dari pembelian bahan baku.
     * Otomatis dicatat sebagai pengeluaran.
     */
    public function masukBahan(
        BahanBaku $bahan,
        float $jumlah,
        float $hargaSatuan,
        int $userId,
        ?int $supplierId = null,
        ?string $catatan = null,
        ?string $tanggal = null
    ): StokBahanBaku {
        return DB::transaction(function () use (
            $bahan,
            $jumlah,
            $hargaSatuan,
            $userId,
            $supplierId,
            $catatan,
            $tanggal
        ) {
            if ($jumlah <= 0 || $hargaSatuan < 0) {
                throw new InvalidArgumentException(
                    'Jumlah harus lebih dari nol dan harga tidak boleh negatif.'
                );
            }

            $tanggal ??= today()->toDateString();

            $mutasi = $this->mutasi(
                BahanBaku::class,
                $bahan->id,
                StokBahanBaku::class,
                'bahan_baku_id',
                TipeMutasi::Masuk,
                $jumlah,
                $userId,
                null,
                $catatan,
                [
                    'supplier_id' => $supplierId,
                    'harga_satuan' => $hargaSatuan,
                    'tanggal' => $tanggal,
                ]
            );

            $bahan->update([
                'harga_terakhir' => $hargaSatuan,
            ]);

            if ($hargaSatuan > 0) {
                Pengeluaran::create([
                    'user_id' => $userId,
                    'kategori' => 'bahan_baku',
                    'keterangan' =>
                        "Pembelian {$bahan->nama} ({$jumlah} {$bahan->satuan})",
                    'jumlah' => round($jumlah * $hargaSatuan, 2),
                    'tanggal' => $tanggal,
                    'referensi_type' => $mutasi->getMorphClass(),
                    'referensi_id' => $mutasi->id,
                ]);
            }

            return $mutasi;
        });
    }

    /**
     * Stok bahan baku keluar karena digunakan dalam produksi.
     */
    public function keluarBahan(
        BahanBaku $bahan,
        float $jumlah,
        int $userId,
        ?Model $ref = null,
        ?string $catatan = null
    ): StokBahanBaku {
        return $this->mutasi(
            BahanBaku::class,
            $bahan->id,
            StokBahanBaku::class,
            'bahan_baku_id',
            TipeMutasi::Keluar,
            $jumlah,
            $userId,
            $ref,
            $catatan,
            [
                'harga_satuan' => $bahan->harga_terakhir,
                'tanggal' => today()->toDateString(),
            ]
        );
    }

    /**
     * Mengembalikan bahan baku karena batch dibatalkan.
     *
     * Menggunakan referensi produksi agar riwayat pengembalian
     * dapat ditelusuri berdasarkan batch.
     *
     * Tidak membuat catatan pembelian atau pengeluaran.
     */
    public function kembalikanBahan(
        BahanBaku $bahan,
        float $jumlah,
        int $userId,
        Model $produksi,
        ?string $catatan = null
    ): StokBahanBaku {
        return $this->mutasi(
            BahanBaku::class,
            $bahan->id,
            StokBahanBaku::class,
            'bahan_baku_id',
            TipeMutasi::Masuk,
            $jumlah,
            $userId,
            $produksi,
            $catatan ?? 'Pengembalian bahan baku produksi',
            [
                'harga_satuan' => $bahan->pivot->harga_satuan
                    ?? $bahan->harga_terakhir,
                'tanggal' => today()->toDateString(),
            ]
        );
    }

    /**
     * Stok opname bahan baku.
     * Jumlah merupakan stok fisik akhir, bukan selisih.
     */
    public function opnameBahan(
        BahanBaku $bahan,
        float $stokFisik,
        int $userId,
        ?string $catatan = null
    ): StokBahanBaku {
        return $this->mutasi(
            BahanBaku::class,
            $bahan->id,
            StokBahanBaku::class,
            'bahan_baku_id',
            TipeMutasi::Penyesuaian,
            $stokFisik,
            $userId,
            null,
            $catatan,
            [
                'tanggal' => today()->toDateString(),
            ]
        );
    }

    /**
     * Stok produk masuk karena hasil produksi.
     */
    public function masukProduk(
        Produk $produk,
        float $jumlah,
        int $userId,
        ?Model $ref = null,
        ?string $catatan = null
    ): StokProduk {
        return $this->mutasi(
            Produk::class,
            $produk->id,
            StokProduk::class,
            'produk_id',
            TipeMutasi::Masuk,
            $jumlah,
            $userId,
            $ref,
            $catatan
        );
    }

    /**
     * Stok produk keluar karena penjualan atau penggunaan.
     */
    public function keluarProduk(
        Produk $produk,
        float $jumlah,
        int $userId,
        ?Model $ref = null,
        ?string $catatan = null
    ): StokProduk {
        return $this->mutasi(
            Produk::class,
            $produk->id,
            StokProduk::class,
            'produk_id',
            TipeMutasi::Keluar,
            $jumlah,
            $userId,
            $ref,
            $catatan
        );
    }

    /**
     * Stok opname produk.
     */
    public function opnameProduk(
        Produk $produk,
        float $stokFisik,
        int $userId,
        ?string $catatan = null
    ): StokProduk {
        return $this->mutasi(
            Produk::class,
            $produk->id,
            StokProduk::class,
            'produk_id',
            TipeMutasi::Penyesuaian,
            $stokFisik,
            $userId,
            null,
            $catatan
        );
    }

    /**
     * Koreksi harga terakhir bahan baku.
     */
    public function koreksiHarga(
        BahanBaku $bahan,
        float $hargaBaru,
        bool $koreksiPembelianTerakhir = false
    ): ?StokBahanBaku {
        return DB::transaction(function () use (
            $bahan,
            $hargaBaru,
            $koreksiPembelianTerakhir
        ) {
            if ($hargaBaru < 0) {
                throw new InvalidArgumentException(
                    'Harga tidak boleh negatif.'
                );
            }

            $bahan = BahanBaku::query()
                ->lockForUpdate()
                ->findOrFail($bahan->id);

            $bahan->update([
                'harga_terakhir' => $hargaBaru,
            ]);

            if (! $koreksiPembelianTerakhir) {
                return null;
            }

            $pembelian = StokBahanBaku::query()
                ->where('bahan_baku_id', $bahan->id)
                ->where('tipe', TipeMutasi::Masuk->value)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $pembelian) {
                return null;
            }

            $pembelian->update([
                'harga_satuan' => $hargaBaru,
            ]);

            Pengeluaran::query()
                ->where(
                    'referensi_type',
                    $pembelian->getMorphClass()
                )
                ->where('referensi_id', $pembelian->id)
                ->update([
                    'jumlah' => round(
                        (float) $pembelian->jumlah * $hargaBaru,
                        2
                    ),
                ]);

            return $pembelian;
        });
    }

    /**
     * Inti seluruh proses mutasi stok.
     *
     * 1. Kunci data barang.
     * 2. Hitung stok sebelum dan sesudah.
     * 3. Cegah stok negatif.
     * 4. Perbarui stok barang.
     * 5. Simpan riwayat mutasi.
     */
    private function mutasi(
        string $modelClass,
        int $id,
        string $ledgerClass,
        string $fk,
        TipeMutasi $tipe,
        float $jumlah,
        int $userId,
        ?Model $ref,
        ?string $catatan,
        array $extra = []
    ): Model {
        if (! is_finite($jumlah)) {
            throw new InvalidArgumentException(
                'Jumlah stok harus berupa angka yang valid.'
            );
        }

        if (
            $jumlah < 0 ||
            ($jumlah == 0 && $tipe !== TipeMutasi::Penyesuaian)
        ) {
            throw new InvalidArgumentException(
                'Jumlah harus lebih besar dari nol.'
            );
        }

        return DB::transaction(function () use (
            $modelClass,
            $id,
            $ledgerClass,
            $fk,
            $tipe,
            $jumlah,
            $userId,
            $ref,
            $catatan,
            $extra
        ) {
            $item = $modelClass::query()
                ->lockForUpdate()
                ->findOrFail($id);

            $kolom = $item instanceof BahanBaku
                ? 'stok_saat_ini'
                : 'stok';

            $sebelum = (float) $item->{$kolom};

            $sesudah = round(
                match ($tipe) {
                    TipeMutasi::Masuk =>
                        $sebelum + $jumlah,

                    TipeMutasi::Keluar =>
                        $sebelum - $jumlah,

                    TipeMutasi::Penyesuaian =>
                        $jumlah,
                },
                2
            );

            if ($sesudah < 0) {
                throw new StokTidakCukupException(
                    sprintf(
                        'Stok %s tidak cukup (tersisa %s, diminta %s).',
                        $item->nama,
                        $sebelum,
                        $jumlah
                    )
                );
            }

            $item->update([
                $kolom => $sesudah,
            ]);

            return $ledgerClass::create([
                $fk => $id,
                'user_id' => $userId,
                'tipe' => $tipe,
                'jumlah' => $jumlah,
                'stok_sebelum' => $sebelum,
                'stok_sesudah' => $sesudah,
                'referensi_type' => $ref?->getMorphClass(),
                'referensi_id' => $ref?->getKey(),
                'catatan' => $catatan,
                ...$extra,
            ]);
        }, 3);
    }
}
