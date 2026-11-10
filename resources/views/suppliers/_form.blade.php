{{-- Variabel: $supplier, $action, $method (POST|PUT) --}}
<form method="POST" action="{{ $action }}" class="flex flex-col gap-5"
      x-data="{ loading: false }" x-on:submit="loading = true" x-on:pageshow.window="loading = false">
    @csrf
    @if ($method !== 'POST') @method($method) @endif

    <div class="grid gap-5 sm:grid-cols-2">
        <x-field name="kode" label="Kode">
            <x-text-input id="kode" name="kode" type="text" :value="old('kode', $supplier->kode)" maxlength="20" required autofocus />
        </x-field>
        <x-field name="nama" label="Nama supplier">
            <x-text-input id="nama" name="nama" type="text" :value="old('nama', $supplier->nama)" maxlength="100" required />
        </x-field>
        <x-field name="kontak_person" label="Kontak person (opsional)">
            <x-text-input id="kontak_person" name="kontak_person" type="text" icon="person" :value="old('kontak_person', $supplier->kontak_person)" maxlength="100" />
        </x-field>
        <x-field name="no_hp" label="No. HP" hint="Hanya angka, 9 sampai 15 digit.">
            <x-text-input id="no_hp" name="no_hp" type="tel" inputmode="numeric" maxlength="15" pattern="[0-9]*" autocomplete="tel"
                          :value="old('no_hp', $supplier->no_hp)" required
                          x-init="$el.value = $el.value.replace(/\D/g, '')"
                          x-on:input="$event.target.value = $event.target.value.replace(/\D/g, '')" />
        </x-field>
    </div>

    <x-field name="alamat" label="Alamat (opsional)">
        <x-textarea-input id="alamat" name="alamat">{{ old('alamat', $supplier->alamat) }}</x-textarea-input>
    </x-field>
    <x-field name="catatan" label="Catatan (opsional)">
        <x-textarea-input id="catatan" name="catatan">{{ old('catatan', $supplier->catatan) }}</x-textarea-input>
    </x-field>

    <x-checkbox name="is_active" label="Supplier aktif" :checked="$supplier->exists ? (bool) $supplier->is_active : true" />

    <div class="flex flex-wrap gap-3">
        <x-submit-button class="w-auto" icon="save" loading-text="Menyimpan…">Simpan</x-submit-button>
        <x-btn :href="route('suppliers.index')" variant="secondary">Batal</x-btn>
    </div>
</form>
