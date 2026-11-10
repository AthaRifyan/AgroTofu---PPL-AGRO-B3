@php
    // $katalog = ['Tahu Putih' => ['kode' => ..., 'satuan' => 'kg'], ...], $produkAda = koleksi Produk berkunci nama
    $ada = $produkAda->map(fn ($p) => ['stok' => (float) $p->stok, 'harga' => (float) $p->harga_jual, 'satuan' => $p->satuan])->all();
    $namaDipilih = old('nama');
@endphp

<x-app-layout>
    <x-page-header title="Tambah produk" eyebrow="Gudang" :back="route('produk.index')"
                   subtitle="Pilih produk dari katalog. Jika produk sudah ada, harga diperbarui dan stok ditambahkan (tidak dibuat ganda)." />

    <x-card>
        <form method="POST" action="{{ route('produk.store') }}" class="flex flex-col gap-5"
              x-data="produkBaru(@js($ada), @js($namaDipilih ?? ''))"
              x-on:submit="loading = true" x-on:pageshow.window="loading = false">
            @csrf

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="nama" label="Nama produk" class="sm:col-span-2">
                    <x-select-input id="nama" name="nama" x-model="nama" x-on:change="pilih()" required>
                        <option value="">Pilih produk…</option>
                        @foreach (array_keys($katalog) as $nama)
                            <option value="{{ $nama }}" @selected($namaDipilih === $nama)>{{ $nama }}</option>
                        @endforeach
                    </x-select-input>
                </x-field>

                {{-- Info produk yang sudah ada --}}
                <div x-show="info" x-cloak class="flex items-start gap-3 rounded-tm-md bg-tertiary-fixed/50 p-4 text-body-md text-on-tertiary-fixed sm:col-span-2">
                    <x-icon name="info" class="mt-0.5 text-[20px] text-primary" />
                    <div>
                        <p class="text-label-lg">Produk ini sudah ada.</p>
                        <p class="text-body-sm">
                            Stok saat ini <strong x-text="angka(info?.stok) + ' ' + (info?.satuan ?? '')"></strong>,
                            harga jual saat ini <strong x-text="rupiah(info?.harga)"></strong>.
                            Menyimpan akan memperbarui harga jual dan menambah stok pada produk yang sama.
                        </p>
                    </div>
                </div>
                <p x-show="nama && ! info" x-cloak class="flex items-center gap-2 rounded-tm-md bg-surface-container-low p-3 text-body-sm text-on-surface-variant sm:col-span-2">
                    <x-icon name="add_box" class="text-[20px] text-secondary" /> Produk ini belum ada dan akan dibuat baru.
                </p>

                <x-readonly-field label="Kode produk" :value="'Otomatis dari katalog'" />
                <x-readonly-field label="Satuan" value="kg" />

                <x-field name="harga_jual" label="Harga jual (per kg)">
                    <x-rupiah-input id="harga_jual" name="harga_jual" :value="old('harga_jual')" required />
                </x-field>

                <x-field name="tambah_stok" label="Jumlah stok yang ditambahkan (kg)" hint="Boleh dikosongkan jika hanya ingin mengubah harga.">
                    <x-text-input id="tambah_stok" name="tambah_stok" type="number" step="any" min="0" inputmode="decimal" x-model="tambah" :value="old('tambah_stok')" />
                    <p x-show="info && Number(tambah) > 0" x-cloak class="text-body-sm text-on-surface-variant">
                        Stok setelah ditambah: <strong class="text-on-surface" x-text="angka(stokAkhir) + ' kg'"></strong>
                    </p>
                </x-field>
            </div>

            <div class="flex flex-wrap gap-3">
                <x-submit-button class="w-auto" icon="save" loading-text="Menyimpan…">Simpan</x-submit-button>
                <x-btn :href="route('produk.index')" variant="secondary">Batal</x-btn>
            </div>
        </form>
    </x-card>
</x-app-layout>
