<?php
// app/Http/Controllers/HasilProduksiController.php

namespace App\Http\Controllers;

use App\Models\Produksi;
use App\Services\ProduksiService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HasilProduksiController extends Controller
{
    public function store(Request $request, Produksi $produksi, ProduksiService $service): RedirectResponse
    {
        // buang baris kosong (produk yang tidak dihasilkan) sebelum validasi
        $baris = collect($request->input('hasil', []))
            ->filter(fn ($r) => (float) ($r['jumlah_baik'] ?? 0) > 0 || (float) ($r['jumlah_reject'] ?? 0) > 0)
            ->values()->all();

        $request->merge(['hasil' => $baris]);

        $v = $request->validate([
            'hasil'                 => ['required', 'array', 'min:1'],
            'hasil.*.produk_id'     => ['required', 'exists:produk,id', 'distinct'],
            'hasil.*.jumlah_baik'   => ['required', 'numeric', 'min:0'],
            'hasil.*.jumlah_reject' => ['nullable', 'numeric', 'min:0'],
            'catatan'               => ['nullable', 'string', 'max:255'],
        ], ['hasil.required' => 'Isi minimal satu hasil produksi (jumlah baik atau reject).']);

        try {
            $service->catatHasil($produksi, $v['hasil'], $request->user()->id, $v['catatan'] ?? null);
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('produksi.show', $produksi)
            ->with('success', 'Hasil produksi tersimpan. Stok tahu bertambah dan batch selesai.');
    }
}