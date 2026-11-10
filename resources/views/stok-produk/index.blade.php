@php $bolehOpname = Auth::user()->hasAnyRole(['admin', 'manajer']); @endphp

<x-app-layout>
    <x-page-header title="Stok produk" eyebrow="Gudang" subtitle="Persediaan tahu jadi dan riwayat perubahannya." />

    <x-card class="!p-0 overflow-hidden">
        <x-table-head cols="md:grid-cols-[minmax(0,2fr)_1.2fr_1fr_1fr_8rem]">
            <span>Produk</span><span>Harga jual</span><span>Stok</span><span>Status</span><span class="text-right">Aksi</span>
        </x-table-head>

        <div class="divide-y divide-surface-container-low">
            @forelse ($produk as $p)
                @php $habis = (float) $p->stok <= 0; @endphp
                <div x-data="{ buka: {{ $errors->has('stok_fisik') && (string) old('produk_id') === (string) $p->id ? 'true' : 'false' }}, loading: false }"
                     x-on:submit="loading = true" x-on:pageshow.window="loading = false" class="{{ $habis ? 'bg-error-container/15' : '' }}">
                    <div class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-3 px-5 py-3.5 sm:px-6 md:grid-cols-[minmax(0,2fr)_1.2fr_1fr_1fr_8rem]">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-tm-md {{ $habis ? 'bg-error-container text-error' : 'bg-surface-container text-primary' }}"><x-icon :name="$habis ? 'warning' : 'inventory_2'" class="text-[20px]" /></span>
                            <div class="min-w-0">
                                <div class="truncate text-label-lg text-on-surface">{{ $p->nama }}</div>
                                <div class="text-body-sm text-on-surface-variant">{{ $p->kode }}<span class="md:hidden"> · <x-money :value="$p->harga_jual" /></span>@unless ($p->is_active) · Nonaktif @endunless</div>
                            </div>
                        </div>
                        <div class="hidden text-body-md text-on-surface md:block"><x-money :value="$p->harga_jual" /> <span class="text-on-surface-variant">/ {{ $p->satuan }}</span></div>
                        <div class="text-right md:text-left"><span class="text-label-lg font-bold {{ $habis ? 'text-error' : 'text-on-surface' }}"><x-qty :value="$p->stok" :unit="$p->satuan" /></span></div>
                        <div class="hidden md:block">
                            @if ($habis)<x-badge tone="danger" :dot="true">Habis</x-badge>
                            @elseif (! $p->is_active)<x-badge>Nonaktif</x-badge>
                            @else<x-badge tone="success" :dot="true">Tersedia</x-badge>@endif
                        </div>
                        <div class="col-span-2 md:col-span-1 md:text-right">
                            @if ($bolehOpname)
                                <button type="button" x-on:click="buka = ! buka" class="inline-flex items-center gap-1 rounded-tm-md px-2.5 py-1.5 text-label-md text-secondary transition-colors hover:bg-surface-container-high focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary">
                                    <x-icon name="fact_check" class="text-[18px]" /> Koreksi stok
                                </button>
                            @endif
                        </div>
                    </div>

                    @if ($bolehOpname)
                        <form x-show="buka" x-cloak method="POST" action="{{ route('stok-produk.opname', $p) }}" class="grid gap-3 bg-surface-container-low px-5 py-4 sm:px-6 md:grid-cols-[1fr_2fr_auto] md:items-end"
                              x-data="{ fisik: '{{ (string) old('produk_id') === (string) $p->id ? old('stok_fisik') : '' }}', sistem: {{ (float) $p->stok }} }">
                            @csrf
                            <input type="hidden" name="produk_id" value="{{ $p->id }}">
                            <div class="md:col-span-3">
                                <h3 class="text-label-lg text-on-surface">Koreksi stok (stok opname)</h3>
                                <p class="text-body-sm text-on-surface-variant">Gunakan jika jumlah barang di gudang berbeda dengan stok di sistem. Isi jumlah hasil hitung fisik; sistem otomatis mencatat selisihnya. Stok di sistem saat ini: <strong class="text-on-surface"><x-qty :value="$p->stok" :unit="$p->satuan" /></strong>.</p>
                            </div>
                            <x-field name="stok_fisik" :for="'stok_fisik_' . $p->id" label="Stok fisik hasil hitung ({{ $p->satuan }})">
                                <x-text-input :id="'stok_fisik_' . $p->id" name="stok_fisik" type="number" step="any" min="0" inputmode="decimal" x-model="fisik" :value="(string) old('produk_id') === (string) $p->id ? old('stok_fisik') : ''" required class="!bg-surface-container-lowest" />
                                <p x-show="fisik !== '' && ! isNaN(Number(fisik))" x-cloak class="text-body-sm text-on-surface-variant">
                                    Selisih:
                                    <span class="font-bold"
                                          x-bind:class="Number(fisik) - sistem > 0 ? 'text-tertiary' : (Number(fisik) - sistem < 0 ? 'text-error' : '')"
                                          x-text="(Number(fisik) - sistem > 0 ? '+' : (Number(fisik) - sistem < 0 ? '−' : '')) + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(Math.abs(Number(fisik) - sistem)) + ' {{ $p->satuan }}'"></span>
                                </p>
                            </x-field>
                            <x-field name="catatan" :for="'catatan_' . $p->id" label="Alasan atau catatan">
                                <x-text-input :id="'catatan_' . $p->id" name="catatan" type="text" maxlength="255" :value="(string) old('produk_id') === (string) $p->id ? old('catatan') : ''" required placeholder="Contoh: hitung ulang etalase" class="!bg-surface-container-lowest" />
                            </x-field>
                            <x-submit-button class="w-auto" icon="save" loading-text="Menyimpan…">Simpan koreksi</x-submit-button>
                        </form>
                    @endif
                </div>
            @empty
                <div class="p-6"><x-empty-state icon="category" title="Belum ada produk" text="Produk dibuat oleh admin atau manajer di menu Produk." /></div>
            @endforelse
        </div>
    </x-card>

    <x-card class="mt-6 !p-0 overflow-hidden">
        <div class="px-5 pb-3 pt-5 sm:px-6 sm:pt-6">
            <h2 class="text-headline-sm font-bold text-on-surface">Riwayat stok produk</h2>
            <p class="text-body-sm text-on-surface-variant">Perubahan stok dari produksi, penjualan, dan opname.</p>
        </div>
        <x-table-head cols="md:grid-cols-[minmax(0,1.5fr)_1fr_1fr_0.9fr_1.1fr_1.3fr]">
            <span>Produk</span><span>Tipe</span><span>Jumlah</span><span>Selisih</span><span>Stok</span><span>Waktu dan petugas</span>
        </x-table-head>
        <div class="divide-y divide-surface-container-low">
            @forelse ($riwayat as $r)
                @php
                    $tone = $r->tipe->value === 'masuk' ? 'success' : ($r->tipe->value === 'keluar' ? 'danger' : 'info');
                    $namaTipe = ['masuk' => 'Masuk', 'keluar' => 'Keluar', 'penyesuaian' => 'Koreksi stok'][$r->tipe->value] ?? ucfirst($r->tipe->value);
                @endphp
                <div class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-3 px-5 py-3.5 sm:px-6 md:grid-cols-[minmax(0,1.5fr)_1fr_1fr_0.9fr_1.1fr_1.3fr]">
                    <div class="min-w-0">
                        <div class="truncate text-label-lg text-on-surface">{{ $r->produk?->nama }}</div>
                        <div class="text-body-sm text-on-surface-variant md:hidden">{{ $r->created_at->translatedFormat('d M Y, H:i') }}@if ($r->user) · {{ $r->user->name }}@endif</div>
                        @if ($r->catatan)<div class="text-body-sm text-on-surface-variant">{{ $r->catatan }}</div>@endif
                    </div>
                    <div class="hidden md:block"><x-badge :tone="$tone" :dot="true">{{ $namaTipe }}</x-badge></div>
                    <div class="hidden text-body-md text-on-surface md:block">
                        @if ($r->tipe->value === 'penyesuaian')<span class="text-body-sm text-on-surface-variant">Stok fisik</span> @endif<x-qty :value="$r->jumlah" :unit="$r->produk?->satuan" />
                    </div>
                    <div class="hidden text-label-lg md:block"><x-selisih :sebelum="$r->stok_sebelum" :sesudah="$r->stok_sesudah" /></div>
                    <div class="hidden text-body-md text-on-surface-variant md:block"><x-qty :value="$r->stok_sebelum" /> → <span class="text-on-surface"><x-qty :value="$r->stok_sesudah" /></span></div>
                    <div class="hidden text-body-sm text-on-surface-variant md:block">{{ $r->created_at->translatedFormat('d M Y, H:i') }}@if ($r->user)<div>{{ $r->user->name }}</div>@endif</div>
                    {{-- Ringkasan untuk HP --}}
                    <div class="text-right md:hidden">
                        <x-badge :tone="$tone" :dot="true">{{ $namaTipe }}</x-badge>
                        <div class="mt-0.5 text-label-lg"><x-selisih :sebelum="$r->stok_sebelum" :sesudah="$r->stok_sesudah" /> <span class="text-body-sm font-normal text-on-surface-variant">{{ $r->produk?->satuan }}</span></div>
                    </div>
                </div>
            @empty
                <div class="p-6"><x-empty-state icon="history" title="Belum ada riwayat" /></div>
            @endforelse
        </div>
        {{ $riwayat->links() }}
    </x-card>
</x-app-layout>
