<?php
// app/Models/Pengeluaran.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Pengeluaran extends Model
{
    protected $table = 'pengeluaran';
    protected $guarded = [];
    protected $casts = ['tanggal' => 'date', 'jumlah' => 'decimal:2'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function referensi(): MorphTo { return $this->morphTo(); }
}