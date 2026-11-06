@php
    $user = Auth::user();

    // Menu sesuai role (hak akses mengikuti routes/web.php)
    $semua = [
        ['grup' => 'Operasional', 'route' => 'dashboard',         'match' => 'dashboard',     'label' => 'Dasbor',      'icon' => 'dashboard',              'roles' => null],
        ['grup' => 'Operasional', 'route' => 'produksi.index',    'match' => 'produksi.*',    'label' => 'Produksi',    'icon' => 'precision_manufacturing', 'roles' => ['admin', 'manajer', 'karyawan', 'kasir']],
        ['grup' => 'Operasional', 'route' => 'penjualan.create',  'match' => 'penjualan.*',   'label' => 'Kasir',       'icon' => 'point_of_sale',          'roles' => ['admin', 'manajer', 'kasir']],
        ['grup' => 'Gudang & data', 'route' => 'stok-produk.index', 'match' => 'stok-produk.*', 'label' => 'Stok produk', 'icon' => 'inventory_2',         'roles' => ['admin', 'manajer', 'kasir']],
        ['grup' => 'Gudang & data', 'route' => 'bahan-baku.index',  'match' => 'bahan-baku.*',  'label' => 'Bahan baku',  'icon' => 'grain',              'roles' => ['admin', 'manajer']],
        ['grup' => 'Gudang & data', 'route' => 'produk.index',      'match' => 'produk.*',      'label' => 'Produk',      'icon' => 'category',           'roles' => ['admin', 'manajer']],
        ['grup' => 'Gudang & data', 'route' => 'suppliers.index',   'match' => 'suppliers.*',   'label' => 'Supplier',    'icon' => 'local_shipping',     'roles' => ['admin', 'manajer']],
    ];

    $menu    = collect($semua)->filter(fn ($m) => $m['roles'] === null || $user->hasAnyRole($m['roles']))->values();
    $grup    = $menu->groupBy('grup');
    $bar     = $menu->take(4);    // tampil di bar bawah (HP)
    $sisa    = $menu->slice(4);   // masuk ke lembar "Menu"
    $peran   = ucfirst((string) $user->getRoleNames()->first());
    $inisial = mb_strtoupper(mb_substr($user->name, 0, 1));
@endphp

<div x-data="{ sheet: false }" x-on:keydown.escape.window="sheet = false">

    {{-- ============ DESKTOP: sidebar ============ --}}
    <aside class="fixed left-0 top-0 z-50 hidden h-full w-72 flex-col justify-between bg-surface-container-lowest shadow-[0_1px_8px_rgba(77,42,0,0.05)] lg:flex">
        <div class="flex min-h-0 flex-1 flex-col">
            <a href="{{ route('dashboard') }}" class="flex h-20 shrink-0 items-center gap-2 bg-surface-container-low px-6">
                <span class="flex h-10 w-10 items-center justify-center rounded-tm-md bg-primary-container text-on-primary-container shadow-[0_2px_8px_-2px_rgba(77,42,0,0.12)]"><x-icon name="grain" class="text-[24px]" /></span>
                <span class="flex flex-col">
                    <span class="text-headline-sm leading-tight tracking-tight text-on-surface">AgroTofu AI</span>
                    <span class="text-label-sm uppercase tracking-wider text-on-surface-variant">Smart Agro-Factory</span>
                </span>
            </a>

            <div class="px-4 py-2">
                <div class="flex items-center justify-between rounded-tm-md bg-surface-container px-3 py-1.5">
                    <span class="flex items-center gap-1.5">
                        <span class="h-2 w-2 animate-pulse rounded-full bg-secondary-container"></span>
                        <span class="text-label-sm text-on-secondary-container">Masuk sebagai</span>
                    </span>
                    <span class="text-label-sm font-bold text-primary">{{ $peran }}</span>
                </div>
            </div>

            <nav class="flex-1 space-y-4 overflow-y-auto px-4 py-2" aria-label="Menu utama">
                @foreach ($grup as $namaGrup => $items)
                    <div>
                        <div class="mb-1 px-3 text-label-sm uppercase tracking-wider text-outline">{{ $namaGrup }}</div>
                        <div class="space-y-1">
                            @foreach ($items as $m)
                                @php $aktif = request()->routeIs($m['match']); @endphp
                                <a href="{{ route($m['route']) }}" @if ($aktif) aria-current="page" @endif
                                   class="flex items-center gap-3 rounded-tm-md px-3 py-2.5 text-label-lg transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary {{ $aktif ? 'bg-primary-container font-semibold text-on-primary-container shadow-[0_2px_6px_-1px_rgba(77,42,0,0.08)]' : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface' }}">
                                    <x-icon :name="$m['icon']" class="text-[20px]" />
                                    <span>{{ $m['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </nav>
        </div>

        <div class="flex flex-col gap-1 bg-surface-container-low p-4">
            <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-tm-md px-3 py-2 text-label-md text-on-surface-variant transition-all hover:bg-surface-container hover:text-on-surface focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary">
                <x-icon name="account_circle" class="text-[20px]" /> <span>Profil saya</span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex w-full items-center gap-3 rounded-tm-md px-3 py-2 text-label-md text-on-surface-variant transition-all hover:bg-surface-container hover:text-on-surface focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary">
                    <x-icon name="logout" class="text-[20px]" /> <span>Keluar</span>
                </button>
            </form>
        </div>
    </aside>

    {{-- ============ DESKTOP: header ============ --}}
    <header class="fixed left-72 right-0 top-0 z-40 hidden h-20 items-center justify-between bg-surface/90 px-8 shadow-[0_1px_8px_rgba(77,42,0,0.04)] backdrop-blur-xl lg:flex">
        <div class="flex items-center gap-2 text-body-md text-on-surface-variant">
            <x-icon name="calendar_today" class="text-[20px] text-primary" />
            <span>{{ now('Asia/Jakarta')->translatedFormat('l, d F Y') }}</span>
        </div>
        <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-tm-md pl-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary">
            <span class="flex flex-col text-right">
                <span class="text-label-lg text-on-surface">{{ $user->name }}</span>
                <span class="text-label-sm text-on-surface-variant">{{ $peran }}</span>
            </span>
            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-primary text-label-md text-on-primary">{{ $inisial }}</span>
        </a>
    </header>

    {{-- ============ HP: top bar ============ --}}
    <header class="sticky top-0 z-30 bg-surface/95 pt-[env(safe-area-inset-top)] shadow-[0_1px_8px_rgba(77,42,0,0.04)] backdrop-blur lg:hidden">
        <div class="flex h-14 items-center justify-between px-4">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-tm-md bg-primary-container text-on-primary-container shadow-sm"><x-icon name="grain" class="text-[22px]" /></span>
                <span class="text-headline-sm tracking-tight text-on-surface">AgroTofu <span class="text-secondary">AI</span></span>
            </a>
            <x-badge tone="info">{{ $peran }}</x-badge>
        </div>
    </header>

    {{-- ============ HP: bar bawah ============ --}}
    <nav class="fixed inset-x-0 bottom-0 z-30 bg-surface/95 pb-[env(safe-area-inset-bottom)] shadow-[0_-1px_8px_rgba(77,42,0,0.06)] backdrop-blur lg:hidden" aria-label="Menu utama">
        <div class="mx-auto flex max-w-lg items-stretch justify-around px-2 py-1.5">
            @foreach ($bar as $m)
                @php $aktif = request()->routeIs($m['match']); @endphp
                <a href="{{ route($m['route']) }}" @if ($aktif) aria-current="page" @endif
                   class="flex min-w-0 flex-1 flex-col items-center gap-0.5 rounded-tm-md px-1 py-1 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary">
                    <span class="flex h-7 w-14 items-center justify-center rounded-tm-lg transition-colors {{ $aktif ? 'bg-primary-container text-on-primary-container' : 'text-on-surface-variant' }}">
                        <x-icon :name="$m['icon']" class="text-[22px]" />
                    </span>
                    <span class="max-w-full truncate text-label-sm {{ $aktif ? 'text-on-surface' : 'text-on-surface-variant' }}">{{ $m['label'] }}</span>
                </a>
            @endforeach

            <button type="button" x-on:click="sheet = true" aria-haspopup="dialog"
                    class="flex min-w-0 flex-1 flex-col items-center gap-0.5 rounded-tm-md px-1 py-1 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary">
                <span class="flex h-7 w-14 items-center justify-center rounded-tm-lg text-on-surface-variant"><x-icon name="menu" class="text-[22px]" /></span>
                <span class="text-label-sm text-on-surface-variant">Menu</span>
            </button>
        </div>
    </nav>

    {{-- ============ HP: lembar "Menu" ============ --}}
    <div x-show="sheet" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Menu">
        <div x-show="sheet" x-transition.opacity x-on:click="sheet = false" class="absolute inset-0 bg-inverse-surface/60"></div>

        <div x-show="sheet" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
             class="absolute inset-x-0 bottom-0 max-h-[85vh] overflow-y-auto rounded-t-[1rem] bg-surface p-4 pb-[max(1rem,env(safe-area-inset-bottom))] shadow-xl">
            <div class="mb-3 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary text-label-lg text-on-primary">{{ $inisial }}</span>
                    <div class="min-w-0">
                        <div class="truncate text-label-lg text-on-surface">{{ $user->name }}</div>
                        <div class="truncate text-body-sm text-on-surface-variant">{{ $user->email }}</div>
                    </div>
                </div>
                <button type="button" x-on:click="sheet = false" aria-label="Tutup menu" class="flex h-9 w-9 items-center justify-center rounded-tm-md text-on-surface-variant hover:bg-surface-container-high"><x-icon name="close" /></button>
            </div>

            <div class="space-y-1">
                @foreach ($sisa as $m)
                    <a href="{{ route($m['route']) }}" class="flex items-center gap-3 rounded-tm-md px-3 py-3 text-label-lg text-on-surface hover:bg-surface-container-high">
                        <x-icon :name="$m['icon']" class="text-[22px] text-secondary" /> <span class="flex-1">{{ $m['label'] }}</span>
                        <x-icon name="chevron_right" class="text-[20px] text-on-surface-variant" />
                    </a>
                @endforeach
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-tm-md px-3 py-3 text-label-lg text-on-surface hover:bg-surface-container-high">
                    <x-icon name="account_circle" class="text-[22px] text-secondary" /> <span class="flex-1">Profil saya</span>
                    <x-icon name="chevron_right" class="text-[20px] text-on-surface-variant" />
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 rounded-tm-md px-3 py-3 text-label-lg text-error hover:bg-error-container/50">
                        <x-icon name="logout" class="text-[22px]" /> <span>Keluar</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
