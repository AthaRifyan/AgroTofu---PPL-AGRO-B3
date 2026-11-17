<?php
// app/Models/Produksi.php

namespace App\Models;

use App\Enums\StatusProduksi;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
// use Illuminate\Database\Eloquent\Relations\HasMany;

class Produksi extends Model
{
    protected $table = 'produksi';
    protected $guarded = [];
    protected $casts = [
        'tanggal_jadwal'   => 'date',
        'waktu_mulai'      => 'datetime',
        'waktu_selesai'    => 'datetime',
        'status'           => StatusProduksi::class,
        'berat_kedelai_kg' => 'decimal:2',
        'biaya_bahan'      => 'decimal:2',
    ];

    public function penanggungJawab(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penanggung_jawab_id');
    }

    public function tahapan(): HasMany
    {
        return $this->hasMany(DetailProduksi::class)->orderBy('urutan');
    }

    public function hasil(): HasMany
    {
        return $this->hasMany(HasilProduksi::class);
    }

    /** M:N dengan data pivot jumlah & harga snapshot. */
    public function bahanBaku(): BelongsToMany
    {
        return $this->belongsToMany(BahanBaku::class, 'produksi_bahan_baku')
            ->withPivot(['jumlah_dipakai', 'harga_satuan'])
            ->withTimestamps();
    }

    public function mutasiProduk(): MorphMany
    {
        return $this->morphMany(StokProduk::class, 'referensi');
    }

    public function mutasiBahan(): MorphMany
    {
        return $this->morphMany(StokBahanBaku::class, 'referensi');
    }

    /** Rendemen = kg tahu baik per kg kedelai (pembanding prediksi AI di tahap lanjutan). */
    protected function rendemen(): Attribute
    {
        return Attribute::get(function () {
            $berat = (float) $this->berat_kedelai_kg;

            return $berat > 0 ? round((float) $this->hasil->sum('jumlah_baik') / $berat, 2) : null;
        });
    }
    public function prediksiAi(): HasMany
{
    return $this->hasMany(PrediksiAi::class)->latest('id');
}
}

// ===== app/Models/Produksi.php : tambahkan relasi =====




// ===== app/Models/DetailProduksi.php =====
// 1) tambahkan ke $fillable:  'durasi_rencana_menit', 'disesuaikan_ai'
// 2) tambahkan ke $casts:     'disesuaikan_ai' => 'boolean'
// 3) pastikan jam_mulai & jam_selesai di-cast 'datetime'