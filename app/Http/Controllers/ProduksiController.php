<?php

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

class ProduksiController extends Controller
{
    public function __construct(
        private readonly ProduksiService $service,
        private readonly PrediksiAiService $ai,
    ) {
    }

    /**
     * Daftar laporan batch.
     */
    public function index(Request $request)
    {
        $query = Produksi::query()
            ->where('mode_laporan', true)
            ->with([
                'penanggungJawab',
                'hasil.produk',
                'prediksiAi',
            ]);

        // Karyawan hanya melihat laporan miliknya.
        if ($request->user()->hasRole('karyawan')) {
            $query->where(
                'penanggung_jawab_id',
                $request->user()->id
            );
        } elseif (
            ! $request->user()->hasRole('admin') &&
            ! $request->user()->hasRole('manajer')
        ) {
            abort(403);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $produksi = $query
            ->latest('tanggal_jadwal')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('produksi.index', compact('produksi'));
    }

    /**
     * Form pembuatan batch.
     */
    public function create(Request $request)
    {
        $this->pastikanKaryawan($request);

        $adaBatchBerjalan = Produksi::query()
            ->where('mode_laporan', true)
            ->where('penanggung_jawab_id', $request->user()->id)
            ->where('status', StatusProduksi::Berjalan->value)
            ->exists();

        if ($adaBatchBerjalan) {
            return redirect()
                ->route('produksi.index')
                ->with(
                    'error',
                    'Kamu masih memiliki batch yang belum selesai.'
                );
        }

        $bahanJson = BahanBaku::query()
            ->where('is_active', true)
            ->orderBy('kategori')
            ->orderBy('nama')
            ->get()
            ->map(fn (BahanBaku $b) => [
                'id' => $b->id,
                'nama' => $b->nama,
                'kategori' => $b->kategori,
                'satuan' => $b->satuan,
                'stok' => (float) $b->stok_saat_ini,
            ])
            ->values();

        return view('produksi.create', compact('bahanJson'));
    }

    /**
     * Simpan batch baru dan kurangi stok bahan baku.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->pastikanKaryawan($request);

        $v = $request->validate([
            'tanggal_jadwal' => [
                'required',
                'date',
            ],

            'durasi_non_masak_estimasi_menit' => [
                'required',
                'integer',
                'min:1',
                'max:1440',
            ],

            'catatan' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'bahan' => [
                'required',
                'array',
                'min:1',
            ],

            'bahan.*.bahan_baku_id' => [
                'required',
                'integer',
                'distinct',
                'exists:bahan_baku,id',
            ],

            'bahan.*.jumlah' => [
                'required',
                'numeric',
                'gt:0',
            ],
        ], [
            'bahan.required' => 'Pilih minimal satu bahan baku.',
            'bahan.min' => 'Pilih minimal satu bahan baku.',
            'bahan.*.bahan_baku_id.distinct' =>
                'Bahan baku yang sama tidak boleh dipilih dua kali.',
            'bahan.*.jumlah.gt' =>
                'Jumlah bahan baku harus lebih dari nol.',
        ]);

        try {
            $produksi = $this->service->buatLaporan(
                [
                    'tanggal_jadwal' => $v['tanggal_jadwal'],
                    'durasi_non_masak_estimasi_menit' =>
                        $v['durasi_non_masak_estimasi_menit'],
                    'catatan' => $v['catatan'] ?? null,
                ],
                $v['bahan'],
                (int) $request->user()->id
            );
        } catch (DomainException $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('produksi.show', $produksi->id)
            ->with(
                'success',
                'Batch berhasil dibuat. Stok bahan baku sudah dikurangi.'
            );
    }

    /**
     * Detail satu batch.
     */
    public function show(
        Request $request,
        Produksi $produksi
    ) {
        $this->pastikanLaporanBaru($produksi);
        $this->pastikanHakAkses($request, $produksi);

        $produksi->load([
            'penanggungJawab',
            'bahanBaku',
            'hasil.produk',
            'hasil.pencatat',
            'prediksiAi.pengguna',
        ]);

        $produkAktif = Produk::query()
            ->where('is_active', true)
            ->orderBy('nama')
            ->get();

        return view('produksi.show_laporan', [
            'produksi' => $produksi,
            'produkAktif' => $produkAktif,
        ]);
    }

    /**
     * Upload gambar dan jalankan prediksi YOLO + Random Forest.
     */
    public function prediksiAi(
        Request $request,
        Produksi $produksi
    ): RedirectResponse {
        $this->pastikanKaryawan($request);
        $this->pastikanLaporanBaru($produksi);
        $this->pastikanPemilik($request, $produksi);

        if ($produksi->status !== StatusProduksi::Berjalan) {
            return back()->with(
                'error',
                'Prediksi hanya bisa dilakukan pada batch berjalan.'
            );
        }

        $maksimalGambar = max(
            1,
            (int) config('services.soybean_ai.max_images', 300)
        );

        $v = $request->validate([
            'images' => [
                'required',
                'array',
                'min:1',
                'max:' . $maksimalGambar,
            ],

            'images.*' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:10240',
            ],
        ], [
            'images.required' => 'Upload minimal satu foto kedelai.',
            'images.min' => 'Upload minimal satu foto kedelai.',
            'images.max' => 'Jumlah foto melebihi batas yang diizinkan.',
            'images.*.image' => 'File harus berupa gambar.',
            'images.*.max' => 'Ukuran setiap foto maksimal 10 MB.',
        ]);

        try {
            $prediksi = $this->ai->jalankan(
                $produksi,
                (float) $produksi->berat_kedelai_kg,
                $v['images'],
                (int) $request->user()->id
            );
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with(
            'success',
            sprintf(
                'Prediksi berhasil. Good %.2f%%, Bad %.2f%%, estimasi tahu %.2f kg, dan estimasi pemasakan %s.',
                $prediksi->good_percent,
                $prediksi->bad_percent,
                $prediksi->hasil_tahu_kg,
                PrediksiAi::label($prediksi->waktu_memasak_menit)
            )
        );
    }

    /**
     * Simpan hasil aktual dan selesaikan batch.
     */
    public function selesaikanLaporan(
        Request $request,
        Produksi $produksi
    ): RedirectResponse {
        $this->pastikanKaryawan($request);
        $this->pastikanLaporanBaru($produksi);
        $this->pastikanPemilik($request, $produksi);

        $v = $request->validate([
            'durasi_masak_aktual_menit' => [
                'required',
                'integer',
                'min:1',
                'max:1440',
            ],

            'durasi_non_masak_aktual_menit' => [
                'required',
                'integer',
                'min:0',
                'max:10080',
            ],

            'catatan_aktual' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'hasil' => [
                'required',
                'array',
                'min:1',
            ],

            'hasil.*.produk_id' => [
                'required',
                'integer',
                'distinct',
                'exists:produk,id',
            ],

            'hasil.*.jumlah_baik' => [
                'required',
                'numeric',
                'min:0',
            ],

            'hasil.*.jumlah_reject' => [
                'nullable',
                'numeric',
                'min:0',
            ],
        ], [
            'hasil.required' => 'Masukkan hasil produksi.',
            'hasil.min' => 'Masukkan minimal satu hasil produksi.',
            'hasil.*.produk_id.distinct' =>
                'Produk yang sama tidak boleh dimasukkan dua kali.',
        ]);

        try {
            $this->service->selesaikanLaporan(
                $produksi,
                [
                    'durasi_masak_aktual_menit' =>
                        $v['durasi_masak_aktual_menit'],

                    'durasi_non_masak_aktual_menit' =>
                        $v['durasi_non_masak_aktual_menit'],

                    'catatan_aktual' => $v['catatan_aktual'] ?? null,
                ],
                $v['hasil'],
                (int) $request->user()->id
            );
        } catch (DomainException $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('produksi.show', $produksi->id)
            ->with(
                'success',
                'Laporan selesai. Hasil tahu baik sudah ditambahkan ke stok produk.'
            );
    }

    /**
     * Batalkan batch dan kembalikan stok bahan baku.
     */
    public function batalLaporan(
        Request $request,
        Produksi $produksi
    ): RedirectResponse {
        $this->pastikanKaryawan($request);
        $this->pastikanLaporanBaru($produksi);
        $this->pastikanPemilik($request, $produksi);

        $v = $request->validate([
            'alasan_batal' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        try {
            $this->service->batalLaporan(
                $produksi,
                (int) $request->user()->id,
                $v['alasan_batal'] ?? null
            );
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('produksi.index')
            ->with(
                'success',
                'Batch dibatalkan dan stok bahan baku sudah dikembalikan.'
            );
    }

    private function pastikanKaryawan(Request $request): void
    {
        abort_unless(
            $request->user()->hasRole('karyawan'),
            403,
            'Hanya karyawan yang dapat melakukan tindakan ini.'
        );
    }

    private function pastikanLaporanBaru(Produksi $produksi): void
    {
        abort_unless(
            (bool) $produksi->mode_laporan,
            404,
            'Laporan produksi tidak ditemukan.'
        );
    }

    private function pastikanPemilik(
        Request $request,
        Produksi $produksi
    ): void {
        abort_unless(
            (int) $produksi->penanggung_jawab_id ===
            (int) $request->user()->id,
            403,
            'Kamu tidak memiliki akses ke laporan ini.'
        );
    }

    private function pastikanHakAkses(
        Request $request,
        Produksi $produksi
    ): void {
        if ($request->user()->hasRole('karyawan')) {
            $this->pastikanPemilik($request, $produksi);
            return;
        }

        abort_unless(
            $request->user()->hasRole('admin') ||
            $request->user()->hasRole('manajer'),
            403,
            'Kamu tidak memiliki akses ke laporan ini.'
        );
    }
}
