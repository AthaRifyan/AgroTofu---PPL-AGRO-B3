<?php

namespace App\Http\Controllers;

use App\Enums\StatusProduksi;
use App\Enums\StatusTahap;
use App\Enums\TahapProduksi;
use App\Models\DetailProduksi;
use App\Models\Produksi;
use App\Services\TahapProduksiService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DetailProduksiController extends Controller
{
    /**
     * Mulai / selesaikan satu tahap. Kontrak form tidak berubah, hanya ditambah field durasi:
     *   PATCH produksi/{produksi}/tahap/{tahap}
     *   status=berjalan + durasi_menit=10   -> mulai tahap
     *   status=selesai                      -> selesaikan manual
     */
    public function update(Request $request, Produksi $produksi, TahapProduksi $tahap, TahapProduksiService $service): RedirectResponse
    {
        // bag per tahap supaya error durasi tidak tercampur antar kartu tahap di halaman yang sama
        $v = $request->validateWithBag("tahap_{$tahap->value}", [
            'status'       => ['required', Rule::in([StatusTahap::Berjalan->value, StatusTahap::Selesai->value])],
            'durasi_menit' => ['required_if:status,berjalan', 'nullable', 'integer', 'min:1', 'max:1440'],
        ], [
            'durasi_menit.required_if' => 'Durasi tahap (menit) harus diisi sebelum memulai tahap.',
            'durasi_menit.integer'     => 'Durasi tahap harus berupa angka bulat (menit).',
            'durasi_menit.min'         => 'Durasi tahap minimal 1 menit.',
            'durasi_menit.max'         => 'Durasi tahap maksimal 1440 menit (24 jam).',
        ]);

        try {
            if ($v['status'] === StatusTahap::Berjalan->value) {
                $service->mulaiTahap($produksi, $tahap, (int) $v['durasi_menit'], $request->user()->id);
                $pesan = "Tahap {$tahap->label()} dimulai ({$v['durasi_menit']} menit).";
            } else {
                $detail = $service->selesaikanTahap($produksi, $tahap, $request->user()->id);
                $pesan  = $detail->jenis_selesai === 'otomatis'
                    ? "Waktu tahap {$tahap->label()} sudah habis, tahap diselesaikan otomatis."
                    : "Tahap {$tahap->label()} selesai. {$detail->keterangan_selesai}.";
            }
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $pesan);
    }

    /**
     * JSON status semua tahap (untuk countdown di frontend). Selalu menyinkronkan tahap yang
     * waktunya habis lebih dulu, dan mengirim waktu server agar countdown tidak bergantung jam perangkat.
     *   GET produksi/{produksi}/tahapan
     */
    public function index(Request $request, Produksi $produksi, TahapProduksiService $service): JsonResponse
    {
        $service->sinkronkan($produksi);

        $tahapan = $produksi->tahapan()->with('petugas:id,name')->get();
        $sebelumnyaSelesai = true;

        $data = $tahapan->map(function (DetailProduksi $t) use (&$sebelumnyaSelesai, $produksi) {
            $bisaDimulai = $produksi->status === StatusProduksi::Berjalan
                && $t->status === StatusTahap::Menunggu
                && $sebelumnyaSelesai;

            $sebelumnyaSelesai = $sebelumnyaSelesai && $t->status === StatusTahap::Selesai;

            return [
                'tahap'                => $t->tahap->value,
                'label'                => $t->tahap->label(),
                'urutan'               => $t->urutan,
                'status'               => $t->status->value,
                'bisa_dimulai'         => $bisaDimulai,
                'durasi_rencana_menit' => $t->durasi_rencana_menit,
                'waktu_mulai'          => $t->waktu_mulai?->toIso8601String(),
                'batas_waktu'          => $t->batas_waktu?->toIso8601String(),
                'sisa_detik'           => $t->sisa_detik,
                'jenis_selesai'        => $t->jenis_selesai,
                'durasi_aktual_detik'  => $t->durasi_aktual_detik,
                'keterangan_selesai'   => $t->keterangan_selesai,
                'petugas'              => $t->petugas?->name,
            ];
        });

        return response()->json([
            'server_time'   => now()->toIso8601String(),
            'status_batch'  => $produksi->status->value,
            'semua_selesai' => $tahapan->every(fn ($t) => $t->status === StatusTahap::Selesai),
            'tahapan'       => $data->values(),
        ]);
    }
}