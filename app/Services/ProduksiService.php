<?php

namespace App\Services;

use App\Enums\StatusProduksi;
use App\Models\BahanBaku;
use App\Models\HasilProduksi;
use App\Models\Produk;
use App\Models\Produksi;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProduksiService
{
    public function __construct(
        private readonly StokService $stok
    ) {
    }

    /**
     * Buat laporan batch baru.
     *
     * 1. Kunci user agar tidak membuat dua batch bersamaan.
     * 2. Validasi bahan baku.
     * 3. Hitung berat kedelai dan biaya.
     * 4. Buat batch.
     * 5. Simpan bahan dan kurangi stok.
     */
    public function buatLaporan(
        array $data,
        array $bahan,
        int $userId
    ): Produksi {
        return DB::transaction(function () use (
            $data,
            $bahan,
            $userId
        ) {
            User::query()
                ->lockForUpdate()
                ->findOrFail($userId);

            $masihBerjalan = Produksi::query()
                ->where('mode_laporan', true)
                ->where('penanggung_jawab_id', $userId)
                ->where('status', StatusProduksi::Berjalan->value)
                ->exists();

            if ($masihBerjalan) {
                throw new DomainException(
                    'Kamu masih memiliki batch yang belum diselesaikan.'
                );
            }

            if (empty($bahan)) {
                throw new DomainException(
                    'Minimal harus memilih satu bahan baku.'
                );
            }

            $ids = collect($bahan)
                ->pluck('bahan_baku_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            if ($ids->count() !== count($bahan)) {
                throw new DomainException(
                    'Bahan baku yang sama tidak boleh dimasukkan dua kali.'
                );
            }

            $models = BahanBaku::query()
                ->whereIn('id', $ids)
                ->where('is_active', true)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($models->count() !== $ids->count()) {
                throw new DomainException(
                    'Ada bahan baku yang tidak ditemukan atau sudah tidak aktif.'
                );
            }

            $beratKedelai = 0;
            $biayaBahan = 0;

            foreach ($bahan as $row) {
                $id = (int) $row['bahan_baku_id'];
                $jumlah = (float) $row['jumlah'];
                $item = $models->get($id);

                if (! $item || $jumlah <= 0) {
                    throw new DomainException(
                        'Jumlah atau data bahan baku tidak valid.'
                    );
                }

                if ($item->kategori === 'utama') {
                    $beratKedelai += $jumlah;
                }

                $biayaBahan +=
                    $jumlah * (float) $item->harga_terakhir;
            }

            if ($beratKedelai <= 0) {
                throw new DomainException(
                    'Minimal harus ada bahan berkategori utama (kedelai).'
                );
            }

            if (
                ! isset($data['durasi_non_masak_estimasi_menit']) ||
                (int) $data['durasi_non_masak_estimasi_menit'] < 1
            ) {
                throw new DomainException(
                    'Estimasi durasi non-pemasakan tidak valid.'
                );
            }

            $produksi = Produksi::create([
                'kode_batch' => 'BTH-' .
                    now()->format('ymd') . '-' .
                    Str::upper(Str::random(5)),

                'tanggal_jadwal' => $data['tanggal_jadwal'],
                'jam_rencana' => null,

                'berat_kedelai_kg' => round($beratKedelai, 2),

                'status' => StatusProduksi::Berjalan,
                'mode_laporan' => true,

                'durasi_non_masak_estimasi_menit' =>
                    (int) $data['durasi_non_masak_estimasi_menit'],

                'waktu_mulai' => now(),

                'penanggung_jawab_id' => $userId,

                'biaya_bahan' => round($biayaBahan, 2),

                'catatan' => $data['catatan'] ?? null,
            ]);

            foreach ($bahan as $row) {
                $id = (int) $row['bahan_baku_id'];
                $jumlah = (float) $row['jumlah'];
                $item = $models->get($id);
                $harga = (float) $item->harga_terakhir;

                $this->stok->keluarBahan(
                    $item,
                    $jumlah,
                    $userId,
                    $produksi,
                    "Pemakaian batch {$produksi->kode_batch}"
                );

                $produksi->bahanBaku()->attach($id, [
                    'jumlah_dipakai' => $jumlah,
                    'harga_satuan' => $harga,
                ]);
            }

            return $produksi;
        }, 3);
    }

    /**
     * Simpan hasil aktual dan selesaikan batch.
     */
    public function selesaikanLaporan(
        Produksi $produksi,
        array $data,
        array $hasil,
        int $userId
    ): Produksi {
        return DB::transaction(function () use (
            $produksi,
            $data,
            $hasil,
            $userId
        ) {
            $produksi = Produksi::query()
                ->lockForUpdate()
                ->findOrFail($produksi->id);

            if (! $produksi->mode_laporan) {
                throw new DomainException(
                    'Batch ini bukan laporan produksi.'
                );
            }

            if ($produksi->status !== StatusProduksi::Berjalan) {
                throw new DomainException(
                    'Laporan ini sudah tidak dapat diubah.'
                );
            }

            if ((int) $produksi->penanggung_jawab_id !== $userId) {
                throw new DomainException(
                    'Kamu tidak memiliki akses untuk menyelesaikan batch ini.'
                );
            }

            if (! $produksi->prediksiAi()->exists()) {
                throw new DomainException(
                    'Jalankan prediksi AI terlebih dahulu sebelum menyelesaikan laporan.'
                );
            }

            if ($produksi->hasil()->exists()) {
                throw new DomainException(
                    'Hasil produksi batch ini sudah pernah disimpan.'
                );
            }

            $durasiMasak = (int) ($data['durasi_masak_aktual_menit'] ?? 0);
            $durasiNonMasak = (int) ($data['durasi_non_masak_aktual_menit'] ?? -1);

            if ($durasiMasak < 1 || $durasiNonMasak < 0) {
                throw new DomainException(
                    'Durasi aktual tidak valid.'
                );
            }

            if (empty($hasil)) {
                throw new DomainException(
                    'Isi minimal satu hasil produksi.'
                );
            }

            $ids = collect($hasil)
                ->pluck('produk_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            if ($ids->count() !== count($hasil)) {
                throw new DomainException(
                    'Produk yang sama tidak boleh dimasukkan dua kali.'
                );
            }

            // Kunci produk secara berurutan untuk mencegah konflik stok.
            $produkModels = Produk::query()
                ->whereIn('id', $ids)
                ->where('is_active', true)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($produkModels->count() !== $ids->count()) {
                throw new DomainException(
                    'Ada produk yang tidak ditemukan atau tidak aktif.'
                );
            }

            $totalBaik = 0;
            $totalReject = 0;

            foreach ($hasil as $row) {
                $produk = $produkModels->get((int) $row['produk_id']);

                $baik = (float) $row['jumlah_baik'];
                $reject = (float) ($row['jumlah_reject'] ?? 0);

                if ($baik <= 0) {
                    throw new DomainException(
                        "Jumlah tahu baik untuk produk {$produk->nama} harus lebih dari nol."
                    );
                }

                if ($reject < 0) {
                    throw new DomainException(
                        "Jumlah tahu reject untuk produk {$produk->nama} tidak boleh negatif."
                    );
                }

                HasilProduksi::create([
                    'produksi_id' => $produksi->id,
                    'produk_id' => $produk->id,
                    'dicatat_oleh' => $userId,
                    'jumlah_baik' => $baik,
                    'jumlah_reject' => $reject,
                    'catatan' => $data['catatan_aktual'] ?? null,
                ]);

                if ($baik > 0) {
                    $this->stok->masukProduk(
                        $produk,
                        $baik,
                        $userId,
                        $produksi,
                        "Hasil batch {$produksi->kode_batch}"
                    );
                }

                $totalBaik += $baik;
                $totalReject += $reject;
            }

            $produksi->update([
                'durasi_masak_aktual_menit' => $durasiMasak,

                'durasi_non_masak_aktual_menit' => $durasiNonMasak,

                'durasi_total_aktual_menit' =>
                    $durasiMasak + $durasiNonMasak,

                'catatan_aktual' => $data['catatan_aktual'] ?? null,

                'status' => StatusProduksi::Selesai,

                'waktu_selesai' => now(),
            ]);

            return $produksi->fresh([
                'hasil.produk',
                'prediksiAi',
                'penanggungJawab',
            ]);
        }, 3);
    }

    /**
     * Batalkan batch dan kembalikan stok bahan baku.
     */
    public function batalLaporan(
        Produksi $produksi,
        int $userId,
        ?string $alasan = null
    ): Produksi {
        return DB::transaction(function () use (
            $produksi,
            $userId,
            $alasan
        ) {
            $produksi = Produksi::query()
                ->lockForUpdate()
                ->findOrFail($produksi->id);

            if (! $produksi->mode_laporan) {
                throw new DomainException(
                    'Batch ini bukan laporan produksi.'
                );
            }

            if ($produksi->status !== StatusProduksi::Berjalan) {
                throw new DomainException(
                    'Hanya batch berjalan yang dapat dibatalkan.'
                );
            }

            if ((int) $produksi->penanggung_jawab_id !== $userId) {
                throw new DomainException(
                    'Kamu tidak memiliki akses untuk membatalkan batch ini.'
                );
            }

            if ($produksi->stok_dikembalikan_at !== null) {
                throw new DomainException(
                    'Stok batch ini sudah pernah dikembalikan.'
                );
            }

            if ($produksi->hasil()->exists()) {
                throw new DomainException(
                    'Batch yang sudah memiliki hasil tidak dapat dibatalkan.'
                );
            }

            $bahanDipakai = $produksi->bahanBaku()
                ->orderBy('bahan_baku.id')
                ->get();

            foreach ($bahanDipakai as $bahan) {
                $this->stok->kembalikanBahan(
                    $bahan,
                    (float) $bahan->pivot->jumlah_dipakai,
                    $userId,
                    $produksi,
                    "Pengembalian bahan dari pembatalan batch {$produksi->kode_batch}"
                );
            }

            $produksi->update([
                'status' => StatusProduksi::Batal,
                'waktu_selesai' => now(),
                'stok_dikembalikan_at' => now(),
                'dibatalkan_oleh' => $userId,
                'alasan_batal' => $alasan,
            ]);

            return $produksi->fresh();
        }, 3);
    }
}
