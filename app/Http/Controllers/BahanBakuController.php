<?php

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

        $data['harga_terakhir'] = $data['harga_terakhir'] ?? 0;   // [REVISI #6] harga bisa diisi sejak awal

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
        // pembelianTerakhir dipakai frontend untuk petunjuk di samping opsi koreksi
        return view('bahan-baku.edit', ['bahanBaku' => $bahanBaku->load('pembelianTerakhir')]);
    }

    public function update(Request $request, BahanBaku $bahanBaku): RedirectResponse
    {
        $data = $this->validasi($request, $bahanBaku);

        // [REVISI #6] harga diproses terpisah lewat service; stok TIDAK diubah di sini (pakai Stok Masuk / Opname)
        $hargaBaru = array_key_exists('harga_terakhir', $data) ? (float) $data['harga_terakhir'] : null;
        unset($data['harga_terakhir']);

        $dikoreksi = null;

        DB::transaction(function () use ($bahanBaku, $data, $hargaBaru, $request, &$dikoreksi) {
            $bahanBaku->update($data);

            if ($hargaBaru !== null && abs($hargaBaru - (float) $bahanBaku->harga_terakhir) > 0.001) {
                $dikoreksi = $this->stok->koreksiHarga(
                    $bahanBaku, $hargaBaru, $request->boolean('koreksi_pembelian_terakhir')
                );
            }
        });

        $pesan = 'Bahan baku diperbarui.';
        if ($dikoreksi) {
            $pesan .= ' Pembelian terakhir ('
                . number_format((float) $dikoreksi->jumlah, 2, ',', '.') . " {$bahanBaku->satuan}) ikut dikoreksi menjadi Rp "
                . number_format($hargaBaru, 0, ',', '.') . '.';
        }

        return redirect()->route('bahan-baku.show', $bahanBaku)->with('success', $pesan);
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
            // [REVISI #6] harga: opsional saat tambah, wajib terisi jika dikirim saat ubah
            'harga_terakhir' => $bahan
                ? ['sometimes', 'required', 'numeric', 'min:0']
                : ['nullable', 'numeric', 'min:0'],
        ];

        if (! $bahan) {
            $rules['stok_awal'] = ['nullable', 'numeric', 'min:0'];
        }

        $data = $request->validate($rules, [
            'harga_terakhir.required' => 'Harga wajib diisi.',
            'harga_terakhir.numeric'  => 'Harga harus berupa angka.',
            'harga_terakhir.min'      => 'Harga tidak boleh negatif.',
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}