<x-app-layout>
    <x-page-header title="Produk" eyebrow="Gudang" subtitle="Produk tahu yang dijual dan harga jualnya.">
        <x-slot:actions>
            <x-btn :href="route('produk.create')" icon="add">Tambah produk</x-btn>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" action="{{ route('produk.index') }}" class="mb-4 flex gap-2 lg:w-96">
        <div class="flex-1"><x-text-input name="q" type="search" icon="search" :value="request('q')" placeholder="Cari nama atau kode produk" class="!bg-surface-container-lowest shadow-sm" /></div>
        <x-btn variant="secondary">Cari</x-btn>
    </form>

    <x-card class="!p-0 overflow-hidden">
        <x-table-head cols="md:grid-cols-[minmax(0,2fr)_1.2fr_1fr_1fr_9rem]">
            <span>Produk</span><span>Harga jual</span><span>Stok</span><span>Status</span><span class="text-right">Aksi</span>
        </x-table-head>

        <div class="divide-y divide-surface-container-low">
            @forelse ($produk as $p)
                <div class="grid items-center gap-3 px-5 py-3.5 sm:px-6 md:grid-cols-[minmax(0,2fr)_1.2fr_1fr_1fr_9rem]">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-tm-md bg-surface-container text-secondary"><x-icon name="category" class="text-[20px]" /></span>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="truncate text-label-lg text-on-surface">{{ $p->nama }}</span>
                                @unless ($p->is_active)<x-badge class="md:hidden">Nonaktif</x-badge>@endunless
                            </div>
                            <div class="text-body-sm text-on-surface-variant">{{ $p->kode }}<span class="md:hidden"> · <x-money :value="$p->harga_jual" /> / {{ $p->satuan }} · Stok <x-qty :value="$p->stok" :unit="$p->satuan" /></span></div>
                        </div>
                    </div>
                    <div class="hidden text-body-md text-on-surface md:block"><x-money :value="$p->harga_jual" /> <span class="text-on-surface-variant">/ {{ $p->satuan }}</span></div>
                    <div class="hidden text-label-lg font-bold text-on-surface md:block"><x-qty :value="$p->stok" :unit="$p->satuan" /></div>
                    <div class="hidden md:block"><x-badge :tone="$p->is_active ? 'success' : 'neutral'" :dot="true">{{ $p->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge></div>
                    <div class="flex gap-2 md:justify-end">
                        <x-btn :href="route('produk.edit', $p)" variant="secondary" size="sm" icon="edit">Ubah</x-btn>
                        <form method="POST" action="{{ route('produk.destroy', $p) }}" onsubmit="return confirm('Hapus produk ini?')">
                            @csrf @method('DELETE')
                            <x-btn variant="ghost" size="sm" icon="delete" aria-label="Hapus {{ $p->nama }}">Hapus</x-btn>
                        </form>
                    </div>
                </div>
            @empty
                <div class="p-6"><x-empty-state icon="category" title="Belum ada produk" text="Tambahkan produk agar bisa dijual dan dicatat hasil produksinya.">
                    <x-btn :href="route('produk.create')" icon="add">Tambah produk</x-btn>
                </x-empty-state></div>
            @endforelse
        </div>

        {{ $produk->links() }}
    </x-card>
</x-app-layout>
