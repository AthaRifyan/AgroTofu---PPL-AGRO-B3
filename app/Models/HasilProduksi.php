<?php
// app/Models/HasilProduksi.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HasilProduksi extends Model
{
    protected $table = 'hasil_produksi';
    protected $guarded = [];
    protected $casts = ['jumlah_baik' => 'decimal:2', 'jumlah_reject' => 'decimal:2'];

    public function produksi(): BelongsTo { return $this->belongsTo(Produksi::class); }
    public function produk(): BelongsTo { return $this->belongsTo(Produk::class); }
    public function pencatat(): BelongsTo { return $this->belongsTo(User::class, 'dicatat_oleh'); }
}