<?php
// app/Http/Controllers/BahanBakuController.php

namespace App\Http\Controllers;

use App\Models\BahanBaku;
use App\Models\Supplier;
use App\Services\StokService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BahanBakuController extends Controller
{
    public function __construct(private readonly StokService $stok)
    {
    }

    public function index(Request $request)
    {
        $bahanBaku = BahanBaku::query()
            ->when($request->q, fn ($q, $s) => $q->where(
                fn ($w) => $w->where('nama', 'like', "%{$s}%")->orWhere('kode', 'like', "%{$s}%")
            ))
            ->when($request->boolean('menipis'), fn ($q) => $q->stokMenipis())
            ->orderBy('nama')->paginate(15)->withQueryString();

        return view('bahan-baku.index', compact('bahanBaku'));
    }

    public function create()
    {
        return view('bahan-baku.create', [
            'bahanBaku' => new BahanBaku(['satuan' => 'kg', 'kategori' => 'pendukung', 'stok_minimum' => 0]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data     = $this->validasi($request);
        $stokAwal = (float) ($data['stok_awal'] ?? 0);
        unset($data['stok_awal']);

        DB::transaction(function () use ($data, $stokAwal, $request) {
            $bahan = BahanBaku::create($data);

            if ($stokAwal > 0) {
                $this->stok->opnameBahan($bahan, $stokAwal, $request->user()->id, 'Stok awal');
            }
        });

        return redirect()->route('bahan-baku.index')->with('success', 'Bahan baku berhasil ditambahkan.');
    }

    public function show(BahanBaku $bahanBaku)
    {
        return view('bahan-baku.show', [
            'bahanBaku' => $bahanBaku,
            'riwayat'   => $bahanBaku->mutasiStok()->with(['supplier', 'user'])->latest('id')->paginate(10),
            'suppliers' => Supplier::where('is_active', true)->orderBy('nama')->get(),
        ]);
    }

    public function edit(BahanBaku $bahanBaku)
    {
        return view('bahan-baku.edit', compact('bahanBaku'));
    }

    public function update(Request $request, BahanBaku $bahanBaku): RedirectResponse
    {
        // stok TIDAK diubah di sini; gunakan "Stok Masuk" atau "Stok Opname"
        $bahanBaku->update($this->validasi($request, $bahanBaku));

        return redirect()->route('bahan-baku.show', $bahanBaku)->with('success', 'Bahan baku diperbarui.');
    }

    public function destroy(BahanBaku $bahanBaku): RedirectResponse
    {
        if ($bahanBaku->mutasiStok()->exists() || $bahanBaku->produksi()->exists()) {
            return back()->with('error', 'Bahan sudah punya riwayat stok/produksi. Nonaktifkan saja, jangan dihapus.');
        }

        $bahanBaku->delete();

        return redirect()->route('bahan-baku.index')->with('success', 'Bahan baku dihapus.');
    }

    private function validasi(Request $request, ?BahanBaku $bahan = null): array
    {
        $rules = [
            'kode'         => ['required', 'string', 'max:20', Rule::unique('bahan_baku', 'kode')->ignore($bahan)],
            'nama'         => ['required', 'string', 'max:100'],
            'kategori'     => ['required', Rule::in(BahanBaku::KATEGORI)],
            'satuan'       => ['required', Rule::in(BahanBaku::SATUAN)],
            'stok_minimum' => ['required', 'numeric', 'min:0'],
            'keterangan'   => ['nullable', 'string'],
        ];

        if (! $bahan) {
            $rules['stok_awal'] = ['nullable', 'numeric', 'min:0'];
        }

        $data = $request->validate($rules);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}