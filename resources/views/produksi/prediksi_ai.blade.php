
{{-- resources/views/produksi/_prediksi-ai.blade.php
     Dipasang di produksi/show.blade.php, tepat setelah kartu "Alur tahapan produksi":
         @include('produksi._prediksi-ai')

     Memakai variabel $produksi.
     Relasi prediksiAi.pengguna sudah dimuat di controller.
--}}

@php
    $bisaJalankan = $produksi->status === \App\Enums\StatusProduksi::Berjalan
        && auth()->user()->hasRole('karyawan');

    $riwayatPrediksi = $produksi->prediksiAi
        ->sortByDesc('created_at')
        ->values();

    $terakhir = $riwayatPrediksi->first();

    // Akumulasi seluruh hasil scan dalam satu batch.
    $totalScan = $riwayatPrediksi->count();

    $totalBerat = $riwayatPrediksi->sum('berat_kedelai_kg');

    $totalHasilTahu = $riwayatPrediksi->sum('hasil_tahu_kg');

    $totalDurasiMenit = $riwayatPrediksi->sum('waktu_memasak_menit');

    $kolom = 'md:grid-cols-[1.4fr_1fr_1fr_1fr_1.4fr_0.8fr]';

    // Batas jumlah foto mengikuti konfigurasi layanan AI.
    $maxFoto = max(1, (int) config('services.soybean_ai.max_images', 10));
@endphp

<x-card
    class="mt-4"
    title="Prediksi AI kualitas kedelai"
    description="Lakukan pemindaian kedelai secara bertahap sebelum task pemasakan dimulai. Setiap pemindaian maksimal 10 kg. Hasil prediksi dari setiap pemindaian akan diakumulasikan untuk menentukan total hasil tahu dan durasi pemasakan."
>

    {{-- FORM PEMINDAIAN --}}
    @if ($bisaJalankan)
        <form
            method="POST"
            enctype="multipart/form-data"
            action="{{ route('produksi.prediksi-ai', $produksi) }}"
            class="flex flex-col gap-4"
            x-data="{
                loading: false,
                maxFoto: @js($maxFoto),
                fotos: [],
                nomorFoto: 1,

                kameraAktif: false,
                kameraStream: null,

                tambahFoto(event) {
                    const fileDipilih = Array.from(event.target.files || []);
                    const sisa = this.maxFoto - this.fotos.length;

                    if (fileDipilih.length > sisa) {
                        alert('Maksimal ' + this.maxFoto + ' foto untuk setiap pemindaian. Hanya ' + sisa + ' foto yang dapat ditambahkan.');
                    }

                    const fileDitambahkan = fileDipilih.slice(0, Math.max(0, sisa));

                    fileDitambahkan.forEach(file => {
                        if (!file.type.startsWith('image/')) {
                            return;
                        }

                        this.fotos.push({
                            id: this.nomorFoto++,
                            nama: file.name,
                            ukuran: file.size,
                            url: URL.createObjectURL(file),
                            file: file
                        });
                    });

                    this.sinkronkanFoto();

                    // Mengosongkan input agar foto yang sama dapat dipilih kembali.
                    event.target.value = '';
                },

                sinkronkanFoto() {
                    const transfer = new DataTransfer();

                    this.fotos.forEach(foto => {
                        transfer.items.add(foto.file);
                    });

                    this.$refs.uploadInput.files = transfer.files;
                },

                async bukaKamera() {
                    if (this.fotos.length >= this.maxFoto) {
                        alert('Jumlah foto sudah mencapai batas maksimal.');
                        return;
                    }

                    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                        alert('Browser tidak mendukung akses kamera langsung. Pastikan website dibuka melalui HTTPS atau localhost dan gunakan browser terbaru.');
                        return;
                    }

                    try {
                        this.kameraStream = await navigator.mediaDevices.getUserMedia({
                            video: {
                                facingMode: {
                                    ideal: 'environment'
                                }
                            },
                            audio: false
                        });

                        this.kameraAktif = true;

                        this.$nextTick(async () => {
                            const video = this.$refs.videoKamera;

                            if (video && this.kameraStream) {
                                video.srcObject = this.kameraStream;

                                try {
                                    await video.play();
                                } catch (error) {
                                    console.error('Gagal menjalankan tampilan kamera:', error);
                                }
                            }
                        });

                    } catch (error) {
                        console.error('Kesalahan akses kamera:', error);

                        if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
                            alert('Akses kamera ditolak. Izinkan penggunaan kamera melalui pengaturan izin browser.');
                        } else if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
                            alert('Kamera tidak ditemukan pada perangkat ini.');
                        } else if (error.name === 'NotReadableError') {
                            alert('Kamera sedang digunakan aplikasi lain. Tutup aplikasi tersebut lalu coba kembali.');
                        } else {
                            alert('Kamera tidak dapat dibuka. Pastikan website menggunakan HTTPS dan izin kamera telah diberikan.');
                        }

                        this.tutupKamera();
                    }
                },

                ambilFoto() {
                    if (!this.kameraStream || !this.$refs.videoKamera) {
                        alert('Kamera belum siap. Silakan coba kembali.');
                        return;
                    }

                    if (this.fotos.length >= this.maxFoto) {
                        alert('Jumlah foto sudah mencapai batas maksimal.');
                        this.tutupKamera();
                        return;
                    }

                    const video = this.$refs.videoKamera;

                    if (!video.videoWidth || !video.videoHeight) {
                        alert('Tampilan kamera belum siap. Tunggu sebentar lalu coba kembali.');
                        return;
                    }

                    // Membatasi ukuran foto agar lebih ringan saat dikirim.
                    const batasUkuran = 1920;
                    const skala = Math.min(
                        1,
                        batasUkuran / Math.max(video.videoWidth, video.videoHeight)
                    );

                    const canvas = document.createElement('canvas');

                    canvas.width = Math.round(video.videoWidth * skala);
                    canvas.height = Math.round(video.videoHeight * skala);

                    const context = canvas.getContext('2d');

                    context.drawImage(
                        video,
                        0,
                        0,
                        canvas.width,
                        canvas.height
                    );

                    canvas.toBlob(blob => {
                        if (!blob) {
                            alert('Gagal mengambil foto. Silakan coba kembali.');
                            return;
                        }

                        const namaFile = 'kamera-kedelai-' + Date.now() + '.jpg';

                        const file = new File(
                            [blob],
                            namaFile,
                            {
                                type: 'image/jpeg',
                                lastModified: Date.now()
                            }
                        );

                        this.fotos.push({
                            id: this.nomorFoto++,
                            nama: file.name,
                            ukuran: file.size,
                            url: URL.createObjectURL(file),
                            file: file
                        });

                        this.sinkronkanFoto();

                        // Tutup kamera setelah satu foto berhasil diambil.
                        this.tutupKamera();

                    }, 'image/jpeg', 0.90);
                },

                tutupKamera() {
                    if (this.kameraStream) {
                        this.kameraStream.getTracks().forEach(track => {
                            track.stop();
                        });

                        this.kameraStream = null;
                    }

                    if (this.$refs.videoKamera) {
                        this.$refs.videoKamera.srcObject = null;
                    }

                    this.kameraAktif = false;
                },

                hapusFoto(id) {
                    const foto = this.fotos.find(item => item.id === id);

                    if (foto) {
                        URL.revokeObjectURL(foto.url);
                    }

                    this.fotos = this.fotos.filter(item => item.id !== id);

                    this.sinkronkanFoto();
                },

                hapusSemuaFoto() {
                    this.fotos.forEach(foto => {
                        URL.revokeObjectURL(foto.url);
                    });

                    this.fotos = [];

                    this.sinkronkanFoto();
                },

                formatUkuran(bytes) {
                    if (bytes < 1024 * 1024) {
                        return (bytes / 1024).toFixed(0) + ' KB';
                    }

                    return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
                },

                validasiSubmit(event) {
                    if (this.fotos.length === 0) {
                        event.preventDefault();
                        alert('Silakan ambil foto menggunakan kamera atau pilih foto dari galeri terlebih dahulu.');
                        return;
                    }

                    this.tutupKamera();
                    this.loading = true;
                }
            }"
            x-on:submit="validasiSubmit($event)"
            x-on:pageshow.window="loading = false"
            x-on:keydown.escape.window="tutupKamera()"
        >
            @csrf

            <div class="rounded-tm-md bg-surface-container-low p-3.5">
                <div class="text-label-lg font-bold text-on-surface">
                    Pemindaian kedelai per tahap
                </div>

                <p class="mt-1 text-body-sm text-on-surface-variant">
                    Masukkan berat kedelai untuk pemindaian saat ini. Maksimal 10 kg
                    untuk setiap pemindaian. Jika kedelai lebih dari 10 kg,
                    lakukan pemindaian berikutnya.
                </p>
            </div>

            {{-- BERAT KEDELAI --}}
            <x-field name="berat_kedelai_kg" label="Berat kedelai (kg)">
                <x-text-input
                    id="berat_kedelai_kg"
                    name="berat_kedelai_kg"
                    type="number"
                    step="0.01"
                    min="0.01"
                    max="10"
                    inputmode="decimal"
                    required
                    class="sm:w-64"
                    :value="old('berat_kedelai_kg')"
                    placeholder="Contoh: 10"
                />
            </x-field>

            @error('berat_kedelai_kg')
                <p class="text-body-sm text-error">{{ $message }}</p>
            @enderror

            {{-- FOTO KEDELAI --}}
            <div>
                <div class="mb-2">
                    <label class="block text-label-md text-on-surface">
                        Foto biji kedelai
                    </label>

                    <p class="text-body-sm text-on-surface-variant">
                        Maksimal {{ $maxFoto }} foto,
                        10 MB per foto. Kamu bisa mengambil foto langsung
                        atau memilih foto dari galeri.
                    </p>
                </div>

                {{-- INPUT FILE ASLI YANG DIKIRIM KE SERVER --}}
                <input
                    type="file"
                    id="images"
                    name="images[]"
                    accept="image/png,image/jpeg,image/webp"
                    multiple
                    x-ref="uploadInput"
                    class="hidden"
                >

                {{-- INPUT GALERI --}}
                <input
                    type="file"
                    accept="image/png,image/jpeg,image/webp"
                    multiple
                    x-ref="galleryInput"
                    x-on:change="tambahFoto($event)"
                    class="hidden"
                >

                {{-- TOMBOL KAMERA DAN GALERI --}}
                <div class="flex flex-col gap-2 sm:flex-row">

                    <button
                        type="button"
                        x-on:click="bukaKamera()"
                        x-bind:disabled="fotos.length >= maxFoto || loading"
                        class="inline-flex items-center justify-center gap-2 rounded-tm-md bg-primary px-4 py-3 text-label-md font-semibold text-on-primary transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <x-icon name="sensors" class="text-[20px]" />
                        Ambil Foto
                    </button>

                    <button
                        type="button"
                        x-on:click="$refs.galleryInput.click()"
                        x-bind:disabled="fotos.length >= maxFoto || loading"
                        class="inline-flex items-center justify-center gap-2 rounded-tm-md border border-outline px-4 py-3 text-label-md font-semibold text-on-surface transition hover:bg-surface-container disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <x-icon name="add_box" class="text-[20px]" />
                        Pilih dari Galeri
                    </button>

                </div>

                {{-- MODAL KAMERA LANGSUNG --}}
                <div
                    x-show="kameraAktif"
                    x-cloak
                    x-transition.opacity
                    class="fixed inset-0 z-[100] flex items-center justify-center bg-black/90 p-3 sm:p-6"
                >
                    <div class="w-full max-w-2xl overflow-hidden rounded-tm-md bg-surface shadow-xl">

                        {{-- HEADER KAMERA --}}
                        <div class="flex items-center justify-between gap-3 border-b border-outline-variant p-4">
                            <div>
                                <div class="text-label-lg font-bold text-on-surface">
                                    Kamera kedelai
                                </div>

                                <p class="text-body-sm text-on-surface-variant">
                                    Arahkan kamera ke biji kedelai.
                                </p>
                            </div>

                            <button
                                type="button"
                                x-on:click="tutupKamera()"
                                class="flex h-9 w-9 items-center justify-center rounded-full text-on-surface transition hover:bg-surface-container"
                                aria-label="Tutup kamera"
                            >
                                <x-icon name="close" class="text-[22px]" />
                            </button>
                        </div>

                        {{-- TAMPILAN VIDEO KAMERA --}}
                        <div class="relative bg-black">
                            <video
                                x-ref="videoKamera"
                                autoplay
                                playsinline
                                muted
                                class="block max-h-[65vh] min-h-[220px] w-full object-contain"
                            ></video>
                        </div>

                        {{-- TOMBOL AMBIL FOTO --}}
                        <div class="flex flex-col gap-2 p-4 sm:flex-row sm:justify-end">

                            <button
                                type="button"
                                x-on:click="tutupKamera()"
                                class="rounded-tm-md border border-outline px-5 py-3 text-label-md font-semibold text-on-surface transition hover:bg-surface-container"
                            >
                                Batal
                            </button>

                            <button
                                type="button"
                                x-on:click="ambilFoto()"
                                x-bind:disabled="fotos.length >= maxFoto"
                                class="inline-flex items-center justify-center gap-2 rounded-tm-md bg-primary px-5 py-3 text-label-md font-semibold text-on-primary transition hover:opacity-90 disabled:opacity-50"
                            >
                                <x-icon name="sensors" class="text-[20px]" />
                                Ambil Gambar
                            </button>

                        </div>
                    </div>
                </div>

                {{-- JUMLAH FOTO --}}
                <div class="mt-3 flex items-center justify-between gap-2">
                    <div class="text-body-sm text-on-surface-variant">
                        Foto dipilih:
                        <span
                            class="font-bold text-on-surface"
                            x-text="fotos.length + ' / ' + maxFoto"
                        >0 / {{ $maxFoto }}</span>
                    </div>

                    <button
                        type="button"
                        x-show="fotos.length > 0"
                        x-on:click="hapusSemuaFoto()"
                        class="text-body-sm font-semibold text-error hover:underline"
                    >
                        Hapus semua
                    </button>
                </div>

                {{-- PREVIEW FOTO --}}
                <div
                    x-show="fotos.length > 0"
                    x-cloak
                    class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4"
                >
                    <template x-for="foto in fotos" :key="foto.id">
                        <div class="overflow-hidden rounded-tm-md border border-outline-variant bg-surface-container-low">

                            <div class="relative aspect-square">
                                <img
                                    x-bind:src="foto.url"
                                    x-bind:alt="foto.nama"
                                    class="h-full w-full object-cover"
                                >

                                {{-- TOMBOL HAPUS FOTO --}}
                                <button
                                    type="button"
                                    x-on:click="hapusFoto(foto.id)"
                                    x-bind:disabled="loading"
                                    aria-label="Hapus foto"
                                    class="absolute right-2 top-2 flex h-8 w-8 items-center justify-center rounded-full bg-black/70 text-white transition hover:bg-error disabled:opacity-50"
                                >
                                    <x-icon name="close" class="text-[18px]" />
                                </button>

                                {{-- NOMOR FOTO --}}
                                <span
                                    class="absolute bottom-2 left-2 rounded-full bg-black/70 px-2 py-1 text-xs font-semibold text-white"
                                    x-text="'Foto ' + foto.id"
                                ></span>
                            </div>

                            <div class="p-2">
                                <div
                                    class="truncate text-body-sm font-medium text-on-surface"
                                    x-text="foto.nama"
                                    x-bind:title="foto.nama"
                                ></div>

                                <div
                                    class="text-xs text-on-surface-variant"
                                    x-text="formatUkuran(foto.ukuran)"
                                ></div>
                            </div>

                        </div>
                    </template>
                </div>

                <p
                    x-show="fotos.length === 0"
                    class="mt-3 rounded-tm-md border border-dashed border-outline-variant p-5 text-center text-body-sm text-on-surface-variant"
                >
                    <x-icon name="add_box" class="mb-1 text-[28px]" />
                    <span class="block">Belum ada foto yang dipilih.</span>
                    <span class="block">Ambil foto menggunakan kamera atau pilih dari galeri.</span>
                </p>

                @error('images')
                    <p class="mt-1 text-body-sm text-error">{{ $message }}</p>
                @enderror

                @error('images.*')
                    <p class="mt-1 text-body-sm text-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- TOMBOL PROSES --}}
            <x-submit-button
                class="w-auto self-start"
                loading-text="Memproses prediksi AI... jangan tutup halaman"
            >
                Jalankan pemindaian AI
            </x-submit-button>

            <p class="text-body-sm text-on-surface-variant">
                Setiap pemindaian yang berhasil akan ditambahkan ke akumulasi
                batch. Pastikan berat dan foto yang dimasukkan sesuai dengan
                kedelai yang sedang diproses.
            </p>
        </form>
    @else
        <p class="text-body-sm text-on-surface-variant">
            Prediksi AI hanya tersedia untuk karyawan pada batch yang sedang
            berjalan. Pemindaian dilakukan sebelum task pemasakan dimulai.
        </p>
    @endif

    {{-- RINGKASAN AKUMULASI --}}
    @if ($totalScan > 0)
        <div class="mt-5">
            <div class="mb-3">
                <div class="text-label-lg font-bold text-on-surface">
                    Akumulasi hasil pemindaian
                </div>

                <p class="text-body-sm text-on-surface-variant">
                    Total seluruh pemindaian yang berhasil dilakukan pada batch ini.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-3 md:grid-cols-4">

                {{-- JUMLAH SCAN --}}
                <div class="rounded-tm-md bg-surface-container-low p-3.5">
                    <div class="text-body-sm text-on-surface-variant">
                        Total pemindaian
                    </div>

                    <div class="text-label-lg font-bold text-on-surface">
                        {{ $totalScan }} kali
                    </div>
                </div>

                {{-- TOTAL BERAT --}}
                <div class="rounded-tm-md bg-surface-container-low p-3.5">
                    <div class="text-body-sm text-on-surface-variant">
                        Total kedelai
                    </div>

                    <div class="text-label-lg font-bold text-on-surface">
                        {{ number_format($totalBerat, 2) }} kg
                    </div>
                </div>

                {{-- TOTAL TAHU --}}
                <div class="rounded-tm-md bg-surface-container-low p-3.5">
                    <div class="text-body-sm text-on-surface-variant">
                        Estimasi total tahu
                    </div>

                    <div class="text-label-lg font-bold text-on-surface">
                        {{ number_format($totalHasilTahu, 2) }} kg
                    </div>
                </div>

                {{-- TOTAL DURASI --}}
                <div class="rounded-tm-md bg-surface-container-low p-3.5">
                    <div class="text-body-sm text-on-surface-variant">
                        Total durasi pemasakan
                    </div>

                    <div class="text-label-lg font-bold text-on-surface">
                        {{ \App\Models\PrediksiAi::label($totalDurasiMenit) }}
                    </div>
                </div>
            </div>

            <div class="mt-3 rounded-tm-md border border-outline-variant p-3.5">
                <div class="text-label-md font-bold text-on-surface">
                    Informasi akumulasi
                </div>

                <p class="mt-1 text-body-sm text-on-surface-variant">
                    Durasi pemasakan merupakan penjumlahan hasil prediksi
                    dari {{ $totalScan }} pemindaian, yaitu
                    {{ \App\Models\PrediksiAi::label($totalDurasiMenit) }}.
                    Nilai ini digunakan sebagai total durasi pemasakan batch
                    selama task pemasakan masih menunggu untuk dimulai.
                </p>
            </div>
        </div>
    @endif

    {{-- HASIL PEMINDAIAN TERAKHIR --}}
    @if ($terakhir)
        <div class="mt-5">
            <div class="mb-3">
                <div class="text-label-lg font-bold text-on-surface">
                    Hasil pemindaian terakhir
                </div>

                <p class="text-body-sm text-on-surface-variant">
                    Hasil AI dari pemindaian yang paling baru dilakukan.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-3 md:grid-cols-4">

                <div class="rounded-tm-md bg-surface-container-low p-3.5">
                    <div class="text-body-sm text-on-surface-variant">
                        Good
                    </div>

                    <div class="text-label-lg font-bold text-on-surface">
                        {{ number_format($terakhir->good_percent, 1) }}%
                    </div>
                </div>

                <div class="rounded-tm-md bg-surface-container-low p-3.5">
                    <div class="text-body-sm text-on-surface-variant">
                        Bad
                    </div>

                    <div class="text-label-lg font-bold text-on-surface">
                        {{ number_format($terakhir->bad_percent, 1) }}%
                    </div>
                </div>

                <div class="rounded-tm-md bg-surface-container-low p-3.5">
                    <div class="text-body-sm text-on-surface-variant">
                        Estimasi hasil tahu
                    </div>

                    <div class="text-label-lg font-bold text-on-surface">
                        {{ number_format($terakhir->hasil_tahu_kg, 2) }} kg
                    </div>
                </div>

                <div class="rounded-tm-md bg-surface-container-low p-3.5">
                    <div class="text-body-sm text-on-surface-variant">
                        Durasi pemasakan
                    </div>

                    <div class="text-label-lg font-bold text-on-surface">
                        {{ \App\Models\PrediksiAi::label($terakhir->waktu_memasak_menit) }}
                    </div>
                </div>

            </div>
        </div>
    @endif

</x-card>

{{-- RIWAYAT SELURUH PEMINDAIAN --}}
@if ($totalScan > 0)
    <x-card class="mt-4 !p-0 overflow-hidden">

        <div class="px-5 pt-5 sm:px-6">
            <div class="text-label-lg font-bold text-on-surface">
                Riwayat pemindaian AI
            </div>

            <p class="mt-1 text-body-sm text-on-surface-variant">
                Setiap baris menunjukkan satu pemindaian. Nilai total
                akumulasi ditampilkan pada ringkasan di atas.
            </p>
        </div>

        <div class="mt-4">
            <x-table-head :cols="$kolom">
                <span>Waktu / oleh</span>
                <span>Berat / foto</span>
                <span>Good / Bad</span>
                <span>Hasil tahu</span>
                <span>Durasi masak</span>
                <span>Diterapkan</span>
            </x-table-head>

            <div class="divide-y divide-surface-container-low">

                @foreach ($riwayatPrediksi as $riwayat)
                    <div
                        class="grid grid-cols-2 items-center gap-x-3 gap-y-1 px-5 py-3.5 text-body-md text-on-surface sm:px-6 {{ $kolom }}"
                    >

                        {{-- WAKTU DAN PENGGUNA --}}
                        <div>
                            <div class="text-label-lg">
                                {{ $riwayat->created_at->translatedFormat('d M Y H:i') }}
                            </div>

                            <div class="text-body-sm text-on-surface-variant">
                                {{ $riwayat->pengguna?->name ?? '-' }}
                            </div>
                        </div>

                        {{-- BERAT DAN FOTO --}}
                        <div>
                            {{ number_format($riwayat->berat_kedelai_kg, 2) }} kg

                            <div class="text-body-sm text-on-surface-variant">
                                {{ $riwayat->jumlah_gambar }} foto
                            </div>
                        </div>

                        {{-- GOOD DAN BAD --}}
                        <div>
                            {{ number_format($riwayat->good_percent, 1) }}%
                            /
                            {{ number_format($riwayat->bad_percent, 1) }}%
                        </div>

                        {{-- HASIL TAHU --}}
                        <div>
                            {{ number_format($riwayat->hasil_tahu_kg, 2) }} kg
                        </div>

                        {{-- DURASI SETIAP SCAN --}}
                        <div>
                            {{ \App\Models\PrediksiAi::label($riwayat->waktu_memasak_menit) }}
                        </div>

                        {{-- STATUS PENERAPAN --}}
                        <div class="text-body-sm text-on-surface-variant">
                            {{ $riwayat->diterapkan ? 'Ya' : 'Tidak' }}
                        </div>

                    </div>
                @endforeach

            </div>
        </div>

        {{-- TOTAL AKUMULASI DI BAGIAN BAWAH --}}
        <div class="border-t border-outline-variant bg-surface-container-low p-4 sm:px-6">
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4">

                <div>
                    <div class="text-body-sm text-on-surface-variant">
                        Total scan
                    </div>

                    <div class="text-label-lg font-bold text-on-surface">
                        {{ $totalScan }} kali
                    </div>
                </div>

                <div>
                    <div class="text-body-sm text-on-surface-variant">
                        Total kedelai
                    </div>

                    <div class="text-label-lg font-bold text-on-surface">
                        {{ number_format($totalBerat, 2) }} kg
                    </div>
                </div>

                <div>
                    <div class="text-body-sm text-on-surface-variant">
                        Total hasil tahu
                    </div>

                    <div class="text-label-lg font-bold text-on-surface">
                        {{ number_format($totalHasilTahu, 2) }} kg
                    </div>
                </div>

                <div>
                    <div class="text-body-sm text-on-surface-variant">
                        Total durasi
                    </div>

                    <div class="text-label-lg font-bold text-on-surface">
                        {{ \App\Models\PrediksiAi::label($totalDurasiMenit) }}
                    </div>
                </div>

            </div>
        </div>

    </x-card>
@endif
