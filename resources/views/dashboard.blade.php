@php
    $user    = Auth::user();
    $kelola  = $user->hasAnyRole(['admin', 'manajer']);
    $kasirOk = $user->hasAnyRole(['admin', 'manajer', 'kasir']);
    $jam     = now('Asia/Jakarta')->hour;
    $sapaan  = $jam < 11 ? 'Selamat pagi' : ($jam < 15 ? 'Selamat siang' : ($jam < 18 ? 'Selamat sore' : 'Selamat malam'));
    $nama    = \Illuminate\Support\Str::before($user->name, ' ');
@endphp

<x-app-layout>
    {{-- Hero --}}
    <div class="mb-6 flex flex-col justify-between gap-4 rounded-tm-md bg-surface-container-low p-5 shadow-sm sm:p-6 lg:flex-row lg:items-center">
        <div class="max-w-2xl">
            <div class="mb-1 inline-flex items-center gap-1.5 text-label-md text-primary">
                <span class="h-2 w-2 animate-ping rounded-full bg-secondary-container"></span>
                <span class="uppercase tracking-wider">{{ now('Asia/Jakarta')->translatedFormat('l, d F Y') }}</span>
            </div>
            <h1 class="text-headline-xl-mobile text-on-surface sm:text-headline-lg">{{ $sapaan }}, {{ $nama }}</h1>
            <p class="mt-1 text-body-md text-on-surface-variant">
                @if ($kelola)
                    Pantau produksi, stok, dan keuangan pabrik tahu dari satu tempat.
                @elseif ($kasirOk)
                    Catat transaksi penjualan dan cek stok produk yang tersedia.
                @else
                    Lihat jadwal produksi dan kerjakan tahapan batch yang sedang berjalan.
                @endif
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if ($kelola)
                <x-btn :href="route('produksi.create')" icon="add">Jadwalkan produksi</x-btn>
            @endif
            @if ($kasirOk)
                <x-btn :href="route('penjualan.create')" :variant="$kelola ? 'secondary' : 'primary'" icon="point_of_sale">Transaksi baru</x-btn>
            @endif
            @unless ($kasirOk)
                <x-btn :href="route('produksi.index')" icon="precision_manufacturing">Lihat produksi</x-btn>
            @endunless
        </div>
    </div>

    {{-- Ringkasan angka --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @isset($penjualanHariIni)
            <x-stat-card icon="point_of_sale" label="Penjualan hari ini" tone="primary" :href="route('penjualan.index', ['tanggal' => today()->toDateString()])">
                <x-money :value="$penjualanHariIni" />
                <x-slot:footer><span>Nota selesai hari ini</span><x-icon name="chevron_right" class="text-[16px]" /></x-slot:footer>
            </x-stat-card>
        @endisset

        @if ($kelola)
            <x-stat-card icon="payments" label="Pendapatan bulan ini" tone="success">
                <x-money :value="$pendapatan" />
                <x-slot:footer><span>{{ now('Asia/Jakarta')->translatedFormat('F Y') }}</span></x-slot:footer>
            </x-stat-card>
            <x-stat-card icon="account_balance_wallet" label="Pengeluaran bulan ini">
                <x-money :value="$pengeluaran" />
                <x-slot:footer><span>Pembelian bahan baku dan lainnya</span></x-slot:footer>
            </x-stat-card>
            <x-stat-card :icon="$laba < 0 ? 'trending_down' : 'trending_up'" label="Laba bulan ini" :tone="$laba < 0 ? 'danger' : 'success'">
                <x-money :value="$laba" />
                <x-slot:footer>
                    <span>{{ (float) $pendapatan > 0 ? 'Margin ' . rtrim(rtrim(number_format($laba / $pendapatan * 100, 1, ',', '.'), '0'), ',') . '%' : 'Belum ada pendapatan' }}</span>
                </x-slot:footer>
            </x-stat-card>
        @else
            <x-stat-card icon="category" label="Produk aktif" tone="neutral">
                {{ $produkAktif->count() }} produk
                <x-slot:footer><span>Siap dijual</span></x-slot:footer>
            </x-stat-card>
        @endif
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-3">
        {{-- Stok produk (tabel) --}}
        <x-card class="!p-0 overflow-hidden lg:col-span-2">
            <div class="flex items-center justify-between px-5 pb-3 pt-5 sm:px-6 sm:pt-6">
                <div>
                    <h2 class="text-headline-sm font-bold text-on-surface">Stok produk</h2>
                    <p class="text-body-sm text-on-surface-variant">Produk aktif, harga jual, dan persediaan.</p>
                </div>
                @if ($kasirOk)
                    <a href="{{ route('stok-produk.index') }}" class="inline-flex items-center gap-1 text-label-md text-primary hover:underline">Kelola <x-icon name="arrow_forward" class="text-[16px]" /></a>
                @endif
            </div>
            <div class="hidden grid-cols-[minmax(0,2fr)_1fr_1fr] gap-3 bg-surface-container-low px-5 py-2.5 text-label-sm uppercase tracking-wider text-on-surface-variant sm:px-6 md:grid">
                <span>Produk</span><span>Harga jual</span><span class="text-right">Stok</span>
            </div>
            <div class="divide-y divide-surface-container-low">
                @forelse ($produkAktif as $p)
                    <div class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-3 px-5 py-3.5 sm:px-6 md:grid-cols-[minmax(0,2fr)_1fr_1fr]">
                        <div class="min-w-0">
                            <div class="truncate text-label-lg text-on-surface">{{ $p->nama }}</div>
                            <div class="text-body-sm text-on-surface-variant md:hidden"><x-money :value="$p->harga_jual" /> / {{ $p->satuan }}</div>
                        </div>
                        <div class="hidden text-body-md text-on-surface md:block"><x-money :value="$p->harga_jual" /> <span class="text-on-surface-variant">/ {{ $p->satuan }}</span></div>
                        <div class="text-right">
                            <x-badge :tone="(float) $p->stok > 0 ? 'success' : 'danger'" :dot="true"><x-qty :value="$p->stok" :unit="$p->satuan" /></x-badge>
                        </div>
                    </div>
                @empty
                    <div class="p-6"><x-empty-state icon="category" title="Belum ada produk aktif" /></div>
                @endforelse
            </div>
        </x-card>

        <div class="flex flex-col gap-4">
            @if ($kelola)
                <x-card title="Produksi" description="Status batch saat ini.">
                    <div class="grid grid-cols-2 gap-3">
                        <a href="{{ route('produksi.index', ['status' => 'berjalan']) }}" class="rounded-tm-md bg-surface-container-low p-3 transition-colors hover:bg-surface-container focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary">
                            <div class="text-label-sm uppercase tracking-wider text-on-surface-variant">Berjalan</div>
                            <div class="text-headline-lg font-extrabold text-on-surface">{{ $produksiBerjalan }}</div>
                        </a>
                        <a href="{{ route('produksi.index', ['status' => 'terjadwal']) }}" class="rounded-tm-md bg-surface-container-low p-3 transition-colors hover:bg-surface-container focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary">
                            <div class="text-label-sm uppercase tracking-wider text-on-surface-variant">Terjadwal</div>
                            <div class="text-headline-lg font-extrabold text-on-surface">{{ $produksiTerjadwal }}</div>
                        </a>
                    </div>
                </x-card>

                <x-card title="Bahan baku menipis" description="Segera beli atau catat stok masuk.">
                    @forelse ($stokMenipis as $b)
                        <a href="{{ route('bahan-baku.show', $b) }}" class="flex items-center justify-between gap-3 py-2.5 hover:bg-surface-container-low/60">
                            <div class="min-w-0">
                                <div class="truncate text-label-lg text-on-surface">{{ $b->nama }}</div>
                                <div class="text-body-sm text-on-surface-variant">Minimum <x-qty :value="$b->stok_minimum" :unit="$b->satuan" /></div>
                            </div>
                            <x-badge tone="danger" :dot="true"><x-qty :value="$b->stok_saat_ini" :unit="$b->satuan" /></x-badge>
                        </a>
                    @empty
                        <div class="flex items-center gap-2 rounded-tm-md bg-tertiary-fixed/50 px-3 py-3 text-label-md text-on-tertiary-fixed">
                            <x-icon name="check_circle" class="text-[20px] text-primary" /> Semua stok aman
                        </div>
                    @endforelse
                </x-card>
            @endif
        </div>
    </div>
</x-app-layout>
