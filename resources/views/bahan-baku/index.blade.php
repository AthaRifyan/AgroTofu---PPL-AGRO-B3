@php
    $menipisAktif = request()->boolean('menipis');
    $tautan = fn ($menipis) => route('bahan-baku.index', array_filter(['q' => request('q'), 'menipis' => $menipis ? 1 : null]));
@endphp

<x-app-layout>
    <x-page-header title="Manajemen bahan baku" eyebrow="Gudang" subtitle="Pantau ketersediaan bahan baku dan bahan pendukung produksi.">
        <x-slot:actions>
            <x-btn :href="route('bahan-baku.create')" icon="add">Tambah bahan</x-btn>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-1.5 overflow-x-auto">
            <a href="{{ $tautan(false) }}" class="shrink-0 rounded-tm-md px-3.5 py-1.5 text-label-md transition-colors {{ ! $menipisAktif ? 'bg-surface-container-high font-semibold text-on-surface' : 'bg-surface-container-lowest text-on-surface-variant hover:bg-surface-container' }}">Semua bahan</a>
            <a href="{{ $tautan(true) }}" class="shrink-0 rounded-tm-md px-3.5 py-1.5 text-label-md transition-colors {{ $menipisAktif ? 'bg-error-container font-semibold text-on-error-container' : 'bg-surface-container-lowest text-on-surface-variant hover:bg-surface-container' }}">Stok menipis</a>
        </div>
        <form method="GET" action="{{ route('bahan-baku.index') }}" class="flex gap-2 lg:w-96">
            @if ($menipisAktif)<input type="hidden" name="menipis" value="1">@endif
            <div class="flex-1"><x-text-input name="q" type="search" icon="search" :value="request('q')" placeholder="Cari nama bahan atau kode" class="!bg-surface-container-lowest shadow-sm" /></div>
            <x-btn variant="secondary">Cari</x-btn>
        </form>
    </div>

    <x-card class="!p-0 overflow-hidden">
        <x-table-head cols="md:grid-cols-[minmax(0,2.4fr)_1fr_1.2fr_1fr_1fr_1.5rem]">
            <span>Bahan dan kode</span><span>Kategori</span><span>Stok saat ini</span><span>Stok min.</span><span>Status</span><span></span>
        </x-table-head>

        <div class="divide-y divide-surface-container-low">
            @forelse ($bahanBaku as $b)
                @php $menipis = (float) $b->stok_minimum > 0 && (float) $b->stok_saat_ini <= (float) $b->stok_minimum; @endphp
                <a href="{{ route('bahan-baku.show', $b) }}"
                   class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-3 px-5 py-3.5 transition-colors hover:bg-surface-container-low/60 focus:outline-none focus-visible:bg-surface-container-low sm:px-6 md:grid-cols-[minmax(0,2.4fr)_1fr_1.2fr_1fr_1fr_1.5rem] {{ $menipis ? 'bg-error-container/15' : '' }}">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-tm-md {{ $menipis ? 'bg-error-container text-error' : 'bg-surface-container text-primary' }}"><x-icon :name="$menipis ? 'warning' : 'grain'" class="text-[20px]" /></span>
                        <div class="min-w-0">
                            <div class="truncate text-label-lg text-on-surface">{{ $b->nama }}</div>
                            <div class="text-body-sm text-on-surface-variant">{{ $b->kode }}</div>
                        </div>
                    </div>
                    <div class="hidden md:block"><x-badge>{{ ucfirst($b->kategori) }}</x-badge></div>
                    <div class="text-right md:text-left">
                        <span class="text-label-lg font-bold {{ $menipis ? 'text-error' : 'text-on-surface' }}"><x-qty :value="$b->stok_saat_ini" :unit="$b->satuan" /></span>
                        <div class="mt-0.5 flex justify-end gap-1 md:hidden">
                            @if ($menipis)<x-badge tone="danger" :dot="true">Menipis</x-badge>@endif
                            @unless ($b->is_active)<x-badge>Nonaktif</x-badge>@endunless
                        </div>
                    </div>
                    <div class="hidden text-body-md text-on-surface-variant md:block"><x-qty :value="$b->stok_minimum" :unit="$b->satuan" /></div>
                    <div class="hidden md:block">
                        @if (! $b->is_active)<x-badge>Nonaktif</x-badge>
                        @elseif ($menipis)<x-badge tone="danger" :dot="true">Menipis</x-badge>
                        @else<x-badge tone="success" :dot="true">Aman</x-badge>@endif
                    </div>
                    <div class="hidden text-outline md:block"><x-icon name="chevron_right" class="text-[20px]" /></div>
                </a>
            @empty
                <div class="p-6"><x-empty-state icon="grain" title="Belum ada bahan baku" text="Tidak ada data yang cocok. Tambahkan bahan baku pertama Anda.">
                    <x-btn :href="route('bahan-baku.create')" icon="add">Tambah bahan</x-btn>
                </x-empty-state></div>
            @endforelse
        </div>

        {{ $bahanBaku->links() }}
    </x-card>
</x-app-layout>
