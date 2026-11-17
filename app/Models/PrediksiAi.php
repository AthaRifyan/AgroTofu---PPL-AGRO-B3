<?php
// app/Models/PrediksiAi.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrediksiAi extends Model
{
    protected $table = 'prediksi_ai';

    protected $fillable = [
        'produksi_id', 'user_id', 'berat_kedelai_kg', 'jumlah_gambar',
        'good_percent', 'bad_percent', 'uncertain_count', 'coverage_percent',
        'hasil_tahu_kg', 'waktu_memasak_menit', 'durasi_sebelumnya_menit',
        'diterapkan', 'response_json',
    ];

    protected $casts = [
        'berat_kedelai_kg' => 'float',
        'jumlah_gambar' => 'integer',
        'uncertain_count' => 'integer',
        'waktu_memasak_menit' => 'integer',
        'durasi_sebelumnya_menit' => 'integer',
        'good_percent'     => 'float',
        'bad_percent'      => 'float',
        'coverage_percent' => 'float',
        'hasil_tahu_kg'    => 'float',
        'diterapkan'       => 'boolean',
        'response_json'    => 'array',
    ];

    public function produksi(): BelongsTo
    {
        return $this->belongsTo(Produksi::class);
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** 150 -> "2 jam 30 menit" */
    public static function label(?int $menit): string
    {
        if ($menit === null) {
            return '-';
        }

        $jam  = intdiv($menit, 60);
        $sisa = $menit % 60;

        return match (true) {
            $jam > 0 && $sisa > 0 => "{$jam} jam {$sisa} menit",
            $jam > 0              => "{$jam} jam",
            default               => "{$sisa} menit",
        };
    }
}