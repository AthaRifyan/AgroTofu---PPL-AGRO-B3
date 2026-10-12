<?php
// app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use App\Enums\StatusProduksi;
use App\Models\BahanBaku;
use App\Models\Pengeluaran;
use App\Models\Penjualan;
use App\Models\Produk;
use App\Models\Produksi;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $data = ['produkAktif' => Produk::where('is_active', true)->orderBy('nama')->get()];

        if ($user->hasAnyRole(['admin', 'manajer'])) {
            $awalBulan   = now()->startOfMonth();
            $pendapatan  = (float) Penjualan::where('status', 'selesai')->where('tanggal', '>=', $awalBulan)->sum('total');
            $pengeluaran = (float) Pengeluaran::where('tanggal', '>=', $awalBulan->toDateString())->sum('jumlah');

            $data += [
                'stokMenipis'       => BahanBaku::stokMenipis()->where('is_active', true)->orderBy('nama')->get(),
                'produksiBerjalan'  => Produksi::where('status', StatusProduksi::Berjalan->value)->count(),
                'produksiTerjadwal' => Produksi::where('status', StatusProduksi::Terjadwal->value)->count(),
                'pendapatan'        => $pendapatan,
                'pengeluaran'       => $pengeluaran,
                'laba'              => $pendapatan - $pengeluaran,
            ];
        }

        if ($user->hasAnyRole(['admin', 'manajer', 'kasir'])) {
            $data['penjualanHariIni'] = (float) Penjualan::where('status', 'selesai')
                ->whereDate('tanggal', today())->sum('total');
        }

        return view('dashboard', $data);
    }
}
