@php
    $user = Auth::user();

    $status = $produksi->status->value;

    $pemilik = (int) $produksi->penanggung_jawab_id === (int) $user->id;

    $bisaEdit = $user->hasRole('karyawan')
        && $pemilik
        && $status === 'berjalan';

    // Ambil prediksi AI terbaru.
    $prediksi = $produksi->prediksiAi
        ->sortByDesc('created_at')
        ->first();

    $durasiMasakPrediksi = $prediksi?->waktu_memasak_menit ?? null;

    $durasiNonMasakEstimasi =
        $produksi->durasi_non_masak_estimasi_menit ?? 0;

    $durasiTotalEstimasi = $durasiMasakPrediksi !== null
        ? $durasiMasakPrediksi + $durasiNonMasakEstimasi
        : null;

    $maxFoto = max(
        1,
        (int) config('services.soybean_ai.max_images', 300)
    );
@endphp

<x-app-layout>

    {{-- =========================================================
        HEADER HALAMAN
    ========================================================== --}}
    <x-page-header
        :title="$produksi->kode_batch"
        eyebrow="Laporan produksi"
        :back="route('produksi.index')"
        :subtitle="$produksi->tanggal_jadwal->translatedFormat('l, d F Y')"
    >
        <x-slot:actions>
            <x-status-badge :status="$produksi->status" />
        </x-slot:actions>
    </x-page-header>


    {{-- =========================================================
        INFORMASI UTAMA
    ========================================================== --}}
    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

        {{-- BERAT KEDELAI --}}
        <x-card class="min-w-0">
            <div class="flex items-start gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-tm-md bg-primary-container text-on-primary-container">
                    <x-icon name="grain" class="h-6 w-6" />
                </div>

                <div class="min-w-0">
                    <p class="text-body-sm text-on-surface-variant">
                        Berat kedelai
                    </p>

                    <div class="mt-1 text-title-lg font-bold text-on-surface">
                        <x-qty
                            :value="$produksi->berat_kedelai_kg"
                            unit="kg"
                        />
                    </div>
                </div>
            </div>
        </x-card>


        {{-- KARYAWAN --}}
        <x-card class="min-w-0">
            <div class="flex items-start gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-tm-md bg-secondary-container text-on-secondary-container">
                    <x-icon name="account_circle" class="h-6 w-6" />
                </div>

                <div class="min-w-0">
                    <p class="text-body-sm text-on-surface-variant">
                        Karyawan
                    </p>

                    <p class="mt-1 break-words text-title-md font-bold text-on-surface">
                        {{ $produksi->penanggungJawab?->name ?? '-' }}
                    </p>
                </div>
            </div>
        </x-card>


        {{-- WAKTU MULAI --}}
        <x-card class="min-w-0">
            <div class="flex items-start gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-tm-md bg-tertiary-container text-on-tertiary-container">
                    <x-icon name="schedule" class="h-6 w-6" />
                </div>

                <div class="min-w-0">
                    <p class="text-body-sm text-on-surface-variant">
                        Waktu mulai
                    </p>

                    <p class="mt-1 text-label-lg font-bold text-on-surface">
                        {{ $produksi->waktu_mulai?->translatedFormat('d M Y, H:i') ?? '-' }}
                    </p>
                </div>
            </div>
        </x-card>


        {{-- BIAYA BAHAN --}}
        <x-card class="min-w-0">
            <div class="flex items-start gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-tm-md bg-primary-container text-on-primary-container">
                    <x-icon name="point_of_sale" class="h-6 w-6" />
                </div>

                <div class="min-w-0">
                    <p class="text-body-sm text-on-surface-variant">
                        Biaya bahan
                    </p>

                    <div class="mt-1 text-title-lg font-bold text-on-surface">
                        <x-money :value="$produksi->biaya_bahan" />
                    </div>
                </div>
            </div>
        </x-card>

    </div>


    {{-- =========================================================
        INFORMASI WAKTU PRODUKSI
    ========================================================== --}}
    <x-card
        class="mt-4"
        title="Informasi waktu produksi"
        description="Perbandingan estimasi waktu produksi berdasarkan prediksi AI dan durasi proses aktual."
    >
        <div class="grid gap-4 sm:grid-cols-2">

            {{-- ESTIMASI NON PEMASAKAN --}}
            <div class="rounded-tm-md bg-surface-container-low p-4">
                <div class="flex items-center gap-2">
                    <div class="flex h-9 w-9 items-center justify-center rounded-tm-md bg-secondary-container text-on-secondary-container">
                        <x-icon name="schedule" class="h-5 w-5" />
                    </div>

                    <p class="text-body-sm text-on-surface-variant">
                        Estimasi proses selain pemasakan
                    </p>
                </div>

                <p class="mt-3 text-title-lg font-bold text-on-surface">
                    {{ \App\Models\PrediksiAi::label(
                        $produksi->durasi_non_masak_estimasi_menit
                    ) }}
                </p>
            </div>


            {{-- ESTIMASI PEMASAKAN --}}
            <div class="rounded-tm-md bg-surface-container-low p-4">
                <div class="flex items-center gap-2">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-tm-md bg-primary-container text-on-primary-container">
                        <x-icon name="schedule" class="h-6 w-6" />
                    </div>

                    <p class="text-body-sm text-on-surface-variant">
                        Estimasi pemasakan dari AI
                    </p>
                </div>

                <p class="mt-3 text-title-lg font-bold text-on-surface">
                    {{ $prediksi
                        ? \App\Models\PrediksiAi::label($prediksi->waktu_memasak_menit)
                        : 'Belum diprediksi' }}
                </p>
            </div>


            {{-- TOTAL ESTIMASI --}}
            <div class="rounded-tm-md border border-primary/20 bg-primary-container/40 p-4 sm:col-span-2">
                <div class="flex items-center gap-2">
                    <div class="flex h-10 w-10 items-center justify-center rounded-tm-md bg-primary text-on-primary">
                        <x-icon name="schedule" class="h-5 w-5" />
                    </div>

                    <div>
                        <p class="text-body-sm text-on-surface-variant">
                            Estimasi total produksi
                        </p>

                        <p class="text-title-xl font-bold text-on-surface">
                            {{ $durasiTotalEstimasi !== null
                                ? \App\Models\PrediksiAi::label($durasiTotalEstimasi)
                                : 'Menunggu prediksi AI' }}
                        </p>
                    </div>
                </div>

                <p class="mt-3 text-body-sm text-on-surface-variant">
                    Estimasi total merupakan penjumlahan prediksi durasi pemasakan
                    AI dan estimasi proses selain pemasakan.
                </p>
            </div>


            {{-- TOTAL AKTUAL --}}
            @if ($produksi->durasi_total_aktual_menit !== null)
                <div class="rounded-tm-md border border-tertiary/20 bg-tertiary-fixed p-4 sm:col-span-2">
                    <div class="flex items-center gap-2">
                        <div class="flex h-10 w-10 items-center justify-center rounded-tm-md bg-tertiary text-on-tertiary">
                            <x-icon name="check_circle" class="h-5 w-5" />
                        </div>

                        <div>
                            <p class="text-body-sm text-on-tertiary-fixed-variant">
                                Total durasi aktual
                            </p>

                            <p class="text-title-xl font-bold text-on-tertiary-fixed">
                                {{ \App\Models\PrediksiAi::label(
                                    $produksi->durasi_total_aktual_menit
                                ) }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <p class="text-xs text-on-tertiary-fixed-variant">
                                Durasi pemasakan
                            </p>

                            <p class="font-semibold text-on-tertiary-fixed">
                                {{ \App\Models\PrediksiAi::label(
                                    $produksi->durasi_masak_aktual_menit
                                ) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-on-tertiary-fixed-variant">
                                Durasi selain pemasakan
                            </p>

                            <p class="font-semibold text-on-tertiary-fixed">
                                {{ \App\Models\PrediksiAi::label(
                                    $produksi->durasi_non_masak_aktual_menit
                                ) }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

        </div>

        @if ($produksi->waktu_selesai)
            <div class="mt-4 flex items-center gap-2 text-body-sm text-on-surface-variant">
                <x-icon name="calendar_today" class="h-4 w-4" />

                <span>
                    Selesai pada
                    {{ $produksi->waktu_selesai->translatedFormat('d M Y, H:i') }}
                </span>
            </div>
        @endif
    </x-card>


    {{-- =========================================================
        BAHAN BAKU
    ========================================================== --}}
    <x-card
        class="mt-4"
        title="Bahan baku yang digunakan"
        description="Daftar bahan baku yang digunakan pada batch produksi ini."
    >
        @if ($produksi->bahanBaku->isEmpty())
            <div class="rounded-tm-md bg-surface-container-low p-6 text-center">
                <x-icon name="inventory_2" class="mx-auto h-10 w-10 text-on-surface-variant" />
                <p class="mt-2 font-semibold text-on-surface-variant">Tidak ada bahan baku tercatat.</p>
            </div>
        @else
            <div class="overflow-x-auto rounded-tm-md border border-outline-variant">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-outline-variant bg-surface-container-low text-left">
                            <th class="px-4 py-3 font-semibold text-on-surface-variant">#</th>
                            <th class="px-4 py-3 font-semibold text-on-surface-variant">Nama Bahan</th>
                            <th class="px-4 py-3 font-semibold text-on-surface-variant">Kategori</th>
                            <th class="px-4 py-3 text-right font-semibold text-on-surface-variant">Jumlah Dipakai</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant">
                        @foreach ($produksi->bahanBaku as $i => $bahan)
                            <tr class="bg-surface transition hover:bg-surface-container-low">
                                <td class="px-4 py-3 text-on-surface-variant">{{ $i + 1 }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-tm-md bg-secondary-container text-on-secondary-container">
                                            <x-icon name="inventory_2" class="h-4 w-4" />
                                        </div>
                                        <span class="font-semibold text-on-surface">{{ $bahan->nama }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full bg-surface-container-high px-3 py-1 text-xs font-medium text-on-surface-variant">
                                        {{ ucfirst($bahan->kategori) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-on-surface">
                                    {{ number_format((float) $bahan->pivot->jumlah_dipakai, 2, ',', '.') }}
                                    <span class="font-normal text-on-surface-variant">{{ $bahan->satuan }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>


    {{-- =========================================================
        PREDIKSI AI
    ========================================================== --}}
    <x-card
        class="mt-4"
        title="Prediksi AI kualitas kedelai"
        description="Ambil foto kedelai menggunakan kamera atau pilih foto dari galeri. AI akan menganalisis kualitas kedelai dan memprediksi hasil tahu."
    >

        {{-- FORM UPLOAD FOTO --}}
        @if ($bisaEdit)

            <form
                method="POST"
                enctype="multipart/form-data"
                action="{{ route('produksi.prediksi-ai', $produksi) }}"
                class="space-y-4"
                x-data="laporanKamera()"
                @submit="periksaSebelumKirim($event)"
                x-on:submit="if (files.length > 0) loading = true"
                x-on:pageshow.window="loading = false"
            >
                @csrf

                <div class="rounded-tm-md border border-outline-variant bg-surface-container-low p-4">

                    <div class="mb-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-tm-md bg-primary-container text-on-primary-container">
                                <x-icon name="sensors" class="h-5 w-5" />
                            </div>

                            <div>
                                <h3 class="font-semibold text-on-surface">
                                    Foto biji kedelai
                                </h3>

                                <p class="text-xs text-on-surface-variant">
                                    Kamera langsung atau galeri perangkat.
                                </p>
                            </div>
                        </div>
                    </div>


                    {{-- TOMBOL KAMERA DAN GALERI --}}
                    <div class="grid gap-3 sm:grid-cols-2">

                        <button
                            type="button"
                            @click="bukaKamera()"
                            class="flex flex-col items-center justify-center gap-2 rounded-tm-md border-2 border-dashed border-primary bg-primary-container/30 p-5 text-center transition hover:bg-primary-container/60"
                        >
                            <span class="flex h-12 w-12 items-center justify-center rounded-full bg-primary text-on-primary">
                                <x-icon name="sensors" class="h-6 w-6" />
                            </span>

                            <span class="font-semibold text-on-surface">
                                Ambil Foto dari Kamera
                            </span>

                            <span class="text-xs text-on-surface-variant">
                                Gunakan kamera belakang HP
                            </span>
                        </button>


                        <button
                            type="button"
                            @click="$refs.galleryInput.click()"
                            class="flex flex-col items-center justify-center gap-2 rounded-tm-md border-2 border-dashed border-outline-variant bg-surface-container-lowest p-5 text-center transition hover:bg-surface-container-high"
                        >
                            <span class="flex h-12 w-12 items-center justify-center rounded-full bg-secondary-container text-on-secondary-container">
                                <x-icon name="inventory_2" class="h-6 w-6" />
                            </span>

                            <span class="font-semibold text-on-surface">
                                Pilih dari Galeri
                            </span>

                            <span class="text-xs text-on-surface-variant">
                                Gunakan foto yang sudah tersedia
                            </span>
                        </button>

                    </div>


                    {{-- INPUT GALERI --}}
                    <input
                        type="file"
                        x-ref="galleryInput"
                        accept="image/jpeg,image/png,image/webp"
                        multiple
                        class="hidden"
                        @change="tambahDariGaleri($event)"
                    >


                    {{-- INPUT UTAMA UNTUK LARAVEL --}}
                    <input
                        type="file"
                        name="images[]"
                        x-ref="uploadInput"
                        accept="image/jpeg,image/png,image/webp"
                        multiple
                        class="hidden"
                    >


                    {{-- PREVIEW KAMERA --}}
                    <div
                        x-show="kameraAktif"
                        x-cloak
                        class="mt-4 rounded-tm-md border border-outline-variant bg-surface-container-low p-3"
                    >

                        <div class="mb-3 flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <div class="flex h-9 w-9 items-center justify-center rounded-tm-md bg-primary-container text-on-primary-container">
                                    <x-icon name="sensors" class="h-5 w-5" />
                                </div>

                                <h3 class="font-semibold text-on-surface">
                                    Kamera langsung
                                </h3>
                            </div>

                            <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800">
                                Kamera aktif
                            </span>
                        </div>


                        <video
                            x-ref="video"
                            autoplay
                            playsinline
                            muted
                            class="mx-auto max-h-[450px] w-full rounded-tm-md bg-black object-contain"
                        ></video>


                        <div class="mt-3 grid grid-cols-2 gap-2">

                            <button
                                type="button"
                                @click="ambilFoto()"
                                :disabled="jumlahFoto >= {{ $maxFoto }}"
                                class="flex items-center justify-center gap-2 rounded-tm-md bg-primary px-4 py-3 font-semibold text-on-primary disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <x-icon name="sensors" class="h-5 w-5" />
                                Ambil Foto
                            </button>

                            <button
                                type="button"
                                @click="tutupKamera()"
                                class="flex items-center justify-center gap-2 rounded-tm-md bg-error px-4 py-3 font-semibold text-white"
                            >
                                <x-icon name="close" class="h-5 w-5" />
                                Tutup Kamera
                            </button>

                        </div>

                        <p class="mt-2 text-xs text-on-surface-variant">
                            Posisikan biji kedelai di depan kamera, lalu tekan
                            tombol Ambil Foto.
                        </p>

                    </div>


                    {{-- PESAN ERROR KAMERA --}}
                    <div
                        x-show="pesanError"
                        x-cloak
                        class="mt-3 flex items-start gap-2 rounded-tm-md bg-error-container p-3 text-sm text-on-error-container"
                    >
                        <x-icon name="error" class="mt-0.5 h-5 w-5 shrink-0" />

                        <span x-text="pesanError"></span>
                    </div>


                    {{-- DAFTAR FOTO --}}
                    <div class="mt-4 rounded-tm-md bg-surface-container-high p-3">

                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <x-icon name="inventory_2" class="h-5 w-5 text-primary" />

                                <span class="text-sm font-semibold text-on-surface">
                                    Foto dipilih
                                </span>
                            </div>

                            <span
                                class="rounded-full bg-primary-container px-3 py-1 text-xs font-semibold text-on-primary-container"
                                x-text="jumlahFoto + ' / {{ $maxFoto }} foto'"
                            ></span>
                        </div>


                        <template x-if="files.length > 0">
                            <div class="mt-3 space-y-2">

                                <template
                                    x-for="(file, index) in files"
                                    :key="file.id"
                                >
                                    <div class="flex items-center justify-between gap-2 rounded-tm-md bg-surface-container-lowest p-3">

                                        <div class="flex min-w-0 items-center gap-2">
                                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-tm-md bg-secondary-container text-on-secondary-container">
                                                <x-icon name="inventory_2" class="h-5 w-5" />
                                            </div>

                                            <div class="min-w-0">
                                                <p
                                                    class="truncate text-sm font-medium text-on-surface"
                                                    x-text="file.name"
                                                ></p>

                                                <p
                                                    class="text-xs text-on-surface-variant"
                                                    x-text="ukuranFile(file.size)"
                                                ></p>
                                            </div>
                                        </div>


                                        <button
                                            type="button"
                                            @click="hapusFoto(index)"
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-error transition hover:bg-error-container"
                                            title="Hapus foto"
                                        >
                                            <x-icon name="delete" class="h-4 w-4" />
                                        </button>

                                    </div>
                                </template>

                            </div>
                        </template>


                        <div
                            x-show="files.length === 0"
                            class="mt-3 rounded-tm-md border border-dashed border-outline-variant p-5 text-center"
                        >
                            <x-icon name="add_box" class="mx-auto h-8 w-8 text-on-surface-variant" />

                            <p class="mt-2 text-sm text-on-surface-variant">
                                Belum ada foto yang dipilih.
                            </p>
                        </div>

                    </div>


                    <p class="mt-3 text-xs text-on-surface-variant">
                        Maksimal {{ $maxFoto }} foto. Format JPG, JPEG, PNG,
                        atau WEBP. Maksimal 10 MB per foto.
                    </p>

                </div>


                {{-- ERROR VALIDASI --}}
                @error('images')
                    <p class="text-sm text-error">{{ $message }}</p>
                @enderror

                @error('images.*')
                    <p class="text-sm text-error">{{ $message }}</p>
                @enderror


                <x-submit-button
                    icon="auto_awesome"
                    loading-text="Memproses gambar..."
                >
                    Jalankan Prediksi AI
                </x-submit-button>

            </form>

        @endif


        {{-- HASIL PREDIKSI TERBARU --}}
        @if ($prediksi)

            <div class="mt-5 border-t border-outline-variant pt-5">

                <div class="mb-4 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-tm-md bg-primary-container text-on-primary-container">
                        <x-icon name="schedule" class="h-5 w-5" />
                    </div>

                    <div>
                        <h3 class="font-bold text-on-surface">
                            Prediksi AI terbaru
                        </h3>

                        <p class="text-xs text-on-surface-variant">
                            Hasil analisis kualitas kedelai.
                        </p>
                    </div>
                </div>


                {{-- RINGKASAN STATISTIK PREDIKSI --}}
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">

                    {{-- GOOD --}}
                    <div class="rounded-tm-md border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-950/30">
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full bg-green-600"></span>
                            <p class="text-body-sm text-on-surface-variant">Kedelai Good</p>
                        </div>
                        <p class="mt-2 text-title-xl font-bold text-green-700 dark:text-green-400">
                            {{ number_format($prediksi->good_percent, 2) }}%
                        </p>
                        <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-green-200 dark:bg-green-900">
                            <div class="h-full rounded-full bg-green-500" style="width: {{ $prediksi->good_percent }}%"></div>
                        </div>
                    </div>

                    {{-- BAD --}}
                    <div class="rounded-tm-md border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-950/30">
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full bg-red-500"></span>
                            <p class="text-body-sm text-on-surface-variant">Kedelai Bad</p>
                        </div>
                        <p class="mt-2 text-title-xl font-bold text-red-600 dark:text-red-400">
                            {{ number_format($prediksi->bad_percent, 2) }}%
                        </p>
                        <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-red-200 dark:bg-red-900">
                            <div class="h-full rounded-full bg-red-500" style="width: {{ $prediksi->bad_percent }}%"></div>
                        </div>
                    </div>

                    {{-- HASIL TAHU --}}
                    <div class="rounded-tm-md border border-primary/20 bg-primary-container/30 p-4">
                        <div class="flex items-center gap-2">
                            <x-icon name="check_circle" class="h-5 w-5 text-primary" />
                            <p class="text-body-sm text-on-surface-variant">Prediksi hasil tahu</p>
                        </div>
                        <p class="mt-2 text-title-xl font-bold text-on-surface">
                            {{ number_format($prediksi->hasil_tahu_kg, 2) }}
                            <span class="text-title-md font-normal text-on-surface-variant">kg</span>
                        </p>
                    </div>

                    {{-- WAKTU PEMASAKAN --}}
                    <div class="rounded-tm-md border border-secondary/20 bg-secondary-container/30 p-4">
                        <div class="flex items-center gap-2">
                            <x-icon name="schedule" class="h-5 w-5 text-secondary" />
                            <p class="text-body-sm text-on-surface-variant">Prediksi pemasakan</p>
                        </div>
                        <p class="mt-2 text-title-xl font-bold text-on-surface">
                            {{ \App\Models\PrediksiAi::label($prediksi->waktu_memasak_menit) }}
                        </p>
                    </div>

                </div>

                {{-- DETAIL TABEL PREDIKSI --}}
                <div class="mt-4 overflow-x-auto rounded-tm-md border border-outline-variant">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-outline-variant bg-surface-container-low text-left">
                                <th class="px-4 py-3 font-semibold text-on-surface-variant">Parameter</th>
                                <th class="px-4 py-3 text-right font-semibold text-on-surface-variant">Nilai</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant">
                            <tr class="bg-surface">
                                <td class="px-4 py-3 text-on-surface-variant">Jumlah gambar dianalisis</td>
                                <td class="px-4 py-3 text-right font-semibold text-on-surface">{{ $prediksi->jumlah_gambar }} foto</td>
                            </tr>
                            <tr class="bg-surface-container-low/50">
                                <td class="px-4 py-3 text-on-surface-variant">Persentase kedelai baik (Good)</td>
                                <td class="px-4 py-3 text-right font-semibold text-green-600">{{ number_format($prediksi->good_percent, 2) }}%</td>
                            </tr>
                            <tr class="bg-surface">
                                <td class="px-4 py-3 text-on-surface-variant">Persentase kedelai buruk (Bad)</td>
                                <td class="px-4 py-3 text-right font-semibold text-red-600">{{ number_format($prediksi->bad_percent, 2) }}%</td>
                            </tr>
                            <tr class="bg-surface-container-low/50">
                                <td class="px-4 py-3 text-on-surface-variant">Prediksi hasil tahu</td>
                                <td class="px-4 py-3 text-right font-semibold text-on-surface">{{ number_format($prediksi->hasil_tahu_kg, 2) }} kg</td>
                            </tr>
                            <tr class="bg-surface">
                                <td class="px-4 py-3 text-on-surface-variant">Prediksi durasi pemasakan</td>
                                <td class="px-4 py-3 text-right font-semibold text-on-surface">{{ \App\Models\PrediksiAi::label($prediksi->waktu_memasak_menit) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>


                <div class="mt-4 flex flex-wrap items-center gap-2 text-body-sm text-on-surface-variant">
                    <x-icon name="schedule" class="h-4 w-4" />

                    <span>
                        Diproses
                        {{ $prediksi->created_at->translatedFormat('d M Y, H:i') }}
                        oleh {{ $prediksi->pengguna?->name ?? '-' }}.
                    </span>
                </div>

            </div>

        @else

            <div class="mt-4 rounded-tm-md border border-dashed border-outline-variant bg-surface-container-low p-5 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-secondary-container text-on-secondary-container">
                    <x-icon name="category" class="h-6 w-6" />
                </div>

                <p class="mt-3 font-semibold text-on-surface">
                    Belum ada prediksi AI
                </p>

                <p class="mt-1 text-sm text-on-surface-variant">
                    Upload foto kedelai untuk mendapatkan hasil analisis AI.
                </p>
            </div>

        @endif


        {{-- RIWAYAT PREDIKSI --}}
        @if ($produksi->prediksiAi->count() > 1)

            <div class="mt-5 border-t border-outline-variant pt-5">

                <div class="mb-3 flex items-center gap-2">
                    <div class="flex h-9 w-9 items-center justify-center rounded-tm-md bg-secondary-container text-on-secondary-container">
                        <x-icon name="schedule" class="h-5 w-5" />
                    </div>

                    <h3 class="font-bold text-on-surface">
                        Riwayat prediksi
                    </h3>
                </div>

                <div class="overflow-x-auto rounded-tm-md border border-outline-variant">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-outline-variant bg-surface-container-low text-left">
                                <th class="px-4 py-3 font-semibold text-on-surface-variant">Waktu</th>
                                <th class="px-4 py-3 text-center font-semibold text-on-surface-variant">Foto</th>
                                <th class="px-4 py-3 text-center font-semibold text-on-surface-variant">Good</th>
                                <th class="px-4 py-3 text-center font-semibold text-on-surface-variant">Bad</th>
                                <th class="px-4 py-3 text-right font-semibold text-on-surface-variant">Hasil Tahu</th>
                                <th class="px-4 py-3 text-right font-semibold text-on-surface-variant">Durasi Masak</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant">
                            @foreach ($produksi->prediksiAi->sortByDesc('created_at') as $loop_i => $riwayat)
                                <tr class="{{ $loop_i === 0 ? 'bg-primary-container/20 font-medium' : 'bg-surface hover:bg-surface-container-low' }} transition">
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-on-surface">
                                            {{ $riwayat->created_at->translatedFormat('d M Y') }}
                                        </p>
                                        <p class="text-xs text-on-surface-variant">{{ $riwayat->created_at->format('H:i') }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-center text-on-surface">{{ $riwayat->jumlah_gambar }}</td>
                                    <td class="px-4 py-3 text-center font-semibold text-green-600">
                                        {{ number_format($riwayat->good_percent, 1) }}%
                                    </td>
                                    <td class="px-4 py-3 text-center font-semibold text-red-600">
                                        {{ number_format($riwayat->bad_percent, 1) }}%
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-on-surface">
                                        {{ number_format($riwayat->hasil_tahu_kg, 2) }}
                                        <span class="font-normal text-on-surface-variant">kg</span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-on-surface">
                                        {{ \App\Models\PrediksiAi::label($riwayat->waktu_memasak_menit) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </div>

        @endif

    </x-card>


    {{-- =========================================================
        PENYELESAIAN LAPORAN
    ========================================================== --}}
    @if ($bisaEdit)

        <x-card
            class="mt-4"
            title="Penyelesaian laporan produksi"
            description="Isi data aktual setelah seluruh proses produksi selesai."
        >

            @if (! $prediksi)
                <div class="mb-4 flex items-start gap-3 rounded-tm-md bg-secondary-fixed p-3 text-body-sm text-on-secondary-fixed-variant">
                    <x-icon name="info" class="mt-0.5 h-5 w-5 shrink-0" />

                    <p>
                        Jalankan prediksi AI terlebih dahulu sebelum menyelesaikan laporan.
                    </p>
                </div>
            @endif


            <form
                method="POST"
                action="{{ route('produksi.selesaikan', $produksi) }}"
                class="space-y-5"
                onsubmit="return confirm('Selesaikan laporan batch ini? Data yang disimpan tidak dapat diubah.')"
            >
                @csrf


                {{-- DURASI AKTUAL --}}
                <div>
                    <div class="mb-3 flex items-center gap-2">
                        <div class="flex h-9 w-9 items-center justify-center rounded-tm-md bg-primary-container text-on-primary-container">
                            <x-icon name="schedule" class="h-5 w-5" />
                        </div>

                        <div>
                            <h3 class="font-semibold text-on-surface">
                                Durasi aktual produksi
                            </h3>

                            <p class="text-xs text-on-surface-variant">
                                Masukkan waktu berdasarkan proses sebenarnya.
                            </p>
                        </div>
                    </div>


                    <div class="grid gap-4 sm:grid-cols-2">

                        <x-field
                            name="durasi_masak_aktual_menit"
                            label="Durasi pemasakan aktual (menit)"
                        >
                            <x-text-input
                                id="durasi_masak_aktual_menit"
                                name="durasi_masak_aktual_menit"
                                type="number"
                                min="1"
                                max="1440"
                                required
                                :value="old('durasi_masak_aktual_menit')"
                                placeholder="Contoh: 180"
                            />
                        </x-field>


                        <x-field
                            name="durasi_non_masak_aktual_menit"
                            label="Durasi proses selain pemasakan (menit)"
                        >
                            <x-text-input
                                id="durasi_non_masak_aktual_menit"
                                name="durasi_non_masak_aktual_menit"
                                type="number"
                                min="0"
                                max="10080"
                                required
                                :value="old('durasi_non_masak_aktual_menit')"
                                placeholder="Contoh: 120"
                            />
                        </x-field>

                    </div>
                </div>


                {{-- HASIL TAHU AKTUAL --}}
                <div class="border-t border-outline-variant pt-5">

                    <div class="mb-4 flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-tm-md bg-secondary-container text-on-secondary-container">
                            <x-icon name="check_circle" class="h-5 w-5" />
                        </div>

                        <div>
                            <h3 class="font-semibold text-on-surface">
                                Hasil tahu aktual
                            </h3>

                            <p class="text-xs text-on-surface-variant">
                                Masukkan jumlah tahu baik dan reject sesuai hasil penimbangan.
                            </p>
                        </div>
                    </div>


                    <div class="mb-4 rounded-tm-md bg-primary-container/40 p-3">
                        <p class="text-sm font-medium text-on-surface">
                            Ketentuan pengisian hasil:
                        </p>

                        <ul class="mt-2 space-y-1 text-xs text-on-surface-variant">
                            <li>• Jumlah tahu baik wajib lebih dari 0.</li>
                            <li>• Jumlah tahu reject wajib diisi, tetapi boleh 0.</li>
                            <li>• Hanya jumlah tahu baik yang ditambahkan ke stok produk.</li>
                        </ul>
                    </div>


                    <div class="space-y-3">

                        @foreach ($produkAktif as $i => $produk)

                            <div class="rounded-tm-md border border-outline-variant bg-surface-container-low p-4">

                                <input
                                    type="hidden"
                                    name="hasil[{{ $i }}][produk_id]"
                                    value="{{ $produk->id }}"
                                />


                                <div class="mb-4 flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-tm-md bg-secondary-container text-on-secondary-container">
                                        <x-icon name="inventory_2" class="h-5 w-5" />
                                    </div>

                                    <div>
                                        <p class="font-semibold text-on-surface">
                                            {{ $produk->nama }}
                                        </p>

                                        <p class="text-xs text-on-surface-variant">
                                            Satuan: {{ $produk->satuan }}
                                        </p>
                                    </div>
                                </div>


                                <div class="grid gap-3 sm:grid-cols-2">

                                    {{-- TAHU BAIK --}}
                                    <x-field
                                        name="hasil_{{ $i }}_baik"
                                        label="Jumlah tahu baik ({{ $produk->satuan }})"
                                    >
                                        <x-text-input
                                            id="hasil_{{ $i }}_baik"
                                            name="hasil[{{ $i }}][jumlah_baik]"
                                            type="number"
                                            min="0.01"
                                            step="0.01"
                                            required
                                            placeholder="Contoh: 10"
                                            :value="old('hasil.' . $i . '.jumlah_baik')"
                                        />
                                    </x-field>


                                    {{-- TAHU REJECT --}}
                                    <x-field
                                        name="hasil_{{ $i }}_reject"
                                        label="Jumlah tahu reject ({{ $produk->satuan }})"
                                    >
                                        <x-text-input
                                            id="hasil_{{ $i }}_reject"
                                            name="hasil[{{ $i }}][jumlah_reject]"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            required
                                            placeholder="Contoh: 0"
                                            :value="old('hasil.' . $i . '.jumlah_reject', 0)"
                                        />
                                    </x-field>

                                </div>

                            </div>

                        @endforeach

                    </div>

                </div>


                {{-- CATATAN AKHIR --}}
                <div class="border-t border-outline-variant pt-5">

                    <div class="mb-3 flex items-center gap-2">
                        <div class="flex h-9 w-9 items-center justify-center rounded-tm-md bg-secondary-container text-on-secondary-container">
                            <x-icon name="fact_check" class="h-5 w-5" />
                        </div>

                        <h3 class="font-semibold text-on-surface">
                            Catatan akhir
                        </h3>
                    </div>


                    <x-field
                        name="catatan_aktual"
                        label="Catatan tambahan (opsional)"
                    >
                        <x-textarea-input
                            id="catatan_aktual"
                            name="catatan_aktual"
                            rows="3"
                            placeholder="Catatan kondisi produksi, kendala, atau informasi lainnya..."
                        >{{ old('catatan_aktual') }}</x-textarea-input>
                    </x-field>

                </div>


                {{-- TOMBOL SELESAIKAN --}}
                <div class="border-t border-outline-variant pt-4">

                    <x-submit-button
                        icon="check_circle"
                        loading-text="Menyimpan laporan..."
                        :disabled="! $prediksi || $produkAktif->isEmpty()"
                    >
                        Simpan dan selesaikan batch
                    </x-submit-button>

                    @if (! $produkAktif->isEmpty() && $prediksi)
                        <p class="mt-2 text-xs text-on-surface-variant">
                            Pastikan seluruh hasil penimbangan sudah benar sebelum menyelesaikan batch.
                        </p>
                    @elseif ($produkAktif->isEmpty())
                        <p class="mt-2 text-xs text-error">
                            Belum ada produk aktif. Hubungi admin untuk menambahkan produk.
                        </p>
                    @endif

                </div>

            </form>

        </x-card>


        {{-- =====================================================
            PEMBATALAN BATCH
        ====================================================== --}}
        <x-card
            class="mt-4"
            title="Batalkan batch"
            description="Pembatalan hanya dapat dilakukan selama batch masih berjalan."
        >

            <div class="mb-4 flex items-start gap-3 rounded-tm-md bg-error-container/50 p-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-tm-md bg-error-container text-on-error-container">
                    <x-icon name="warning" class="h-5 w-5" />
                </div>

                <p class="text-sm text-on-error-container">
                    Jika batch dibatalkan, stok bahan baku yang telah digunakan
                    akan dikembalikan. Riwayat pembatalan tetap disimpan.
                </p>
            </div>


            <form
                method="POST"
                action="{{ route('produksi.batal-laporan', $produksi) }}"
                onsubmit="return confirm('Yakin membatalkan batch? Stok bahan baku akan dikembalikan.')"
                class="space-y-3"
            >
                @csrf

                <x-field
                    name="alasan_batal"
                    label="Alasan pembatalan"
                >
                    <x-textarea-input
                        id="alasan_batal"
                        name="alasan_batal"
                        rows="3"
                        required
                        placeholder="Tuliskan alasan pembatalan..."
                    ></x-textarea-input>
                </x-field>


                <x-btn
                    type="submit"
                    color="danger"
                    variant="secondary"
                    icon="cancel"
                >
                    Batalkan batch
                </x-btn>

            </form>

        </x-card>

    @endif


    {{-- =========================================================
        HASIL PRODUKSI TERSIMPAN
    ========================================================== --}}
    @if ($produksi->hasil->isNotEmpty())

        <x-card
            class="mt-4"
            title="Hasil produksi tersimpan"
            description="Hasil penimbangan aktual yang telah dicatat pada batch ini."
        >

            <div class="overflow-x-auto rounded-tm-md border border-outline-variant">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-outline-variant bg-surface-container-low text-left">
                            <th class="px-4 py-3 font-semibold text-on-surface-variant">Produk</th>
                            <th class="px-4 py-3 font-semibold text-on-surface-variant">Dicatat Oleh</th>
                            <th class="px-4 py-3 text-right font-semibold text-green-700">Tahu Baik</th>
                            <th class="px-4 py-3 text-right font-semibold text-red-600">Tahu Reject</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant">
                        @foreach ($produksi->hasil as $i => $hasil)
                            <tr class="{{ $i % 2 === 0 ? 'bg-surface' : 'bg-surface-container-low/40' }}">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-tm-md bg-secondary-container text-on-secondary-container">
                                            <x-icon name="inventory_2" class="h-4 w-4" />
                                        </div>
                                        <div>
                                            <p class="font-semibold text-on-surface">
                                                {{ $hasil->produk?->nama ?? 'Produk tidak ditemukan' }}
                                            </p>
                                            <p class="text-xs text-on-surface-variant">{{ $hasil->produk?->satuan }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-on-surface-variant">
                                    {{ $hasil->pencatat?->name ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="font-bold text-green-700">
                                        {{ number_format((float) $hasil->jumlah_baik, 2, ',', '.') }}
                                    </span>
                                    <span class="text-on-surface-variant"> {{ $hasil->produk?->satuan }}</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="font-bold text-red-600">
                                        {{ number_format((float) $hasil->jumlah_reject, 2, ',', '.') }}
                                    </span>
                                    <span class="text-on-surface-variant"> {{ $hasil->produk?->satuan }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-outline-variant bg-surface-container-low">
                            <td colspan="2" class="px-4 py-3 font-semibold text-on-surface-variant">Total</td>
                            <td class="px-4 py-3 text-right font-bold text-green-700">
                                {{ number_format($produksi->hasil->sum('jumlah_baik'), 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-red-600">
                                {{ number_format($produksi->hasil->sum('jumlah_reject'), 2, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

        </x-card>

    @endif


    {{-- =========================================================
        CATATAN AWAL
    ========================================================== --}}
    @if ($produksi->catatan)

        <x-card
            class="mt-4"
            title="Catatan awal"
        >
            <div class="flex items-start gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-tm-md bg-secondary-container text-on-secondary-container">
                    <x-icon name="receipt_long" class="h-5 w-5" />
                </div>

                <p class="whitespace-pre-line text-body-md text-on-surface">
                    {{ $produksi->catatan }}
                </p>
            </div>
        </x-card>

    @endif


    {{-- =========================================================
        CATATAN AKHIR
    ========================================================== --}}
    @if ($produksi->catatan_aktual)

        <x-card
            class="mt-4"
            title="Catatan akhir"
        >
            <div class="flex items-start gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-tm-md bg-secondary-container text-on-secondary-container">
                    <x-icon name="fact_check" class="h-5 w-5" />
                </div>

                <p class="whitespace-pre-line text-body-md text-on-surface">
                    {{ $produksi->catatan_aktual }}
                </p>
            </div>
        </x-card>

    @endif


    {{-- =========================================================
        INFORMASI PEMBATALAN
    ========================================================== --}}
    @if ($status === 'batal')

        <x-card
            class="mt-4"
            title="Informasi pembatalan"
            description="Informasi batch yang telah dibatalkan."
        >

            <div class="rounded-tm-md bg-error-container/50 p-4">

                <div class="flex items-center gap-2">
                    <x-icon name="error" class="h-5 w-5 text-on-error-container" />

                    <p class="font-semibold text-on-error-container">
                        Batch dibatalkan
                    </p>
                </div>

                <p class="mt-2 whitespace-pre-line text-sm text-on-error-container">
                    {{ $produksi->alasan_batal ?? 'Tidak ada alasan yang dicatat.' }}
                </p>

            </div>


            @if ($produksi->stok_dikembalikan_at)

                <div class="mt-3 flex items-center gap-3 rounded-tm-md bg-surface-container-low p-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-tm-md bg-secondary-container text-on-secondary-container">
                        <x-icon name="check_circle" class="h-5 w-5" />
                    </div>

                    <div>
                        <p class="text-xs text-on-surface-variant">
                            Stok bahan baku dikembalikan
                        </p>

                        <p class="font-semibold text-on-surface">
                            {{ $produksi->stok_dikembalikan_at->translatedFormat('d M Y, H:i') }}
                        </p>
                    </div>
                </div>

            @endif

        </x-card>

    @endif

</x-app-layout>


{{-- =============================================================
    JAVASCRIPT KAMERA
============================================================= --}}
<script>
    function laporanKamera() {
        return {
            stream: null,
            kameraAktif: false,
            files: [],
            pesanError: '',
            jumlahFoto: 0,
            loading: false,

            // Membuka kamera perangkat.
            async bukaKamera() {
                this.pesanError = '';

                if (
                    !navigator.mediaDevices ||
                    !navigator.mediaDevices.getUserMedia
                ) {
                    this.pesanError =
                        'Browser tidak mendukung akses kamera. Gunakan HTTPS atau localhost dan browser terbaru.';
                    return;
                }

                if (this.jumlahFoto >= {{ $maxFoto }}) {
                    this.pesanError =
                        'Jumlah foto sudah mencapai batas maksimum.';
                    return;
                }

                try {
                    this.stream = await navigator.mediaDevices.getUserMedia({
                        audio: false,
                        video: {
                            facingMode: {
                                ideal: 'environment'
                            },
                            width: {
                                ideal: 1280
                            },
                            height: {
                                ideal: 720
                            }
                        }
                    });

                    this.kameraAktif = true;

                    await this.$nextTick();

                    this.$refs.video.srcObject = this.stream;

                    await this.$refs.video.play();

                } catch (error) {
                    console.error('Kesalahan kamera:', error);

                    if (
                        error.name === 'NotAllowedError' ||
                        error.name === 'PermissionDeniedError'
                    ) {
                        this.pesanError =
                            'Akses kamera ditolak. Izinkan penggunaan kamera pada pengaturan browser, lalu coba lagi.';

                    } else if (error.name === 'NotFoundError') {
                        this.pesanError =
                            'Kamera tidak ditemukan pada perangkat ini.';

                    } else if (error.name === 'NotReadableError') {
                        this.pesanError =
                            'Kamera sedang digunakan aplikasi lain. Tutup aplikasi tersebut lalu coba kembali.';

                    } else {
                        this.pesanError =
                            'Kamera gagal dibuka: ' + error.message;
                    }

                    this.tutupKamera();
                }
            },


            // Mengambil foto dari kamera.
            async ambilFoto() {
                const video = this.$refs.video;

                if (!video || !video.videoWidth) {
                    this.pesanError =
                        'Kamera belum siap. Tunggu beberapa saat.';
                    return;
                }

                if (this.jumlahFoto >= {{ $maxFoto }}) {
                    this.pesanError =
                        'Jumlah foto sudah mencapai batas maksimum.';
                    return;
                }

                const canvas = document.createElement('canvas');

                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;

                const context = canvas.getContext('2d');

                context.drawImage(
                    video,
                    0,
                    0,
                    canvas.width,
                    canvas.height
                );

                const blob = await new Promise(resolve => {
                    canvas.toBlob(
                        resolve,
                        'image/jpeg',
                        0.90
                    );
                });

                if (!blob) {
                    this.pesanError =
                        'Gagal mengambil foto dari kamera.';
                    return;
                }

                const file = new File(
                    [blob],
                    'foto-kedelai-' + Date.now() + '.jpg',
                    {
                        type: 'image/jpeg',
                        lastModified: Date.now()
                    }
                );

                this.files.push({
                    id: Date.now() + Math.random(),
                    file: file,
                    name: file.name,
                    size: file.size
                });

                this.perbaruiInput();
                this.pesanError = '';
            },


            // Menambahkan foto dari galeri.
            tambahDariGaleri(event) {
                const selectedFiles = Array.from(
                    event.target.files || []
                );

                this.pesanError = '';

                for (const file of selectedFiles) {
                    if (this.jumlahFoto >= {{ $maxFoto }}) {
                        this.pesanError =
                            'Maksimal {{ $maxFoto }} foto dalam satu prediksi.';
                        break;
                    }

                    if (!file.type.startsWith('image/')) {
                        continue;
                    }

                    this.files.push({
                        id: Date.now() + Math.random(),
                        file: file,
                        name: file.name,
                        size: file.size
                    });

                    this.perbaruiInput();
                }

                // Memungkinkan file yang sama dipilih kembali.
                event.target.value = '';
            },


            // Memperbarui input file yang dikirim ke Laravel.
            perbaruiInput() {
                const dataTransfer = new DataTransfer();

                this.files.forEach(item => {
                    dataTransfer.items.add(item.file);
                });

                this.$refs.uploadInput.files = dataTransfer.files;

                this.jumlahFoto = this.files.length;
            },


            // Menghapus foto.
            hapusFoto(index) {
                this.files.splice(index, 1);

                this.perbaruiInput();

                this.pesanError = '';
            },


            // Mematikan kamera.
            tutupKamera() {
                if (this.stream) {
                    this.stream.getTracks().forEach(track => {
                        track.stop();
                    });

                    this.stream = null;
                }

                if (this.$refs.video) {
                    this.$refs.video.srcObject = null;
                }

                this.kameraAktif = false;
            },


            // Format ukuran file.
            ukuranFile(bytes) {
                if (bytes < 1024 * 1024) {
                    return (bytes / 1024).toFixed(1) + ' KB';
                }

                return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
            },


            // Validasi sebelum form dikirim.
            periksaSebelumKirim(event) {
                if (this.files.length === 0) {
                    event.preventDefault();

                    this.pesanError =
                        'Ambil minimal satu foto atau pilih foto dari galeri.';

                    return;
                }

                if (this.files.length > {{ $maxFoto }}) {
                    event.preventDefault();

                    this.pesanError =
                        'Jumlah foto melebihi batas maksimum.';

                    return;
                }

                // Kamera dimatikan sebelum form dikirim.
                this.tutupKamera();
            }
        };
    }
</script>