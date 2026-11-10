<x-app-layout>
    <x-page-header title="Ubah produk" eyebrow="Gudang" :subtitle="$produk->nama" :back="route('produk.index')" />

    <x-card>
        <form method="POST" action="{{ route('produk.update', $produk) }}" class="flex flex-col gap-5"
              x-data="{ loading: false }" x-on:submit="loading = true" x-on:pageshow.window="loading = false">
            @csrf
            @method('PUT')

            <p class="flex items-start gap-2 rounded-tm-md bg-surface-container-low p-3 text-body-sm text-on-surface-variant">
                <x-icon name="lock" class="mt-px text-[18px]" />
                <span>Nama, kode, dan satuan produk bersifat tetap. Yang bisa diubah hanya harga jual dan status aktif.</span>
            </p>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-readonly-field label="Nama produk" :value="$produk->nama" />
                <x-readonly-field label="Kode" :value="$produk->kode" />
                <x-readonly-field label="Satuan" :value="$produk->satuan" />

                <x-field name="harga_jual" :label="'Harga jual (per ' . $produk->satuan . ')'">
                    <x-rupiah-input id="harga_jual" name="harga_jual" :value="old('harga_jual', $produk->harga_jual)" required />
                </x-field>
            </div>

            <x-checkbox name="is_active" label="Produk aktif (bisa dijual)" :checked="(bool) $produk->is_active" />

            <div class="flex flex-wrap gap-3">
                <x-submit-button class="w-auto" icon="save" loading-text="Menyimpan…">Simpan</x-submit-button>
                <x-btn :href="route('produk.index')" variant="secondary">Batal</x-btn>
            </div>
        </form>
    </x-card>
</x-app-layout>
