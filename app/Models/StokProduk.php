<?php
// app/Models/StokProduk.php

namespace App\Models;

use App\Enums\TipeMutasi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StokProduk extends Model
{
    protected $table = 'stok_produk';
    protected $guarded = [];
    protected $casts = [
        'tipe'         => TipeMutasi::class,
        'jumlah'       => 'decimal:2',
        'stok_sebelum' => 'decimal:2',
        'stok_sesudah' => 'decimal:2',
    ];

    public function produk(): BelongsTo { return $this->belongsTo(Produk::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function referensi(): MorphTo { return $this->morphTo(); }
}