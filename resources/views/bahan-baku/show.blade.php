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
                        <x-rupiah-input id="harga_satuan" name="harga_satuan" :value="old('harga_satuan')" required />
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

        <x-card title="Koreksi stok (stok opname)"
                description="Gunakan jika jumlah barang di gudang berbeda dengan stok di sistem. Isi jumlah hasil hitung fisik; sistem otomatis mencatat selisihnya.">
            <form method="POST" action="{{ route('bahan-baku.opname', $bahanBaku) }}" class="flex flex-col gap-4"
                  x-data="{ loading: false, fisik: '{{ old('stok_fisik') }}', sistem: {{ (float) $bahanBaku->stok_saat_ini }} }"
                  x-on:submit="loading = true" x-on:pageshow.window="loading = false">
                @csrf
                <div class="flex items-center justify-between rounded-tm-md bg-surface-container-low px-3 py-2.5 text-body-md">
                    <span class="text-on-surface-variant">Stok di sistem saat ini</span>
                    <span class="text-label-lg text-on-surface"><x-qty :value="$bahanBaku->stok_saat_ini" :unit="$bahanBaku->satuan" /></span>
                </div>
                <x-field name="stok_fisik" label="Stok fisik hasil hitung ({{ $bahanBaku->satuan }})">
                    <x-text-input id="stok_fisik" name="stok_fisik" type="number" step="any" min="0" inputmode="decimal" x-model="fisik" :value="old('stok_fisik')" required />
                </x-field>
                <p x-show="fisik !== '' && ! isNaN(Number(fisik))" x-cloak class="flex items-center justify-between rounded-tm-md bg-surface-container px-3 py-2 text-body-sm text-on-surface-variant">
                    <span>Selisih terhadap sistem</span>
                    <span class="text-label-lg font-bold"
                          x-bind:class="Number(fisik) - sistem > 0 ? 'text-tertiary' : (Number(fisik) - sistem < 0 ? 'text-error' : 'text-on-surface-variant')"
                          x-text="(Number(fisik) - sistem > 0 ? '+' : (Number(fisik) - sistem < 0 ? '−' : '')) + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(Math.abs(Number(fisik) - sistem)) + ' {{ $bahanBaku->satuan }}'"></span>
                </p>
                <x-field name="catatan" for="catatan_opname" label="Alasan atau catatan">
                    <x-text-input id="catatan_opname" name="catatan" type="text" :value="old('catatan')" maxlength="255" required placeholder="Contoh: hitung ulang gudang" />
                </x-field>
                <x-submit-button class="w-auto self-start" variant="secondary" icon="fact_check" loading-text="Menyimpan…">Simpan koreksi stok</x-submit-button>
            </form>
        </x-card>
    </div>

    <x-card title="Riwayat stok" class="mt-4">
        @forelse ($riwayat as $r)
            <div class="flex items-start justify-between gap-3 border-b border-outline-variant/40 py-3 last:border-0">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-badge :tone="$r->tipe->value === 'masuk' ? 'success' : ($r->tipe->value === 'keluar' ? 'danger' : 'info')" :dot="true">{{ ['masuk' => 'Masuk', 'keluar' => 'Keluar', 'penyesuaian' => 'Koreksi stok'][$r->tipe->value] ?? ucfirst($r->tipe->value) }}</x-badge>
                        <span class="text-label-lg text-on-surface">
                            @if ($r->tipe->value === 'penyesuaian')<span class="font-normal text-on-surface-variant">Stok fisik</span> @endif<x-qty :value="$r->jumlah" :unit="$bahanBaku->satuan" />
                        </span>
                    </div>
                    <div class="mt-1 text-body-sm text-on-surface-variant">
                        {{ $r->tanggal->translatedFormat('d M Y') }}
                        @if ($r->supplier) · {{ $r->supplier->nama }} @endif
                        @if ($r->user) · {{ $r->user->name }} @endif
                    </div>
                    @if ($r->catatan)<div class="text-body-sm text-on-surface-variant">{{ $r->catatan }}</div>@endif
                </div>
                <div class="shrink-0 text-right text-body-sm text-on-surface-variant">
                    <div class="text-label-lg"><span class="font-normal text-on-surface-variant">Selisih</span> <x-selisih :sebelum="$r->stok_sebelum" :sesudah="$r->stok_sesudah" /> {{ $bahanBaku->satuan }}</div>
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
