@php $menipis = (float) $bahanBaku->stok_minimum > 0 && (float) $bahanBaku->stok_saat_ini <= (float) $bahanBaku->stok_minimum; @endphp

<x-app-layout>
    <x-page-header :title="$bahanBaku->nama" eyebrow="Gudang" :subtitle="$bahanBaku->kode . ' · ' . ucfirst($bahanBaku->kategori)" :back="route('bahan-baku.index')">
        <x-slot:actions>
            <x-btn :href="route('bahan-baku.edit', $bahanBaku)" variant="secondary" icon="edit">Ubah</x-btn>
            <form method="POST" action="{{ route('bahan-baku.destroy', $bahanBaku) }}" onsubmit="return confirm('Hapus bahan baku ini?')">
                @csrf @method('DELETE')
                <x-btn variant="danger" icon="delete">Hapus</x-btn>
            </form>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <x-stat-card icon="grain" label="Stok saat ini" :tone="$menipis ? 'danger' : 'primary'"><x-qty :value="$bahanBaku->stok_saat_ini" :unit="$bahanBaku->satuan" /></x-stat-card>
        <x-stat-card icon="warning" label="Batas minimum"><x-qty :value="$bahanBaku->stok_minimum" :unit="$bahanBaku->satuan" /></x-stat-card>
        <x-stat-card icon="payments" label="Harga terakhir"><x-money :value="$bahanBaku->harga_terakhir" /> / {{ $bahanBaku->satuan }}</x-stat-card>
    </div>

    <div class="mt-2 flex flex-wrap gap-2">
        @if ($menipis)<x-badge tone="danger">Stok menipis</x-badge>@endif
        @unless ($bahanBaku->is_active)<x-badge>Nonaktif</x-badge>@endunless
    </div>
    @if ($bahanBaku->keterangan)
        <p class="mt-2 text-body-md text-on-surface-variant">{{ $bahanBaku->keterangan }}</p>
    @endif

    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <x-card title="Stok masuk" description="Pembelian dari supplier. Otomatis tercatat sebagai pengeluaran.">
            <form method="POST" action="{{ route('bahan-baku.stok-masuk', $bahanBaku) }}" class="flex flex-col gap-4"
                  x-data="{ loading: false }" x-on:submit="loading = true" x-on:pageshow.window="loading = false">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field name="jumlah" label="Jumlah ({{ $bahanBaku->satuan }})">
                        <x-text-input id="jumlah" name="jumlah" type="number" step="any" min="0" inputmode="decimal" :value="old('jumlah')" required />
                    </x-field>
                    <x-field name="harga_satuan" label="Harga per {{ $bahanBaku->satuan }}">
                        <x-text-input id="harga_satuan" name="harga_satuan" type="number" step="any" min="0" inputmode="decimal" :value="old('harga_satuan')" required />
                    </x-field>
                    <x-field name="supplier_id" label="Supplier (opsional)">
                        <x-select-input id="supplier_id" name="supplier_id">
                            <option value="">Tanpa supplier</option>
                            @foreach ($suppliers as $s)
                                <option value="{{ $s->id }}" @selected((string) old('supplier_id') === (string) $s->id)>{{ $s->nama }}</option>
                            @endforeach
                        </x-select-input>
                    </x-field>
                    <x-field name="tanggal" label="Tanggal">
                        <x-text-input id="tanggal" name="tanggal" type="date" :value="old('tanggal', now()->toDateString())" required />
                    </x-field>
                </div>
                <x-field name="catatan" label="Catatan (opsional)">
                    <x-text-input id="catatan" name="catatan" type="text" :value="old('catatan')" maxlength="255" />
                </x-field>
                <x-submit-button class="w-auto self-start" icon="add" loading-text="Menyimpan…">Catat stok masuk</x-submit-button>
            </form>
        </x-card>

        <x-card title="Stok opname" description="Samakan stok sistem dengan hasil hitung fisik.">
            <form method="POST" action="{{ route('bahan-baku.opname', $bahanBaku) }}" class="flex flex-col gap-4"
                  x-data="{ loading: false }" x-on:submit="loading = true" x-on:pageshow.window="loading = false">
                @csrf
                <x-field name="stok_fisik" label="Stok fisik ({{ $bahanBaku->satuan }})">
                    <x-text-input id="stok_fisik" name="stok_fisik" type="number" step="any" min="0" inputmode="decimal" :value="old('stok_fisik')" required />
                </x-field>
                <x-field name="catatan" for="catatan_opname" label="Alasan atau catatan">
                    <x-text-input id="catatan_opname" name="catatan" type="text" :value="old('catatan')" maxlength="255" required placeholder="Contoh: hitung ulang gudang" />
                </x-field>
                <x-submit-button class="w-auto self-start" variant="secondary" icon="fact_check" loading-text="Menyimpan…">Simpan opname</x-submit-button>
            </form>
        </x-card>
    </div>

    <x-card title="Riwayat stok" class="mt-4">
        @forelse ($riwayat as $r)
            <div class="flex items-start justify-between gap-3 border-b border-outline-variant/40 py-3 last:border-0">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-badge :tone="$r->tipe->value === 'masuk' ? 'success' : ($r->tipe->value === 'keluar' ? 'danger' : 'info')">{{ ucfirst($r->tipe->value) }}</x-badge>
                        <span class="text-label-lg text-on-surface"><x-qty :value="$r->jumlah" :unit="$bahanBaku->satuan" /></span>
                    </div>
                    <div class="mt-1 text-body-sm text-on-surface-variant">
                        {{ $r->tanggal->translatedFormat('d M Y') }}
                        @if ($r->supplier) · {{ $r->supplier->nama }} @endif
                        @if ($r->user) · {{ $r->user->name }} @endif
                    </div>
                    @if ($r->catatan)<div class="text-body-sm text-on-surface-variant">{{ $r->catatan }}</div>@endif
                </div>
                <div class="shrink-0 text-right text-body-sm text-on-surface-variant">
                    <x-qty :value="$r->stok_sebelum" /> → <span class="text-on-surface"><x-qty :value="$r->stok_sesudah" /></span>
                    @if ((float) $r->harga_satuan > 0)<div><x-money :value="$r->harga_satuan" /></div>@endif
                </div>
            </div>
        @empty
            <x-empty-state icon="history" title="Belum ada riwayat stok" />
        @endforelse
        {{ $riwayat->links() }}
    </x-card>
</x-app-layout>
