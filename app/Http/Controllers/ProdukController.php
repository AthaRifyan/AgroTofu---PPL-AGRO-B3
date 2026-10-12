<?php
// app/Http/Controllers/ProdukController.php

namespace App\Http\Controllers;

use App\Models\Produk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProdukController extends Controller
{
    public function index(Request $request)
    {
        $produk = Produk::query()
            ->when($request->q, fn ($q, $s) => $q->where(
                fn ($w) => $w->where('nama', 'like', "%{$s}%")->orWhere('kode', 'like', "%{$s}%")
            ))
            ->orderBy('nama')->paginate(15)->withQueryString();

        return view('produk.index', compact('produk'));
    }

    public function create()
    {
        return view('produk.create');
    }

    public function store(Request $request): RedirectResponse
    {
        Produk::create($this->validasi($request));

        return redirect()->route('produk.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Produk $produk)
    {
        return view('produk.edit', compact('produk'));
    }

    public function update(Request $request, Produk $produk): RedirectResponse
    {
        $produk->update($this->validasi($request, $produk));

        return redirect()->route('produk.index')->with('success', 'Produk diperbarui.');
    }

    public function destroy(Produk $produk): RedirectResponse
    {
        if ($produk->mutasiStok()->exists() || $produk->detailPenjualan()->exists() || $produk->hasilProduksi()->exists()) {
            return back()->with('error', 'Produk sudah punya riwayat transaksi. Nonaktifkan saja, jangan dihapus.');
        }

        $produk->delete();

        return back()->with('success', 'Produk dihapus.');
    }

    private function validasi(Request $request, ?Produk $produk = null): array
    {
        $data = $request->validate([
            'kode'       => ['required', 'string', 'max:20', Rule::unique('produk', 'kode')->ignore($produk)],
            'nama'       => ['required', 'string', 'max:100'],
            'satuan'     => ['required', Rule::in(Produk::SATUAN)],
            'harga_jual' => ['required', 'numeric', 'min:0'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}