<?php
// app/Services/PenjualanService.php

namespace App\Services;

use App\Models\Penjualan;
use App\Models\Produk;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PenjualanService
{
    public function __construct(private readonly StokService $stok)
    {
    }

    /** @param array<int, array{produk_id:int|string, jumlah:float|string}> $items */
    public function checkout(int $kasirId, array $items, string $metode, float $bayar, ?string $pembeli = null): Penjualan
    {
        // gabungkan produk yang sama & urutkan id → penguncian stok konsisten
        $baris = collect($items)
            ->groupBy('produk_id')
            ->map(fn ($g) => (float) $g->sum('jumlah'))
            ->sortKeys();

        return DB::transaction(function () use ($kasirId, $baris, $metode, $bayar, $pembeli) {
            $penjualan = Penjualan::create([
                'no_nota'           => 'NOTA-' . now()->format('ymdHis') . '-' . Str::upper(Str::random(3)),
                'kasir_id'          => $kasirId,
                'tanggal'           => now(),
                'nama_pembeli'      => $pembeli,
                'metode_pembayaran' => $metode,
                'status'            => 'selesai',
            ]);

            $total = 0.0;

            foreach ($baris as $produkId => $jumlah) {
                $produk = Produk::findOrFail($produkId);

                if (! $produk->is_active) {
                    throw new DomainException("Produk {$produk->nama} tidak aktif.");
                }

                $subtotal = round((float) $produk->harga_jual * $jumlah, 2);

                $penjualan->detail()->create([
                    'produk_id'    => $produk->id,
                    'jumlah'       => $jumlah,
                    'harga_satuan' => $produk->harga_jual,
                    'subtotal'     => $subtotal,
                ]);

                // melempar StokTidakCukupException bila stok kurang → seluruh transaksi di-rollback
                $this->stok->keluarProduk($produk, $jumlah, $kasirId, $penjualan, "Penjualan {$penjualan->no_nota}");

                $total += $subtotal;
            }

            if ($bayar < $total) {
                throw new DomainException('Nominal pembayaran kurang dari total belanja.');
            }

            $penjualan->update(['total' => $total, 'bayar' => $bayar, 'kembalian' => round($bayar - $total, 2)]);

            return $penjualan;
        });
    }

    /** Nota tidak dihapus/diedit: dibatalkan dan stok dikembalikan (jejak audit tetap utuh). */
    public function batal(Penjualan $penjualan, int $userId): Penjualan
    {
        return DB::transaction(function () use ($penjualan, $userId) {
            $penjualan = Penjualan::lockForUpdate()->findOrFail($penjualan->id);

            if ($penjualan->status === 'batal') {
                throw new DomainException('Nota ini sudah dibatalkan.');
            }

            foreach ($penjualan->detail()->orderBy('produk_id')->get() as $d) {
                $this->stok->masukProduk($d->produk, (float) $d->jumlah, $userId, $penjualan, "Pembatalan {$penjualan->no_nota}");
            }

            $penjualan->update(['status' => 'batal']);

            return $penjualan;
        });
    }
}