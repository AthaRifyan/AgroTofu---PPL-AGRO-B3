<?php
// app/Http/Controllers/ProduksiController.php

namespace App\Http\Controllers;

use App\Enums\StatusProduksi;
use App\Models\BahanBaku;
use App\Models\PrediksiAi;
use App\Models\Produk;
use App\Models\Produksi;
use App\Services\PrediksiAiService;
use App\Services\ProduksiService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ProduksiController extends Controller
{
    public function __construct(
        private readonly ProduksiService $service,
        private readonly PrediksiAiService $ai,
    ) {
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
            'jam_rencana'           => ['required', 'date_format:H:i'],
            'durasi_perebusan_menit' => ['required', 'integer', 'min:1', 'max:1440'],
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
            'produksi'    => $produksi->load([
                'penanggungJawab', 'tahapan.petugas', 'bahanBaku',
                'hasil.produk', 'hasil.pencatat', 'prediksiAi.pengguna',
            ]),
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

    /**
     * Karyawan upload foto kedelai + berat -> Flask -> durasi tahap pemasakan
     * disesuaikan otomatis.
     */
    public function prediksiAi(Produksi $produksi, Request $request): RedirectResponse
    {
        $v = $request->validate([
            'berat_kedelai_kg' => ['required', 'numeric', 'gt:0'],
            'images'           => ['required', 'array', 'min:1', 'max:' . config('services.soybean_ai.max_images')],
            'images.*'         => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ], [
            'images.required' => 'Upload minimal satu foto biji kedelai.',
            'images.max'      => 'Maksimal :max foto dalam sekali prediksi.',
            'images.*.image'  => 'Semua file harus berupa gambar.',
            'images.*.max'    => 'Ukuran tiap foto maksimal 10 MB.',
        ]);

        try {
            $prediksi = $this->ai->jalankan(
                $produksi,
                (float) $v['berat_kedelai_kg'],
                $request->file('images'),
                $request->user()->id,
            );
        } catch (DomainException $e) {
            return back()->withInput(['berat_kedelai_kg' => $v['berat_kedelai_kg']])
                ->with('error', $e->getMessage());
        }

        $ringkas = sprintf(
            'Good %.1f%% · Bad %.1f%% · estimasi hasil tahu %.2f kg.',
            $prediksi->good_percent,
            $prediksi->bad_percent,
            $prediksi->hasil_tahu_kg,
        );

        if ($prediksi->diterapkan) {
            if ($prediksi->durasi_sebelumnya_menit === $prediksi->waktu_memasak_menit) {
                $pesan = sprintf(
                    'Durasi pemasakan tetap %s karena sesuai dengan prediksi AI. %s',
                    PrediksiAi::label($prediksi->waktu_memasak_menit),
                    $ringkas,
                );
            } else {
                $pesan = sprintf(
                    'Durasi pemasakan berhasil disesuaikan dari %s menjadi %s. %s',
                    PrediksiAi::label($prediksi->durasi_sebelumnya_menit),
                    PrediksiAi::label($prediksi->waktu_memasak_menit),
                    $ringkas,
                );
            }

            return back()->with('success', $pesan);
        }

        return back()->with('success',
            'Prediksi tersimpan, durasi pemasakan tidak berubah (sudah sesuai atau tahapnya sudah berjalan). ' . $ringkas
        );
    }
}