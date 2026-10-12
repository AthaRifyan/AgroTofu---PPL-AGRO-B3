<?php
// app/Models/Penjualan.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Penjualan extends Model
{
    public const METODE = ['tunai', 'transfer', 'qris', 'lainnya'];

    protected $table = 'penjualan';
    protected $guarded = [];
    protected $casts = [
        'tanggal'   => 'datetime',
        'total'     => 'decimal:2',
        'bayar'     => 'decimal:2',
        'kembalian' => 'decimal:2',
    ];

    public function kasir(): BelongsTo { return $this->belongsTo(User::class, 'kasir_id'); }
    public function detail(): HasMany { return $this->hasMany(DetailPenjualan::class); }
    public function mutasiStok(): MorphMany { return $this->morphMany(StokProduk::class, 'referensi'); }
}