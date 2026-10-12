<?php
// app/Http/Controllers/PenjualanController.php

namespace App\Http\Controllers;

use App\Models\Penjualan;
use App\Models\Produk;
use App\Services\PenjualanService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PenjualanController extends Controller
{
    public function __construct(private readonly PenjualanService $service)
    {
    }

    public function index(Request $request)
    {
        $query = Penjualan::query()
            ->when($request->tanggal, fn ($q, $t) => $q->whereDate('tanggal', $t))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s));

        $total     = (float) (clone $query)->where('status', 'selesai')->sum('total');
        $penjualan = $query->with('kasir')->latest('tanggal')->paginate(15)->withQueryString();

        return view('penjualan.index', compact('penjualan', 'total'));
    }

    public function create()
    {
        $produkJson = Produk::where('is_active', true)->orderBy('nama')->get()
            ->map(fn ($p) => [
                'id'     => $p->id,
                'nama'   => $p->nama,
                'harga'  => (float) $p->harga_jual,
                'stok'   => (float) $p->stok,
                'satuan' => $p->satuan,
            ])->values();

        return view('penjualan.create', compact('produkJson'));
    }

    public function store(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'items'               => ['required', 'array', 'min:1'],
            'items.*.produk_id'   => ['required', 'exists:produk,id', 'distinct'],
            'items.*.jumlah'      => ['required', 'numeric', 'gt:0'],
            'metode_pembayaran'   => ['required', Rule::in(Penjualan::METODE)],
            'bayar'               => ['required', 'numeric', 'min:0'],
            'nama_pembeli'        => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $penjualan = $this->service->checkout(
                $request->user()->id, $v['items'], $v['metode_pembayaran'], (float) $v['bayar'], $v['nama_pembeli'] ?? null
            );
        } catch (DomainException $e) {   // termasuk StokTidakCukupException
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('penjualan.show', $penjualan)->with('success', 'Transaksi berhasil disimpan.');
    }

    public function show(Penjualan $penjualan)
    {
        return view('penjualan.show', ['penjualan' => $penjualan->load(['detail.produk', 'kasir'])]);
    }

    public function batal(Request $request, Penjualan $penjualan): RedirectResponse
    {
        try {
            $this->service->batal($penjualan, $request->user()->id);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Nota dibatalkan dan stok dikembalikan.');
    }
}