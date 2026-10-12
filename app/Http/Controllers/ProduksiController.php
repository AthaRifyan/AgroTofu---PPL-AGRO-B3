<?php
// app/Http/Controllers/ProduksiController.php

namespace App\Http\Controllers;

use App\Enums\StatusProduksi;
use App\Models\BahanBaku;
use App\Models\Produk;
use App\Models\Produksi;
use App\Services\ProduksiService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class ProduksiController extends Controller
{
    public function __construct(private readonly ProduksiService $service)
    {
    }

    public function index(Request $request)
    {
        $produksi = Produksi::with('penanggungJawab')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest('tanggal_jadwal')->latest('id')
            ->paginate(15)->withQueryString();

        return view('produksi.index', compact('produksi'));
    }

    public function create()
    {
        $bahanJson = BahanBaku::where('is_active', true)->orderBy('kategori')->orderBy('nama')->get()
            ->map(fn ($b) => [
                'id'       => $b->id,
                'nama'     => $b->nama,
                'kategori' => $b->kategori,
                'satuan'   => $b->satuan,
                'stok'     => (float) $b->stok_saat_ini,
            ])->values();

        return view('produksi.create', compact('bahanJson'));
    }

    public function store(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'tanggal_jadwal'        => ['required', 'date'],
            'jam_rencana'           => ['nullable', 'date_format:H:i'],
            'catatan'               => ['nullable', 'string'],
            'langsung_mulai'        => ['sometimes', 'boolean'],
            'bahan'                 => ['required', 'array', 'min:1'],
            'bahan.*.bahan_baku_id' => ['required', 'exists:bahan_baku,id', 'distinct'],
            'bahan.*.jumlah'        => ['required', 'numeric', 'gt:0'],
        ]);

        try {
            $produksi = $this->service->jadwalkan(
                Arr::except($v, ['bahan', 'langsung_mulai']),
                $v['bahan'],
                $request->user()->id,
                $request->boolean('langsung_mulai')
            );
        } catch (DomainException $e) {   // termasuk StokTidakCukupException
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('produksi.show', $produksi)->with(
            'success',
            $produksi->status === StatusProduksi::Berjalan
                ? 'Batch dimulai. Stok bahan baku sudah dikurangi.'
                : 'Jadwal produksi dibuat.'
        );
    }

    public function show(Produksi $produksi)
    {
        return view('produksi.show', [
            'produksi'   => $produksi->load(['penanggungJawab', 'tahapan.petugas', 'bahanBaku', 'hasil.produk', 'hasil.pencatat']),
            'produkAktif' => Produk::where('is_active', true)->orderBy('nama')->get(),
        ]);
    }

    public function mulai(Produksi $produksi, Request $request): RedirectResponse
    {
        try {
            $this->service->mulai($produksi, $request->user()->id);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Batch dimulai. Stok bahan baku sudah dikurangi.');
    }

    public function batal(Produksi $produksi): RedirectResponse
    {
        try {
            $this->service->batal($produksi);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Jadwal produksi dibatalkan.');
    }
}