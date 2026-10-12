<?php
// app/Models/StokBahanBaku.php

namespace App\Models;

use App\Enums\TipeMutasi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StokBahanBaku extends Model
{
    protected $table = 'stok_bahan_baku';
    protected $guarded = [];
    protected $casts = [
        'tipe'         => TipeMutasi::class,
        'tanggal'      => 'date',
        'jumlah'       => 'decimal:2',
        'harga_satuan' => 'decimal:2',
        'stok_sebelum' => 'decimal:2',
        'stok_sesudah' => 'decimal:2',
    ];

    public function bahanBaku(): BelongsTo { return $this->belongsTo(BahanBaku::class); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function referensi(): MorphTo { return $this->morphTo(); }
}