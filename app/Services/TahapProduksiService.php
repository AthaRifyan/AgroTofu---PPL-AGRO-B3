<?php

namespace App\Services;

use App\Enums\StatusProduksi;
use App\Enums\StatusTahap;
use App\Enums\TahapProduksi;
use App\Models\DetailProduksi;
use App\Models\Produksi;
use DomainException;
use Illuminate\Support\Facades\DB;

class TahapProduksiService
{
    /**
     * [REVISI #8 + #9] Mulai tahap: harus berurutan dan wajib ada durasi (menit).
     */
    public function mulaiTahap(Produksi $produksi, TahapProduksi $tahap, int $durasiMenit, int $userId): DetailProduksi
    {
        if ($durasiMenit < 1) {
            throw new DomainException('Durasi tahap minimal 1 menit.');
        }

        return DB::transaction(function () use ($produksi, $tahap, $durasiMenit, $userId) {
            $produksi = Produksi::lockForUpdate()->findOrFail($produksi->id);   // serialisasi aksi per batch
            $this->pastikanBatchBerjalan($produksi);
            $this->sinkronkan($produksi);

            $detail = $produksi->tahapan()->where('tahap', $tahap->value)->firstOrFail();

            if ($detail->status !== StatusTahap::Menunggu) {
                throw new DomainException(
                    "Tahap {$tahap->label()} sudah " . ($detail->status === StatusTahap::Berjalan ? 'berjalan.' : 'selesai.')
                );
            }

            // semua tahap sebelumnya harus SELESAI (termasuk tidak boleh ada yang masih berjalan)
            $belumSelesai = $produksi->tahapan()
                ->where('urutan', '<', $detail->urutan)
                ->where('status', '!=', StatusTahap::Selesai->value)
                ->orderBy('urutan')->first();

            if ($belumSelesai) {
                throw new DomainException(
                    "Selesaikan tahap {$belumSelesai->tahap->label()} terlebih dahulu sebelum memulai tahap {$tahap->label()}."
                );
            }

            $mulai = now();

            $detail->update([
                'status'               => StatusTahap::Berjalan,
                'petugas_id'           => $userId,
                'durasi_rencana_menit' => $durasiMenit,
                'waktu_mulai'          => $mulai,
                'batas_waktu'          => $mulai->copy()->addMinutes($durasiMenit),
                'waktu_selesai'        => null,
                'durasi_aktual_detik'  => null,
                'jenis_selesai'        => null,
            ]);

            return $detail->refresh();
        });
    }

    /**
     * Selesaikan tahap secara manual: durasi sebenarnya tercatat dalam detik.
     * Jika waktunya ternyata sudah habis, tahap sudah diselesaikan otomatis oleh sinkronisasi.
     */
    public function selesaikanTahap(Produksi $produksi, TahapProduksi $tahap, int $userId): DetailProduksi
    {
        return DB::transaction(function () use ($produksi, $tahap) {
            $produksi = Produksi::lockForUpdate()->findOrFail($produksi->id);
            $this->pastikanBatchBerjalan($produksi);
            $this->sinkronkan($produksi);

            $detail = $produksi->tahapan()->where('tahap', $tahap->value)->firstOrFail();

            if ($detail->status === StatusTahap::Selesai) {
                if ($detail->jenis_selesai === 'otomatis') {
                    return $detail;   // waktu habis tepat sebelum tombol ditekan
                }

                throw new DomainException("Tahap {$tahap->label()} sudah diselesaikan.");
            }

            if ($detail->status !== StatusTahap::Berjalan) {
                throw new DomainException("Tahap {$tahap->label()} belum dimulai.");
            }

            $selesai = now();

            $detail->update([
                'status'              => StatusTahap::Selesai,
                'waktu_selesai'       => $selesai,
                'durasi_aktual_detik' => max(0, $selesai->getTimestamp() - $detail->waktu_mulai->getTimestamp()),
                'jenis_selesai'       => 'manual',
            ]);

            return $detail->refresh();
        });
    }

    /**
     * [REVISI #9] Selesaikan otomatis semua tahap yang waktunya sudah habis.
     * Waktu selesai dicatat = batas waktu (bukan "sekarang"), jadi datanya akurat
     * walaupun tidak ada yang membuka halaman saat waktu habis.
     *
     * @return int jumlah tahap yang diselesaikan otomatis
     */
    public function sinkronkan(?Produksi $produksi = null): int
    {
        return DB::transaction(function () use ($produksi) {
            $kedaluwarsa = DetailProduksi::query()
                ->where('status', StatusTahap::Berjalan->value)
                ->whereNotNull('batas_waktu')
                ->where('batas_waktu', '<=', now())
                ->when($produksi, fn ($q) => $q->where('produksi_id', $produksi->id))
                ->lockForUpdate()->get();

            foreach ($kedaluwarsa as $d) {
                $d->update([
                    'status'              => StatusTahap::Selesai,
                    'waktu_selesai'       => $d->batas_waktu,
                    'durasi_aktual_detik' => $d->durasi_rencana_menit * 60,
                    'jenis_selesai'       => 'otomatis',
                ]);
            }

            return $kedaluwarsa->count();
        });
    }

    /**
     * [REVISI #8] Syarat menyimpan hasil: SEMUA tahapan sudah selesai.
     *
     * @throws DomainException
     */
    public function pastikanSemuaSelesai(Produksi $produksi): void
    {
        $this->sinkronkan($produksi);

        $belum = $produksi->tahapan()->where('status', '!=', StatusTahap::Selesai->value)->get();

        if ($belum->isNotEmpty()) {
            throw new DomainException(
                'Semua alur tahapan produksi harus dimulai dan diselesaikan terlebih dahulu sebelum hasil produksi disimpan. '
                . 'Belum selesai: ' . $belum->map(fn ($t) => $t->tahap->label())->implode(', ') . '.'
            );
        }
    }

    private function pastikanBatchBerjalan(Produksi $produksi): void
    {
        if ($produksi->status !== StatusProduksi::Berjalan) {
            throw new DomainException('Tahapan hanya bisa dijalankan saat batch berstatus berjalan. Mulai produksi terlebih dahulu.');
        }
    }
}