<?php
// app/Models/DetailPenjualan.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPenjualan extends Model
{
    protected $table = 'detail_penjualan';
    protected $guarded = [];
    protected $casts = ['jumlah' => 'decimal:2', 'harga_satuan' => 'decimal:2', 'subtotal' => 'decimal:2'];

    public function penjualan(): BelongsTo { return $this->belongsTo(Penjualan::class); }
    public function produk(): BelongsTo { return $this->belongsTo(Produk::class); }
}