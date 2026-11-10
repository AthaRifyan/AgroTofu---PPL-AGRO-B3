{{-- Variabel: $bahanBaku, $action, $method (POST|PUT) --}}
<form method="POST" action="{{ $action }}" class="flex flex-col gap-5"
      x-data="{ loading: false }" x-on:submit="loading = true" x-on:pageshow.window="loading = false">
    @csrf
    @if ($method !== 'POST') @method($method) @endif

    <div class="grid gap-5 sm:grid-cols-2">
        <x-field name="kode" label="Kode">
            <x-text-input id="kode" name="kode" type="text" :value="old('kode', $bahanBaku->kode)" maxlength="20" required autofocus />
        </x-field>
        <x-field name="nama" label="Nama bahan">
            <x-text-input id="nama" name="nama" type="text" :value="old('nama', $bahanBaku->nama)" maxlength="100" required />
        </x-field>
        <x-field name="kategori" label="Kategori">
            <x-select-input id="kategori" name="kategori" required>
                @foreach (\App\Models\BahanBaku::KATEGORI as $k)
                    <option value="{{ $k }}" @selected(old('kategori', $bahanBaku->kategori) === $k)>{{ ucfirst($k) }}</option>
                @endforeach
            </x-select-input>
        </x-field>
        <x-field name="satuan" label="Satuan">
            <x-select-input id="satuan" name="satuan" required>
                @foreach (\App\Models\BahanBaku::SATUAN as $s)
                    <option value="{{ $s }}" @selected(old('satuan', $bahanBaku->satuan) === $s)>{{ $s }}</option>
                @endforeach
            </x-select-input>
        </x-field>
        <x-field name="stok_minimum" label="Batas stok minimum" hint="Peringatan stok menipis muncul saat stok sama dengan atau di bawah angka ini.">
            <x-text-input id="stok_minimum" name="stok_minimum" type="number" step="any" min="0" inputmode="decimal" :value="old('stok_minimum', $bahanBaku->stok_minimum)" required />
        </x-field>
        <x-field name="harga_terakhir" label="Harga terakhir (per satuan)"
                 :hint="$bahanBaku->exists ? 'Mengubah harga di sini tidak mengubah stok.' : 'Boleh dikosongkan. Harga juga terisi otomatis dari Stok masuk.'">
            <x-rupiah-input id="harga_terakhir" name="harga_terakhir" :value="old('harga_terakhir', $bahanBaku->exists ? $bahanBaku->harga_terakhir : null)" :required="$bahanBaku->exists" />
        </x-field>
        @unless ($bahanBaku->exists)
            <x-field name="stok_awal" label="Stok awal (opsional)" hint="Boleh dikosongkan. Stok selanjutnya diubah lewat Stok masuk atau Opname.">
                <x-text-input id="stok_awal" name="stok_awal" type="number" step="any" min="0" inputmode="decimal" :value="old('stok_awal')" />
            </x-field>
        @endunless
    </div>

    {{-- Koreksi harga pada pembelian terakhir (hanya tampil jika bahan ini pernah dibeli) --}}
    @if ($bahanBaku->exists && $bahanBaku->pembelianTerakhir)
        @php $pb = $bahanBaku->pembelianTerakhir; @endphp
        <div class="rounded-tm-md bg-surface-container-low p-4">
            <x-checkbox name="koreksi_pembelian_terakhir" label="Koreksi juga harga pada pembelian terakhir" :checked="false" />
            <p class="mt-2 text-body-sm text-on-surface-variant">
                Pembelian terakhir:
                <strong class="text-on-surface"><x-qty :value="$pb->jumlah" :unit="$bahanBaku->satuan" /></strong>
                dengan harga <strong class="text-on-surface"><x-money :value="$pb->harga_satuan" /></strong> per {{ $bahanBaku->satuan }}
                @if ($pb->tanggal) pada <strong class="text-on-surface">{{ $pb->tanggal->translatedFormat('d M Y') }}</strong>@endif.
                Centang jika harga pada catatan itu salah. Catatan pembelian dan pengeluarannya ikut dikoreksi mengikuti harga baru di atas.
            </p>
        </div>
    @endif

    <x-field name="keterangan" label="Keterangan (opsional)">
        <x-textarea-input id="keterangan" name="keterangan">{{ old('keterangan', $bahanBaku->keterangan) }}</x-textarea-input>
    </x-field>

    <x-checkbox name="is_active" label="Bahan aktif" :checked="$bahanBaku->exists ? (bool) $bahanBaku->is_active : true" />

    <div class="flex flex-wrap gap-3">
        <x-submit-button class="w-auto" icon="save" loading-text="Menyimpan…">Simpan</x-submit-button>
        <x-btn :href="$bahanBaku->exists ? route('bahan-baku.show', $bahanBaku) : route('bahan-baku.index')" variant="secondary">Batal</x-btn>
    </div>
</form>
