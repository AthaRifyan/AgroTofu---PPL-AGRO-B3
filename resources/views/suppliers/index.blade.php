<x-app-layout>
    <x-page-header title="Supplier" eyebrow="Gudang" subtitle="Daftar pemasok bahan baku.">
        <x-slot:actions>
            <x-btn :href="route('suppliers.create')" icon="add">Tambah supplier</x-btn>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" action="{{ route('suppliers.index') }}" class="mb-4 flex gap-2 lg:w-96">
        <div class="flex-1"><x-text-input name="q" type="search" icon="search" :value="request('q')" placeholder="Cari nama atau kode supplier" class="!bg-surface-container-lowest shadow-sm" /></div>
        <x-btn variant="secondary">Cari</x-btn>
    </form>

    <x-card class="!p-0 overflow-hidden">
        <x-table-head cols="md:grid-cols-[minmax(0,2fr)_1.4fr_1fr_9rem]">
            <span>Supplier</span><span>Kontak</span><span>Status</span><span class="text-right">Aksi</span>
        </x-table-head>

        <div class="divide-y divide-surface-container-low">
            @forelse ($suppliers as $s)
                <div class="grid items-center gap-3 px-5 py-3.5 sm:px-6 md:grid-cols-[minmax(0,2fr)_1.4fr_1fr_9rem]">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-tm-md bg-surface-container text-secondary"><x-icon name="local_shipping" class="text-[20px]" /></span>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="truncate text-label-lg text-on-surface">{{ $s->nama }}</span>
                                @unless ($s->is_active)<x-badge class="md:hidden">Nonaktif</x-badge>@endunless
                            </div>
                            <div class="text-body-sm text-on-surface-variant">{{ $s->kode }}<span class="md:hidden">@if ($s->kontak_person) · {{ $s->kontak_person }}@endif @if ($s->no_hp) · {{ $s->no_hp }}@endif</span></div>
                        </div>
                    </div>
                    <div class="hidden text-body-md text-on-surface md:block">
                        {{ $s->kontak_person ?: '-' }}
                        @if ($s->no_hp)<div class="text-body-sm text-on-surface-variant">{{ $s->no_hp }}</div>@endif
                    </div>
                    <div class="hidden md:block">
                        <x-badge :tone="$s->is_active ? 'success' : 'neutral'" :dot="true">{{ $s->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
                    </div>
                    <div class="flex gap-2 md:justify-end">
                        <x-btn :href="route('suppliers.edit', $s)" variant="secondary" size="sm" icon="edit">Ubah</x-btn>
                        <form method="POST" action="{{ route('suppliers.destroy', $s) }}" onsubmit="return confirm('Hapus supplier ini?')">
                            @csrf @method('DELETE')
                            <x-btn variant="ghost" size="sm" icon="delete" aria-label="Hapus {{ $s->nama }}">Hapus</x-btn>
                        </form>
                    </div>
                </div>
            @empty
                <div class="p-6"><x-empty-state icon="local_shipping" title="Belum ada supplier" text="Tambahkan supplier agar stok masuk bisa dicatat per pemasok.">
                    <x-btn :href="route('suppliers.create')" icon="add">Tambah supplier</x-btn>
                </x-empty-state></div>
            @endforelse
        </div>

        {{ $suppliers->links() }}
    </x-card>
</x-app-layout>
