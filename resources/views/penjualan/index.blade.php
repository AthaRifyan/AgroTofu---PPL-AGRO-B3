<x-app-layout>
    <x-page-header title="Riwayat penjualan" eyebrow="Kasir" subtitle="Daftar nota penjualan dan statusnya.">
        <x-slot:actions>
            <x-btn :href="route('penjualan.create')" icon="point_of_sale">Transaksi baru</x-btn>
        </x-slot:actions>
    </x-page-header>

    <x-stat-card icon="payments" label="Total penjualan selesai (sesuai filter)" tone="success" class="mb-4 sm:max-w-md">
        <x-money :value="$total" />
    </x-stat-card>

    <form method="GET" action="{{ route('penjualan.index') }}" class="mb-4 grid gap-2 sm:grid-cols-[1fr_1fr_auto] lg:max-w-2xl">
        <x-text-input name="tanggal" type="date" :value="request('tanggal')" aria-label="Tanggal" class="!bg-surface-container-lowest shadow-sm" />
        <x-select-input name="status" aria-label="Status" class="!bg-surface-container-lowest shadow-sm">
            <option value="">Semua status</option>
            <option value="selesai" @selected(request('status') === 'selesai')>Selesai</option>
            <option value="batal" @selected(request('status') === 'batal')>Batal</option>
        </x-select-input>
        <div class="flex gap-2">
            <x-btn variant="secondary" icon="filter_list">Terapkan</x-btn>
            @if (request()->hasAny(['tanggal', 'status']))
                <x-btn :href="route('penjualan.index')" variant="ghost">Reset</x-btn>
            @endif
        </div>
    </form>

    <x-card class="!p-0 overflow-hidden">
        <x-table-head cols="md:grid-cols-[minmax(0,1.6fr)_1.4fr_1fr_1fr_1.2fr_1fr_1.5rem]">
            <span>Nota</span><span>Waktu</span><span>Kasir</span><span>Metode</span><span class="text-right">Total</span><span>Status</span><span></span>
        </x-table-head>

        <div class="divide-y divide-surface-container-low">
            @forelse ($penjualan as $n)
                <a href="{{ route('penjualan.show', $n) }}"
                   class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-3 px-5 py-3.5 transition-colors hover:bg-surface-container-low/60 focus:outline-none focus-visible:bg-surface-container-low sm:px-6 md:grid-cols-[minmax(0,1.6fr)_1.4fr_1fr_1fr_1.2fr_1fr_1.5rem]">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-tm-md bg-surface-container text-secondary"><x-icon name="receipt_long" class="text-[20px]" /></span>
                        <div class="min-w-0">
                            <div class="truncate text-label-lg text-on-surface">{{ $n->no_nota }}</div>
                            <div class="text-body-sm text-on-surface-variant md:hidden">{{ $n->tanggal->translatedFormat('d M Y, H:i') }} · {{ ucfirst($n->metode_pembayaran) }}</div>
                        </div>
                    </div>
                    <div class="hidden text-body-md text-on-surface md:block">{{ $n->tanggal->translatedFormat('d M Y, H:i') }}</div>
                    <div class="hidden text-body-md text-on-surface md:block">{{ $n->kasir?->name ?? '-' }}</div>
                    <div class="hidden md:block"><x-badge>{{ ucfirst($n->metode_pembayaran) }}</x-badge></div>
                    <div class="text-right">
                        <div class="text-label-lg font-bold text-on-surface"><x-money :value="$n->total" /></div>
                        <div class="mt-0.5 flex justify-end md:hidden"><x-status-badge :status="$n->status" /></div>
                    </div>
                    <div class="hidden md:block"><x-status-badge :status="$n->status" /></div>
                    <div class="hidden text-outline md:block"><x-icon name="chevron_right" class="text-[20px]" /></div>
                </a>
            @empty
                <div class="p-6"><x-empty-state icon="receipt_long" title="Belum ada penjualan" text="Tidak ada nota yang cocok dengan filter ini.">
                    <x-btn :href="route('penjualan.create')" icon="point_of_sale">Transaksi baru</x-btn>
                </x-empty-state></div>
            @endforelse
        </div>

        {{ $penjualan->links() }}
    </x-card>
</x-app-layout>
