
@php
    $bahanErrors = collect($errors->getMessages())
        ->filter(fn ($messages, $key) => str_starts_with($key, 'bahan'))
        ->flatten()
        ->all();

    $bahanAwal = old('bahan', [
        [
            'bahan_baku_id' => '',
            'jumlah' => '',
        ],
    ]);

    if (empty($bahanAwal)) {
        $bahanAwal = [
            [
                'bahan_baku_id' => '',
                'jumlah' => '',
            ],
        ];
    }
@endphp

<x-app-layout>
    <x-page-header
        title="Buat laporan produksi"
        eyebrow="Produksi"
        subtitle="Catat satu batch produksi. Stok bahan baku akan langsung dikurangi ketika laporan dibuat."
        :back="route('produksi.index')"
    />

    <form
        method="POST"
        action="{{ route('produksi.store') }}"
        class="flex flex-col gap-4"
        x-data="produksiForm(
            @js($bahanJson),
            @js($bahanAwal)
        )"
        x-on:submit="if (!validasiForm()) { $event.preventDefault() }"
    >
        @csrf

        {{-- INFORMASI BATCH --}}
        <x-card title="Informasi batch">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-field
                    name="tanggal_jadwal"
                    label="Tanggal produksi"
                >
                    <x-text-input
                        id="tanggal_jadwal"
                        name="tanggal_jadwal"
                        type="date"
                        :value="old('tanggal_jadwal', now()->toDateString())"
                        required
                    />
                </x-field>

                <x-field
                    name="durasi_non_masak_estimasi_menit"
                    label="Estimasi proses selain pemasakan (menit)"
                >
                    <x-text-input
                        id="durasi_non_masak_estimasi_menit"
                        name="durasi_non_masak_estimasi_menit"
                        type="number"
                        min="1"
                        max="1440"
                        step="1"
                        required
                        inputmode="numeric"
                        placeholder="Contoh: 120"
                        :value="old('durasi_non_masak_estimasi_menit', 120)"
                    />
                </x-field>
            </div>

            <p class="mt-2 text-body-sm text-on-surface-variant">
                Estimasi total produksi nantinya dihitung dari prediksi
                durasi pemasakan AI ditambah estimasi proses selain pemasakan.
            </p>

            <x-field
                class="mt-5"
                name="catatan"
                label="Catatan awal (opsional)"
            >
                <x-textarea-input
                    id="catatan"
                    name="catatan"
                    rows="3"
                    placeholder="Kondisi kedelai, kondisi bahan, dan informasi lainnya..."
                >{{ old('catatan') }}</x-textarea-input>
            </x-field>
        </x-card>

        {{-- BAHAN BAKU --}}
        <x-card
            title="Bahan baku yang digunakan"
            description="Masukkan semua bahan yang digunakan pada batch ini. Stok akan langsung dikurangi ketika batch dibuat."
        >
            {{-- LEGENDA KATEGORI --}}
            <div class="mb-4 flex flex-wrap gap-2 text-label-sm">
                <span class="inline-flex items-center gap-1.5 rounded-tm-lg bg-primary-container px-2.5 py-1 text-on-primary-container">
                    <span class="h-2 w-2 rounded-full bg-current opacity-70"></span>
                    Utama
                </span>

                <span class="inline-flex items-center gap-1.5 rounded-tm-lg bg-secondary-fixed px-2.5 py-1 text-on-secondary-fixed-variant">
                    <span class="h-2 w-2 rounded-full bg-current opacity-70"></span>
                    Penggumpal
                </span>

                <span class="inline-flex items-center gap-1.5 rounded-tm-lg bg-tertiary-fixed px-2.5 py-1 text-on-tertiary-fixed">
                    <span class="h-2 w-2 rounded-full bg-current opacity-70"></span>
                    Pendukung
                </span>
            </div>

            {{-- DAFTAR BARIS BAHAN --}}
            <div class="space-y-3">
                <template
                    x-for="(row, i) in rows"
                    :key="i"
                >
                    <div
                        class="rounded-tm-md border-l-4 p-3 transition-colors"
                        x-bind:class="{
                            'border-primary-container bg-primary-fixed/30': kat(row) === 'utama',
                            'border-secondary bg-secondary-fixed/40': kat(row) === 'penggumpal',
                            'border-tertiary bg-tertiary-fixed/40': kat(row) === 'pendukung',
                            'border-outline-variant bg-surface-container-low': !kat(row)
                        }"
                    >
                        <div class="flex items-start gap-2">
                            <div class="grid flex-1 gap-3 sm:grid-cols-[1fr_9rem]">

                                {{-- PILIH BAHAN --}}
                                <div>
                                    <div class="mb-1 flex items-center justify-between gap-2">
                                        <label
                                            class="text-label-md text-on-surface"
                                            x-bind:for="'bahan_' + i"
                                        >
                                            Pilih bahan
                                        </label>

                                        <span
                                            x-show="kat(row)"
                                            x-cloak
                                            class="rounded-tm-lg px-2 py-0.5 text-label-sm capitalize"
                                            x-bind:class="{
                                                'bg-primary-container text-on-primary-container': kat(row) === 'utama',
                                                'bg-secondary-fixed text-on-secondary-fixed-variant': kat(row) === 'penggumpal',
                                                'bg-tertiary-fixed text-on-tertiary-fixed': kat(row) === 'pendukung'
                                            }"
                                            x-text="kat(row)"
                                        ></span>
                                    </div>

                                    <select
                                        x-bind:id="'bahan_' + i"
                                        x-bind:name="`bahan[${i}][bahan_baku_id]`"
                                        x-model="row.bahan_baku_id"
                                        required
                                        class="w-full rounded-tm-md border-2 bg-surface-container-lowest py-3 pl-4 pr-10 text-body-md text-on-surface shadow-sm focus:outline-none focus:ring-2"
                                        x-bind:class="{
                                            'border-primary-container focus:border-primary-container focus:ring-primary-container/50': kat(row) === 'utama',
                                            'border-secondary focus:border-secondary focus:ring-secondary/40': kat(row) === 'penggumpal',
                                            'border-tertiary focus:border-tertiary focus:ring-tertiary/40': kat(row) === 'pendukung',
                                            'border-outline focus:border-primary focus:ring-primary/30': !kat(row)
                                        }"
                                    >
                                        <option value="">Pilih bahan...</option>

                                        <template
                                            x-for="item in bahan"
                                            :key="item.id"
                                        >
                                            <option
                                                :value="item.id"
                                                :disabled="dipakaiBaris(item.id, i)"
                                                x-text="item.nama + ' · stok ' +
                                                    Number(item.stok).toLocaleString('id-ID') +
                                                    ' ' + item.satuan"
                                            ></option>
                                        </template>
                                    </select>
                                </div>

                                {{-- JUMLAH BAHAN --}}
                                <div>
                                    <label
                                        class="mb-1 block text-label-md text-on-surface"
                                        x-bind:for="'jumlah_' + i"
                                    >
                                        Jumlah

                                        <span
                                            x-show="info(row.bahan_baku_id)"
                                            x-text="'(' + (info(row.bahan_baku_id)?.satuan ?? '') + ')'"
                                        ></span>
                                    </label>

                                    <input
                                        x-bind:id="'jumlah_' + i"
                                        x-bind:name="`bahan[${i}][jumlah]`"
                                        x-model="row.jumlah"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        inputmode="decimal"
                                        required
                                        placeholder="Contoh: 50"
                                        class="w-full rounded-tm-md border border-transparent bg-surface-container-low px-4 py-3 text-body-md text-on-surface focus:border-primary-container focus:bg-surface-bright focus:outline-none focus:ring-2 focus:ring-primary-container"
                                        x-bind:class="{
                                            'border-error bg-error-container/20': melebihiStok(row)
                                        }"
                                    />
                                </div>
                            </div>

                            {{-- HAPUS BARIS --}}
                            <button
                                type="button"
                                x-on:click="hapus(i)"
                                x-show="rows.length > 1"
                                aria-label="Hapus baris bahan"
                                class="mt-6 flex h-10 w-10 shrink-0 items-center justify-center rounded-tm-md text-on-surface-variant transition hover:bg-surface-container-high focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary"
                            >
                                <x-icon name="delete" class="text-[22px]" />
                            </button>
                        </div>

                        {{-- PERINGATAN STOK --}}
                        <p
                            x-show="melebihiStok(row)"
                            x-cloak
                            class="mt-2 flex items-center gap-1 text-body-sm text-error"
                        >
                            <x-icon name="warning" class="text-[16px]" />

                            <span>
                                Jumlah melebihi stok saat ini. Kurangi jumlah
                                atau periksa stok bahan baku.
                            </span>
                        </p>

                        {{-- PERINGATAN BAHAN BELUM DIPILIH --}}
                        <p
                            x-show="row.bahan_baku_id && !info(row.bahan_baku_id)"
                            x-cloak
                            class="mt-2 text-body-sm text-error"
                        >
                            Bahan tidak ditemukan. Silakan pilih kembali.
                        </p>
                    </div>
                </template>
            </div>

            {{-- TAMBAH BAHAN DAN TOTAL KEDELAI --}}
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                <x-btn
                    type="button"
                    variant="secondary"
                    size="sm"
                    icon="add"
                    x-on:click="tambah()"
                >
                    Tambah bahan
                </x-btn>

                <div class="text-right">
                    <p class="text-body-sm text-on-surface-variant">
                        Berat kedelai (kategori utama)
                    </p>

                    <p
                        class="text-title-lg font-bold text-on-surface"
                        x-text="new Intl.NumberFormat('id-ID', {
                            minimumFractionDigits: 0,
                            maximumFractionDigits: 2
                        }).format(beratUtama) + ' kg'"
                    ></p>
                </div>
            </div>

            {{-- TOTAL KEDELAI HARUS ADA --}}
            <p
                x-show="beratUtama <= 0"
                x-cloak
                class="mt-3 flex items-center gap-1 text-body-sm text-error"
            >
                <x-icon name="warning" class="text-[16px]" />
                Tambahkan minimal satu bahan berkategori utama (kedelai).
            </p>

            {{-- ERROR VALIDASI SERVER --}}
            <x-input-error
                class="mt-3"
                :messages="$bahanErrors"
            />
        </x-card>

        {{-- INFORMASI PENGURANGAN STOK --}}
        <div class="rounded-tm-md bg-secondary-fixed/50 p-4 text-body-sm text-on-secondary-fixed-variant">
            <div class="flex items-start gap-2">
                <x-icon name="info" class="mt-0.5 shrink-0 text-[18px]" />

                <div>
                    <strong>Perhatian:</strong>

                    setelah batch dibuat, stok seluruh bahan baku yang
                    dimasukkan akan langsung dikurangi dan batch berstatus
                    berjalan.

                    Jika batch belum selesai, laporan tetap dapat dilanjutkan
                    setelah karyawan logout dan login kembali.
                </div>
            </div>
        </div>

        {{-- TOMBOL --}}
        <div class="flex flex-wrap gap-3">
            <x-submit-button
                class="w-auto"
                icon="save"
                loading-text="Membuat batch..."
            >
                Buat batch produksi
            </x-submit-button>

            <x-btn
                :href="route('produksi.index')"
                variant="secondary"
            >
                Kembali
            </x-btn>
        </div>
    </form>
</x-app-layout>

{{-- LOGIC ALPINE --}}
<script>
    function produksiForm(dataBahan, dataAwal) {
        return {
            bahan: dataBahan || [],

            rows: Array.isArray(dataAwal) && dataAwal.length > 0
                ? dataAwal.map(row => ({
                    bahan_baku_id: row.bahan_baku_id ?? '',
                    jumlah: row.jumlah ?? ''
                }))
                : [{
                    bahan_baku_id: '',
                    jumlah: ''
                }],

            // Mengambil informasi bahan berdasarkan ID.
            info(id) {
                return this.bahan.find(item =>
                    String(item.id) === String(id)
                );
            },

            // Mengambil kategori bahan.
            kat(row) {
                const item = this.info(row.bahan_baku_id);

                return item ? item.kategori : '';
            },

            // Menambahkan baris bahan.
            tambah() {
                this.rows.push({
                    bahan_baku_id: '',
                    jumlah: ''
                });
            },

            // Menghapus baris bahan.
            hapus(index) {
                if (this.rows.length > 1) {
                    this.rows.splice(index, 1);
                }
            },

            // Memeriksa apakah bahan sudah digunakan di baris lain.
            dipakaiBaris(id, index) {
                return this.rows.some((row, i) =>
                    i !== index &&
                    String(row.bahan_baku_id) === String(id)
                );
            },

            // Menghitung total kedelai kategori utama.
            get beratUtama() {
                return this.rows.reduce((total, row) => {
                    const item = this.info(row.bahan_baku_id);

                    if (item && item.kategori === 'utama') {
                        return total + Number(row.jumlah || 0);
                    }

                    return total;
                }, 0);
            },

            // Memeriksa jumlah terhadap stok bahan.
            melebihiStok(row) {
                const item = this.info(row.bahan_baku_id);

                return item &&
                    Number(row.jumlah || 0) > Number(item.stok);
            },

            // Memeriksa apakah ada bahan yang melebihi stok.
            adaStokTidakCukup() {
                return this.rows.some(row =>
                    this.melebihiStok(row)
                );
            },

            // Memeriksa apakah ada pilihan bahan yang duplikat.
            adaBahanDuplikat() {
                const ids = this.rows
                    .map(row => String(row.bahan_baku_id))
                    .filter(id => id !== '');

                return new Set(ids).size !== ids.length;
            },

            // Memeriksa kelengkapan form sebelum dikirim.
            validasiForm() {
                if (this.beratUtama <= 0) {
                    alert(
                        'Tambahkan minimal satu bahan berkategori utama (kedelai).'
                    );

                    return false;
                }

                if (this.adaStokTidakCukup()) {
                    alert(
                        'Ada jumlah bahan yang melebihi stok tersedia. Periksa kembali.'
                    );

                    return false;
                }

                if (this.adaBahanDuplikat()) {
                    alert(
                        'Bahan yang sama tidak boleh dimasukkan lebih dari satu kali.'
                    );

                    return false;
                }

                for (const row of this.rows) {
                    if (!row.bahan_baku_id || !row.jumlah) {
                        alert(
                            'Lengkapi semua pilihan bahan dan jumlah penggunaannya.'
                        );

                        return false;
                    }

                    if (Number(row.jumlah) <= 0) {
                        alert(
                            'Jumlah bahan harus lebih besar dari nol.'
                        );

                        return false;
                    }
                }

                return true;
            }
        };
    }
</script>
