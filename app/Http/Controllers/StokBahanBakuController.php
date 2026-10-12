<?php
// app/Http/Controllers/StokBahanBakuController.php

namespace App\Http\Controllers;

use App\Models\BahanBaku;
use App\Services\StokService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StokBahanBakuController extends Controller
{
    public function __construct(private readonly StokService $stok)
    {
    }

    /** Stok masuk dari pembelian supplier (otomatis tercatat sebagai pengeluaran). */
    public function masuk(Request $request, BahanBaku $bahanBaku): RedirectResponse
    {
        $v = $request->validate([
            'jumlah'       => ['required', 'numeric', 'gt:0'],
            'harga_satuan' => ['required', 'numeric', 'min:0'],
            'supplier_id'  => ['nullable', 'exists:suppliers,id'],
            'tanggal'      => ['required', 'date'],
            'catatan'      => ['nullable', 'string', 'max:255'],
        ]);

        $this->stok->masukBahan(
            $bahanBaku, (float) $v['jumlah'], (float) $v['harga_satuan'], $request->user()->id,
            $v['supplier_id'] ?? null, $v['catatan'] ?? null, $v['tanggal']
        );

        return back()->with('success', "Stok {$bahanBaku->nama} bertambah {$v['jumlah']} {$bahanBaku->satuan}.");
    }

    /** Stok opname: samakan stok sistem dengan hasil hitung fisik. */
    public function opname(Request $request, BahanBaku $bahanBaku): RedirectResponse
    {
        $v = $request->validate([
            'stok_fisik' => ['required', 'numeric', 'min:0'],
            'catatan'    => ['required', 'string', 'max:255'],
        ]);

        $this->stok->opnameBahan($bahanBaku, (float) $v['stok_fisik'], $request->user()->id, $v['catatan']);

        return back()->with('success', "Stok opname {$bahanBaku->nama} tersimpan (stok fisik {$v['stok_fisik']} {$bahanBaku->satuan}).");
    }
}