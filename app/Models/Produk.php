<?php
// app/Models/Produk.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Produk extends Model
{
    use HasFactory;

    public const SATUAN = ['kg', 'pcs', 'papan'];

    protected $table = 'produk';
    protected $guarded = [];
    protected $casts = [
        'harga_jual' => 'decimal:2',
        'stok'       => 'decimal:2',
        'is_active'  => 'boolean',
    ];

    public const KATALOG = [
        'Tahu Putih'  => ['kode' => 'PRD-TP', 'satuan' => 'kg'],
        'Tahu Kuning' => ['kode' => 'PRD-TK', 'satuan' => 'kg'],
    ];

    public static function namaTersedia(): array
    {
        return array_keys(self::KATALOG);
    }

    public function mutasiStok(): HasMany { return $this->hasMany(StokProduk::class); }
    public function hasilProduksi(): HasMany { return $this->hasMany(HasilProduksi::class); }
    public function detailPenjualan(): HasMany { return $this->hasMany(DetailPenjualan::class); }
}