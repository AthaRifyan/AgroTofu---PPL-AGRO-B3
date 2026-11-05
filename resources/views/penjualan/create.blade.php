@php
    $daftar = collect($produkJson)->values();
    $itemErrors = collect($errors->getMessages())->filter(fn ($m, $k) => str_starts_with($k, 'items'))->flatten()->all();
    $metodeIkon = ['tunai' => ['payments', 'Tunai'], 'transfer' => ['account_balance', 'Transfer'], 'qris' => ['qr_code_2', 'QRIS'], 'lainnya' => ['more_horiz', 'Lainnya']];
@endphp

<x-app-layout>
    <x-page-header title="Kasir" eyebrow="Penjualan" subtitle="Pilih produk dari katalog, lalu selesaikan pembayaran.">
        <x-slot:actions>
            <x-btn :href="route('penjualan.index')" variant="secondary" icon="history">Riwayat nota</x-btn>
        </x-slot:actions>
    </x-page-header>

    <form method="POST" action="{{ route('penjualan.store') }}"
          x-data="posForm(@js($daftar), @js(old('items', [])), @js(old('bayar', '')))"
          x-on:submit="loading = true" x-on:pageshow.window="loading = false">
        @csrf

        {{-- Data kirim ke backend: hanya produk dengan jumlah > 0 --}}
        <template x-for="(item, i) in cart" :key="item.id">
            <div>
                <input type="hidden" x-bind:name="`items[${i}][produk_id]`" x-bind:value="item.id">
                <input type="hidden" x-bind:name="`items[${i}][jumlah]`" x-bind:value="item.jumlah">
            </div>
        </template>

        <div class="grid items-start gap-6 lg:grid-cols-12">
            {{-- ===== Katalog produk ===== --}}
            <div class="min-w-0 lg:col-span-8">
                <div class="mb-4">
                    <x-text-input type="search" icon="search" x-model="q" placeholder="Cari nama produk" aria-label="Cari produk" class="!bg-surface-container-lowest shadow-sm" />
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
                    @forelse ($daftar as $p)
                        @php $habis = (float) $p['stok'] <= 0; @endphp
                        <div x-show="! q || {{ \Illuminate\Support\Js::from(mb_strtolower($p['nama'])) }}.includes(q.toLowerCase())"
                             class="flex flex-col justify-between rounded-tm-md bg-surface-container-lowest p-2.5 shadow-sm transition-shadow hover:shadow-md {{ $habis ? 'opacity-60' : '' }}"
                             x-bind:class="qtyOf(produk[{{ $loop->index }}]) > 0 ? 'ring-2 ring-primary-container' : ''">
                            <button type="button" x-on:click="tambah(produk[{{ $loop->index }}])" @disabled($habis)
                                    class="text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary disabled:cursor-not-allowed" aria-label="Tambah {{ $p['nama'] }}">
                                <span class="relative flex aspect-[4/3] w-full items-center justify-center overflow-hidden rounded-tm-md bg-surface-container text-secondary">
                                    <x-icon name="category" class="text-[44px]" />
                                    <span class="absolute bottom-2 right-2"><x-badge :tone="$habis ? 'danger' : ((float) $p['stok'] < 10 ? 'info' : 'success')">{{ $habis ? 'Habis' : 'Stok ' . rtrim(rtrim(number_format($p['stok'], 2, ',', '.'), '0'), ',') }}</x-badge></span>
                                    <span x-show="qtyOf(produk[{{ $loop->index }}]) > 0" x-cloak class="absolute left-2 top-2 rounded-tm-lg bg-primary-container px-2 py-0.5 text-label-sm text-on-primary-container shadow-sm" x-text="'× ' + qtyOf(produk[{{ $loop->index }}])"></span>
                                </span>
                                <span class="mt-2 block text-label-lg leading-tight text-on-surface">{{ $p['nama'] }}</span>
                            </button>
                            <div class="mt-2 flex items-center justify-between gap-2">
                                <span class="text-label-lg font-bold text-secondary"><x-money :value="$p['harga']" /><span class="text-label-sm font-normal text-on-surface-variant"> /{{ $p['satuan'] }}</span></span>
                                <button type="button" x-on:click="tambah(produk[{{ $loop->index }}])" @disabled($habis) aria-label="Tambah {{ $p['nama'] }}"
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-tm-md bg-primary-container text-on-primary-container shadow-sm transition-all hover:bg-secondary-container active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary disabled:cursor-not-allowed">
                                    <x-icon name="add" class="text-[20px]" />
                                </button>
                            </div>
                        </div>
                    @empty
                        <x-empty-state class="col-span-full" icon="category" title="Belum ada produk aktif" text="Minta admin atau manajer menambahkan produk terlebih dahulu." />
                    @endforelse
                </div>
            </div>

            {{-- ===== Keranjang & pembayaran ===== --}}
            <div id="pembayaran" class="lg:col-span-4 lg:sticky lg:top-28">
                <x-card>
                    <div class="mb-3 flex items-center justify-between rounded-tm-md bg-surface-container-low px-3 py-2.5">
                        <span class="flex items-center gap-1.5 text-label-lg text-on-surface"><x-icon name="shopping_cart_checkout" class="text-[20px] text-primary" /> Pesanan saat ini</span>
                        <button type="button" x-on:click="kosongkan()" x-show="cart.length > 0" x-cloak class="inline-flex items-center gap-0.5 rounded-tm-sm text-label-sm text-error hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-error">
                            <x-icon name="delete" class="text-[14px]" /> Reset
                        </button>
                    </div>

                    <div class="mb-3 max-h-72 space-y-2 overflow-y-auto pr-0.5">
                        <template x-for="item in cart" :key="item.id">
                            <div class="flex items-center justify-between gap-2 rounded-tm-md bg-surface-container-low/70 p-2.5">
                                <div class="min-w-0">
                                    <div class="truncate text-label-md text-on-surface" x-text="item.nama"></div>
                                    <div class="text-body-sm text-on-surface-variant" x-text="rupiah(item.harga) + ' / ' + item.satuan"></div>
                                    <div class="text-label-md font-extrabold text-secondary" x-text="rupiah(item.subtotal)"></div>
                                    <p x-show="melebihiStok(item)" class="mt-0.5 flex items-center gap-1 text-body-sm text-error"><x-icon name="warning" class="text-[14px]" /> Melebihi stok</p>
                                </div>
                                <div class="flex shrink-0 items-center gap-1">
                                    <button type="button" x-on:click="kurangi(item)" aria-label="Kurangi" class="flex h-8 w-8 items-center justify-center rounded-tm-md bg-surface-container-lowest text-on-surface shadow-sm hover:bg-surface-container active:scale-95"><x-icon name="remove" class="text-[18px]" /></button>
                                    <input type="number" step="any" min="0" inputmode="decimal" x-model.number="qty[item.id]" aria-label="Jumlah"
                                           class="h-8 w-12 rounded-tm-md border-0 bg-surface-container-lowest px-1 text-center text-label-md text-on-surface shadow-sm focus:ring-2 focus:ring-primary-container">
                                    <button type="button" x-on:click="tambah(item)" aria-label="Tambah" class="flex h-8 w-8 items-center justify-center rounded-tm-md bg-surface-container-lowest text-on-surface shadow-sm hover:bg-surface-container active:scale-95"><x-icon name="add" class="text-[18px]" /></button>
                                    <button type="button" x-on:click="hapus(item)" aria-label="Hapus item" class="ml-0.5 flex h-8 w-8 items-center justify-center rounded-tm-md bg-surface-container-lowest text-on-surface-variant shadow-sm hover:bg-error-container hover:text-on-error-container"><x-icon name="delete" class="text-[18px]" /></button>
                                </div>
                            </div>
                        </template>
                        <div x-show="cart.length === 0" class="flex flex-col items-center gap-1 py-6 text-center text-on-surface-variant">
                            <x-icon name="remove_shopping_cart" class="text-[36px] text-outline" />
                            <span class="text-label-md">Keranjang kosong</span>
                            <span class="text-body-sm text-outline">Pilih produk di katalog untuk memulai.</span>
                        </div>
                    </div>

                    <div class="mb-4 flex items-baseline justify-between rounded-tm-md bg-surface-container px-3 py-3">
                        <div>
                            <div class="text-label-sm uppercase tracking-wider text-outline">Total tagihan</div>
                            <div class="text-body-sm text-on-surface-variant" x-text="cart.length + ' macam produk'"></div>
                        </div>
                        <span class="text-headline-md font-extrabold tracking-tight text-secondary" x-text="rupiah(total)"></span>
                    </div>

                    <div class="flex flex-col gap-4">
                        <fieldset>
                            <legend class="mb-1.5 text-label-md text-on-surface">Metode pembayaran</legend>
                            <div class="grid grid-cols-4 gap-1.5">
                                @foreach (\App\Models\Penjualan::METODE as $m)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="metode_pembayaran" value="{{ $m }}" class="peer sr-only" @checked(old('metode_pembayaran', 'tunai') === $m) required>
                                        <span class="flex flex-col items-center justify-center rounded-tm-md bg-surface-container p-2 text-on-surface-variant transition-all hover:bg-surface-container-high active:scale-95 peer-checked:bg-primary-container peer-checked:font-bold peer-checked:text-on-primary-container peer-checked:shadow-sm peer-focus-visible:ring-2 peer-focus-visible:ring-secondary">
                                            <x-icon :name="$metodeIkon[$m][0] ?? 'payments'" class="text-[20px]" />
                                            <span class="mt-0.5 text-label-sm">{{ $metodeIkon[$m][1] ?? ucfirst($m) }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            <x-input-error class="mt-1" :messages="$errors->get('metode_pembayaran')" />
                        </fieldset>

                        <div>
                            <x-field name="bayar" label="Uang diterima (Rp)">
                                <x-text-input id="bayar" name="bayar" type="number" step="any" min="0" inputmode="decimal" x-model="bayar" required />
                            </x-field>
                            <div class="mt-1.5 flex items-center justify-between gap-2">
                                <button type="button" x-on:click="uangPas()" x-show="total > 0" class="rounded-tm-sm text-label-md text-secondary hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-container">Uang pas</button>
                                <p class="ml-auto text-body-sm" x-bind:class="kurang ? 'text-error' : 'text-on-surface-variant'">
                                    <span x-show="kurang">Uang kurang dari total</span>
                                    <span x-show="! kurang && total > 0">Kembalian <span class="text-label-lg text-on-surface" x-text="rupiah(kembalian)"></span></span>
                                </p>
                            </div>
                        </div>

                        <x-field name="nama_pembeli" label="Nama pembeli (opsional)">
                            <x-text-input id="nama_pembeli" name="nama_pembeli" type="text" icon="person" maxlength="100" :value="old('nama_pembeli')" />
                        </x-field>

                        <x-input-error :messages="$itemErrors" />

                        <x-submit-button icon="check_circle" loading-text="Menyimpan transaksi…" x-bind:disabled="loading || ! bisaKirim">Simpan transaksi</x-submit-button>
                    </div>
                </x-card>
            </div>
        </div>

        {{-- Bar ringkas di HP (di atas bar menu) --}}
        <a href="#pembayaran" x-show="cart.length > 0" x-cloak
           class="fixed inset-x-4 bottom-[calc(4.75rem+env(safe-area-inset-bottom))] z-20 flex items-center justify-between rounded-tm-md bg-inverse-surface px-4 py-3 text-inverse-on-surface shadow-xl lg:hidden">
            <span class="text-label-md" x-text="cart.length + ' produk'"></span>
            <span class="flex items-center gap-1 text-label-lg"><span x-text="rupiah(total)"></span> <x-icon name="arrow_forward" class="text-[18px]" /></span>
        </a>
    </form>
</x-app-layout>
