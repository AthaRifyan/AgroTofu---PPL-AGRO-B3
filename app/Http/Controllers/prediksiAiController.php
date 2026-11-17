<?php
// app/Http/Controllers/PrediksiAiController.php

namespace App\Http\Controllers;

use App\Enums\StatusProduksi;
use App\Models\PrediksiAi;
use App\Models\Produksi;

/**
 * Halaman menu "Prediksi AI": daftar batch yang sedang berjalan + riwayat semua prediksi.
 * Proses upload foto tetap dilakukan di halaman detail batch (ProduksiController@prediksiAi).
 */
class PrediksiAiController extends Controller
{
    public function index()
    {
        $berjalan = Produksi::with(['tahapan', 'prediksiAi'])
            ->where('status', StatusProduksi::Berjalan->value)
            ->latest('tanggal_jadwal')->latest('id')
            ->get();

        $riwayat = PrediksiAi::with(['produksi', 'pengguna'])
            ->latest('id')
            ->paginate(15);

        return view('prediksi-ai.index', compact('berjalan', 'riwayat'));
    }
}