<?php
// app/Http/Controllers/StokProdukController.php

namespace App\Http\Controllers;

use App\Models\Produk;
use App\Models\StokProduk;
use App\Services\StokService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StokProdukController extends Controller
{
    public function index()
    {
        return view('stok-produk.index', [
            'produk'  => Produk::orderBy('nama')->get(),
            'riwayat' => StokProduk::with(['produk', 'user'])->latest('id')->paginate(15),
        ]);
    }

    public function opname(Request $request, Produk $produk, StokService $stok): RedirectResponse
    {
        $v = $request->validate([
            'stok_fisik' => ['required', 'numeric', 'min:0'],
            'catatan'    => ['required', 'string', 'max:255'],
        ]);

        $stok->opnameProduk($produk, (float) $v['stok_fisik'], $request->user()->id, $v['catatan']);

        return back()->with('success', "Stok opname {$produk->nama} tersimpan.");
    }
}