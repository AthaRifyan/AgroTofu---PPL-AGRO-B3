{{-- Variabel: $produk, $action, $method (POST|PUT) --}}
<form method="POST" action="{{ $action }}" class="flex flex-col gap-5"
      x-data="{ loading: false }" x-on:submit="loading = true" x-on:pageshow.window="loading = false">
    @csrf
    @if ($method !== 'POST') @method($method) @endif

    <div class="grid gap-5 sm:grid-cols-2">
        <x-field name="kode" label="Kode">
            <x-text-input id="kode" name="kode" type="text" :value="old('kode', $produk->kode)" maxlength="20" required autofocus />
        </x-field>
        <x-field name="nama" label="Nama produk">
            <x-text-input id="nama" name="nama" type="text" :value="old('nama', $produk->nama)" maxlength="100" required />
        </x-field>
        <x-field name="satuan" label="Satuan">
            <x-select-input id="satuan" name="satuan" required>
                @foreach (\App\Models\Produk::SATUAN as $s)
                    <option value="{{ $s }}" @selected(old('satuan', $produk->satuan) === $s)>{{ $s }}</option>
                @endforeach
            </x-select-input>
        </x-field>
        <x-field name="harga_jual" label="Harga jual (Rp)">
            <x-text-input id="harga_jual" name="harga_jual" type="number" step="any" min="0" inputmode="decimal" :value="old('harga_jual', $produk->harga_jual)" required />
        </x-field>
    </div>

    <x-checkbox name="is_active" label="Produk aktif (bisa dijual)" :checked="$produk->exists ? (bool) $produk->is_active : true" />

    <div class="flex flex-wrap gap-3">
        <x-submit-button class="w-auto" icon="save" loading-text="Menyimpan…">Simpan</x-submit-button>
        <x-btn :href="route('produk.index')" variant="secondary">Batal</x-btn>
    </div>
</form>
