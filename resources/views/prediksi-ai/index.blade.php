
{{-- resources/views/prediksi-ai/index.blade.php --}}

<x-app-layout>
    <x-page-header
        title="Prediksi AI"
        eyebrow="Produksi"
        subtitle="Cek kualitas kedelai dari foto dan isi durasi tahap pemasakan secara otomatis."
    />

    {{-- ============================================================
         BATCH YANG SEDANG BERJALAN
    ============================================================ --}}
    <x-card class="mt-6 !p-0 overflow-hidden">
        <div class="px-5 pb-4 pt-5 sm:px-6 sm:pt-6">
            <h2 class="text-headline-sm font-bold text-on-surface">
                Batch yang sedang berjalan
            </h2>

            <p class="mt-1 text-body-sm text-on-surface-variant">
                Pilih batch untuk membuka halaman produksi dan menjalankan prediksi AI.
            </p>
        </div>

        <div class="w-full overflow-x-auto">
            <table class="w-full min-w-[850px] table-fixed text-left">

                {{-- Header tabel batch --}}
                <thead>
                    <tr class="border-y border-outline-variant/30 bg-surface-container-low">
                        <th class="w-[28%] px-5 py-3 text-label-sm font-semibold text-on-surface-variant sm:px-6">
                            Batch
                        </th>

                        <th class="w-[13%] px-3 py-3 text-label-sm font-semibold text-on-surface-variant">
                            Kedelai
                        </th>

                        <th class="w-[25%] px-3 py-3 text-label-sm font-semibold text-on-surface-variant">
                            Prediksi terakhir
                        </th>

                        <th class="w-[26%] px-3 py-3 text-label-sm font-semibold text-on-surface-variant">
                            Durasi pemasakan
                        </th>

                        <th class="w-[8%] px-5 py-3 sm:px-6"></th>
                    </tr>
                </thead>

                {{-- Isi tabel batch --}}
                <tbody class="divide-y divide-surface-container-low">
                    @forelse ($berjalan as $p)
                        @php
                            $terakhir = $p->prediksiAi->first();

                            $masak = $p->tahapan->first(
                                fn ($t) => $t->tahap === \App\Enums\TahapProduksi::Pemasakan
                            );
                        @endphp

                        <tr class="transition-colors hover:bg-surface-container-low/60">

                            {{-- Batch --}}
                            <td class="px-5 py-4 sm:px-6">
                                <a
                                    href="{{ route('produksi.show', $p) }}#prediksi-ai"
                                    class="flex min-w-0 items-center gap-3 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                                >
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-tm-md bg-primary-container text-on-primary-container">
                                        <x-icon name="precision_manufacturing" class="text-[20px]" />
                                    </span>

                                    <div class="min-w-0">
                                        <div class="truncate text-label-lg font-semibold text-on-surface">
                                            {{ $p->kode_batch }}
                                        </div>

                                        <div class="text-body-sm text-on-surface-variant">
                                            {{ $p->tanggal_jadwal->translatedFormat('d M Y') }}
                                        </div>
                                    </div>
                                </a>
                            </td>

                            {{-- Berat kedelai --}}
                            <td class="px-3 py-4">
                                <div class="text-body-md text-on-surface">
                                    <x-qty :value="$p->berat_kedelai_kg" unit="kg" />
                                </div>
                            </td>

                            {{-- Prediksi terakhir --}}
                            <td class="px-3 py-4">
                                @if ($terakhir)
                                    <div class="text-body-md font-medium text-on-surface">
                                        Good {{ number_format($terakhir->good_percent, 1) }}%
                                    </div>

                                    <div class="text-body-sm text-on-surface-variant">
                                        {{ number_format($terakhir->hasil_tahu_kg, 2) }} kg tahu
                                    </div>

                                    <div class="mt-0.5 text-body-sm text-on-surface-variant">
                                        {{ $terakhir->created_at->translatedFormat('d M, H:i') }}
                                    </div>
                                @else
                                    <span class="text-body-sm text-on-surface-variant">
                                        Belum ada prediksi
                                    </span>
                                @endif
                            </td>

                            {{-- Durasi pemasakan --}}
                            <td class="px-3 py-4">
                                @if ($masak && $masak->durasi_rencana_menit)
                                    <div class="text-body-md font-medium text-on-surface">
                                        {{ \App\Models\PrediksiAi::label($masak->durasi_rencana_menit) }}
                                    </div>

                                    @if ($masak->disesuaikan_ai)
                                        <span class="text-body-sm text-secondary">
                                            Diisi AI
                                        </span>
                                    @endif
                                @else
                                    <span class="text-body-sm text-on-surface-variant">
                                        Belum diisi
                                    </span>
                                @endif
                            </td>

                            {{-- Aksi --}}
                            <td class="px-5 py-4 text-right sm:px-6">
                                <a
                                    href="{{ route('produksi.show', $p) }}#prediksi-ai"
                                    aria-label="Buka batch {{ $p->kode_batch }}"
                                    class="inline-flex items-center justify-center text-outline hover:text-primary"
                                >
                                    <x-icon name="chevron_right" class="text-[20px]" />
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-6">
                                <x-empty-state
                                    icon="science"
                                    title="Tidak ada batch yang berjalan"
                                    text="Prediksi AI hanya bisa dijalankan pada batch yang sudah dimulai."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>


    
{{-- ============================================================
     RIWAYAT SEMUA PREDIKSI - GRID VIEW
============================================================ --}}
<x-card class="mt-5 !p-0 overflow-hidden">

    <div class="px-5 pb-4 pt-5 sm:px-6 sm:pt-6">
        <h2 class="text-headline-sm font-bold text-on-surface">
            Riwayat prediksi
        </h2>

        <p class="mt-1 text-body-sm text-on-surface-variant">
            Daftar hasil prediksi AI yang pernah dijalankan.
        </p>
    </div>

    <div class="w-full overflow-x-auto">

        {{-- GRID CONTAINER --}}
        <div class="min-w-[1100px]">

            {{-- HEADER GRID --}}
            <div class="grid grid-cols-[18%_12%_12%_12%_12%_22%_12%] border-y border-outline-variant/30 bg-surface-container-low">

                <div class="px-5 py-3 text-label-sm font-semibold text-on-surface-variant sm:px-6">
                    Waktu / oleh
                </div>

                <div class="px-3 py-3 text-label-sm font-semibold text-on-surface-variant">
                    Batch
                </div>

                <div class="px-3 py-3 text-label-sm font-semibold text-on-surface-variant">
                    Berat / foto
                </div>

                <div class="px-3 py-3 text-label-sm font-semibold text-on-surface-variant">
                    Good / Bad
                </div>

                <div class="px-3 py-3 text-label-sm font-semibold text-on-surface-variant">
                    Hasil tahu
                </div>

                <div class="px-3 py-3 text-label-sm font-semibold text-on-surface-variant">
                    Durasi masak
                </div>

                <div class="px-5 py-3 text-label-sm font-semibold text-on-surface-variant sm:px-6">
                    Diterapkan
                </div>

            </div>

            {{-- BODY GRID --}}
            <div class="divide-y divide-surface-container-low">

                @forelse ($riwayat as $r)

                    <div class="grid grid-cols-[18%_12%_12%_12%_12%_22%_12%] transition-colors hover:bg-surface-container-low/40">

                        {{-- WAKTU / OLEH --}}
                        <div class="min-w-0 px-5 py-4 sm:px-6">

                            <div class="text-label-md font-semibold text-on-surface">
                                {{ $r->created_at->translatedFormat('d M Y') }}
                            </div>

                            <div class="text-body-sm text-on-surface-variant">
                                {{ $r->created_at->translatedFormat('H:i') }}
                            </div>

                            <div
                                class="mt-1 truncate text-body-sm text-on-surface-variant"
                                title="{{ $r->pengguna?->name ?? '-' }}"
                            >
                                {{ $r->pengguna?->name ?? '-' }}
                            </div>

                        </div>

                        {{-- BATCH --}}
                        <div class="min-w-0 break-words px-3 py-4">

                            @if ($r->produksi)

                                <a
                                    href="{{ route('produksi.show', $r->produksi) }}"
                                    class="text-label-md font-semibold text-secondary hover:underline"
                                >
                                    {{ $r->produksi->kode_batch }}
                                </a>

                            @else

                                <span class="text-on-surface-variant">
                                    -
                                </span>

                            @endif

                        </div>

                        {{-- BERAT / FOTO --}}
                        <div class="px-3 py-4">

                            <div class="font-medium text-on-surface">
                                {{ number_format($r->berat_kedelai_kg, 2) }} kg
                            </div>

                            <div class="text-body-sm text-on-surface-variant">
                                {{ $r->jumlah_gambar }} foto
                            </div>

                        </div>

                        {{-- GOOD / BAD --}}
                        <div class="px-3 py-4">

                            <div class="font-medium text-on-surface">
                                {{ number_format($r->good_percent, 1) }}%
                            </div>

                            <div class="text-body-sm text-on-surface-variant">
                                {{ number_format($r->bad_percent, 1) }}%
                            </div>

                        </div>

                        {{-- HASIL TAHU --}}
                        <div class="px-3 py-4">

                            <span class="font-semibold text-on-surface">
                                {{ number_format($r->hasil_tahu_kg, 2) }} kg
                            </span>

                        </div>

                        {{-- DURASI MASAK --}}
                        <div class="min-w-0 px-3 py-4">

                            @if (
                                $r->durasi_sebelumnya_menit !== null
                                && $r->durasi_sebelumnya_menit !== $r->waktu_memasak_menit
                            )

                                <div class="text-body-sm text-on-surface-variant">
                                    Sebelumnya
                                </div>

                                <div class="break-words font-medium text-on-surface">
                                    {{ \App\Models\PrediksiAi::label($r->durasi_sebelumnya_menit) }}
                                </div>

                                <div class="my-0.5 text-secondary">
                                    <x-icon name="expand_more" class="text-[16px]" />
                                </div>

                                <div class="break-words font-semibold text-on-surface">
                                    {{ \App\Models\PrediksiAi::label($r->waktu_memasak_menit) }}
                                </div>

                            @else

                                <div class="break-words font-semibold text-on-surface">
                                    {{ \App\Models\PrediksiAi::label($r->waktu_memasak_menit) }}
                                </div>

                            @endif

                        </div>

                        {{-- DITERAPKAN --}}
                        <div class="px-5 py-4 sm:px-6">

                            @if ($r->diterapkan)

                                <span class="inline-flex items-center rounded-full bg-secondary-container px-2.5 py-1 text-label-sm text-on-secondary-container">
                                    Ya
                                </span>

                            @else

                                <span class="inline-flex items-center rounded-full bg-surface-container-high px-2.5 py-1 text-label-sm text-on-surface-variant">
                                    Tidak
                                </span>

                            @endif

                        </div>

                    </div>

                @empty

                    <div class="p-6">
                        <x-empty-state
                            icon="science"
                            title="Belum ada prediksi"
                            text="Riwayat muncul setelah prediksi AI pertama dijalankan."
                        />
                    </div>

                @endforelse

            </div>

        </div>
    </div>

    {{-- PAGINATION --}}
    @if ($riwayat->hasPages())
        <div class="border-t border-outline-variant/30 px-5 py-4 sm:px-6">
            {{ $riwayat->links() }}
        </div>
    @endif

</x-card>
