<?php
// app/Models/BahanBaku.php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BahanBaku extends Model
{
    use HasFactory;

    public const KATEGORI = ['utama', 'penggumpal', 'pendukung'];
    public const SATUAN = ['kg', 'gram', 'liter', 'ml', 'pcs'];

    protected $table = 'bahan_baku';
    protected $guarded = [];
    protected $casts = [
        'stok_saat_ini'  => 'decimal:2',
        'stok_minimum'   => 'decimal:2',
        'harga_terakhir' => 'decimal:2',
        'is_active'      => 'boolean',
    ];

    public function mutasiStok(): HasMany
    {
        return $this->hasMany(StokBahanBaku::class);
    }

    /** M:N: satu bahan dipakai banyak batch, satu batch memakai banyak bahan. */
    public function produksi(): BelongsToMany
    {
        return $this->belongsToMany(Produksi::class, 'produksi_bahan_baku')
            ->withPivot(['jumlah_dipakai', 'harga_satuan'])
            ->withTimestamps();
    }

    public function scopeStokMenipis(Builder $query): Builder
    {
        return $query->where('stok_minimum', '>', 0)->whereColumn('stok_saat_ini', '<=', 'stok_minimum');
    }
}