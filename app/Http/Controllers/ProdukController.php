<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use App\Services\StokService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        return view('produk.create', [
            'katalog'  => Produk::KATALOG,                                              // isi dropdown
            'produkAda' => Produk::whereIn('nama', Produk::namaTersedia())->get()->keyBy('nama'), // stok/harga saat ini
        ]);
    }

    /**
     * [REVISI #10] "Tambah produk" bersifat upsert:
     * - produk belum ada  -> dibuat
     * - produk sudah ada  -> TIDAK dibuat ulang; harga diperbarui dan stok ditambahkan ke baris yang sama
     */
    public function store(Request $request, StokService $stok): RedirectResponse
    {
        $v = $request->validate([
            'nama'        => ['required', Rule::in(Produk::namaTersedia())],
            'harga_jual'  => ['required', 'numeric', 'min:0'],
            'tambah_stok' => ['nullable', 'numeric', 'min:0'],
        ], [
            'nama.required'      => 'Pilih produk terlebih dahulu.',
            'nama.in'            => 'Produk tidak valid. Pilih Tahu Putih atau Tahu Kuning.',
            'harga_jual.required' => 'Harga jual wajib diisi.',
            'harga_jual.numeric'  => 'Harga jual harus berupa angka.',
            'tambah_stok.numeric' => 'Jumlah stok harus berupa angka.',
            'tambah_stok.min'     => 'Jumlah stok tidak boleh negatif.',
        ]);

        $katalog = Produk::KATALOG[$v['nama']];
        $tambah  = (float) ($v['tambah_stok'] ?? 0);

        [$produk, $baru] = DB::transaction(function () use ($v, $katalog, $tambah, $request, $stok) {
            $produk = Produk::firstOrCreate(
                ['nama' => $v['nama']],
                ['kode' => $katalog['kode'], 'satuan' => $katalog['satuan'], 'harga_jual' => $v['harga_jual'], 'is_active' => true]
            );
            $baru = $produk->wasRecentlyCreated;

            if (! $baru) {
                $produk->update(['harga_jual' => $v['harga_jual']]);
            }

            if ($tambah > 0) {
                // lewat StokService agar tercatat di riwayat (siapa, kapan, sebelum/sesudah)
                $stok->masukProduk($produk, $tambah, $request->user()->id, null, 'Penambahan stok manual (menu Tambah Produk)');
            }

            return [$produk->fresh(), $baru];
        });

        $pesan = $baru
            ? "Produk {$produk->nama} ditambahkan."
            : "Produk {$produk->nama} sudah ada, data diperbarui (tidak dibuat ganda).";

        if ($tambah > 0) {
            $pesan .= ' Stok bertambah ' . number_format($tambah, 2, ',', '.') . " {$produk->satuan}"
                . ' (stok sekarang ' . number_format((float) $produk->stok, 2, ',', '.') . " {$produk->satuan}).";
        }

        return redirect()->route('produk.index')->with('success', $pesan);
    }

    public function edit(Produk $produk)
    {
        return view('produk.edit', compact('produk'));
    }

    /** Nama, kode, dan satuan terkunci (katalog tetap). Yang bisa diubah: harga jual dan status aktif. */
    public function update(Request $request, Produk $produk): RedirectResponse
    {
        $v = $request->validate(
            ['harga_jual' => ['required', 'numeric', 'min:0']],
            ['harga_jual.required' => 'Harga jual wajib diisi.', 'harga_jual.numeric' => 'Harga jual harus berupa angka.']
        );

        $produk->update(['harga_jual' => $v['harga_jual'], 'is_active' => $request->boolean('is_active')]);

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
}