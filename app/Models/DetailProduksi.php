<?php
// app/Models/DetailProduksi.php

namespace App\Models;

use App\Enums\StatusTahap;
use App\Enums\TahapProduksi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailProduksi extends Model
{
    protected $table = 'detail_produksi';
    protected $guarded = [];
    protected $casts = [
        'tahap'         => TahapProduksi::class,
        'status'        => StatusTahap::class,
        'waktu_mulai'   => 'datetime',
        'waktu_selesai' => 'datetime',
    ];

    public function produksi(): BelongsTo { return $this->belongsTo(Produksi::class); }
    public function petugas(): BelongsTo { return $this->belongsTo(User::class, 'petugas_id'); }
}