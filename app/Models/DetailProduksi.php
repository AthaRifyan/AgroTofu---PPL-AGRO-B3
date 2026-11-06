<?php

namespace App\Models;

use App\Enums\StatusTahap;
use App\Enums\TahapProduksi;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailProduksi extends Model
{
    protected $table = 'detail_produksi';
    protected $guarded = [];
    protected $casts = [
        'tahap'                => TahapProduksi::class,
        'status'               => StatusTahap::class,
        'waktu_mulai'          => 'datetime',
        'batas_waktu'          => 'datetime',
        'waktu_selesai'        => 'datetime',
        'durasi_rencana_menit' => 'integer',
        'durasi_aktual_detik'  => 'integer',
    ];

    public function produksi(): BelongsTo { return $this->belongsTo(Produksi::class); }
    public function petugas(): BelongsTo { return $this->belongsTo(User::class, 'petugas_id'); }

    /** Sisa waktu (detik) untuk tahap yang sedang berjalan; null jika tidak berjalan. */
    protected function sisaDetik(): Attribute
    {
        return Attribute::get(function () {
            if ($this->status !== StatusTahap::Berjalan || ! $this->batas_waktu) {
                return null;
            }

            return max(0, $this->batas_waktu->getTimestamp() - now()->getTimestamp());
        });
    }

    /** Contoh: "09 menit 43 detik". */
    protected function durasiAktualLabel(): Attribute
    {
        return Attribute::get(fn () => $this->durasi_aktual_detik === null
            ? null
            : sprintf('%02d menit %02d detik', intdiv($this->durasi_aktual_detik, 60), $this->durasi_aktual_detik % 60));
    }

    /** Teks keterangan yang ditampilkan di atas nama petugas setelah tahap selesai. */
    protected function keteranganSelesai(): Attribute
    {
        return Attribute::get(function () {
            if ($this->status !== StatusTahap::Selesai || $this->durasi_aktual_detik === null) {
                return null;
            }

            return $this->jenis_selesai === 'otomatis'
                ? "Selesai otomatis selama {$this->durasi_rencana_menit} menit"
                : 'Diselesaikan pada durasi ' . $this->durasi_aktual_label;
        });
    }
}