<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#fff8f5">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'TofuMind AI') }}</title>

        <!-- Font & ikon dimuat lokal lewat Vite (lihat resources/css/app.css) -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-surface font-sans text-on-surface antialiased">
        <div class="flex min-h-screen flex-col lg:flex-row">

            {{-- PANEL KIRI: brand & ringkasan fitur (hanya tampil di layar lebar) --}}
            <aside class="relative hidden overflow-hidden bg-surface-container-low p-14 lg:flex lg:w-[52%] lg:flex-col lg:justify-between">
                <div class="pointer-events-none absolute -left-24 -top-24 h-96 w-96 rounded-full bg-primary-container/20 blur-3xl"></div>
                <div class="pointer-events-none absolute bottom-10 right-4 h-80 w-80 rounded-full bg-secondary-container/15 blur-3xl"></div>

                <div class="relative z-10 flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-tm-md bg-primary-container text-on-surface shadow-sm">
                        <x-icon name="grain" />
                    </div>
                    <div>
                        <div class="text-headline-sm tracking-tight text-on-surface">TofuMind <span class="text-secondary">AI</span></div>
                        <p class="text-body-sm text-on-surface-variant">Sistem Manajemen Produksi Cerdas</p>
                    </div>
                </div>

                <div class="relative z-10 my-10 w-full max-w-lg">
                    <h2 class="text-headline-md tracking-tight text-on-surface">
                        Kelola produksi tahu dari bahan baku sampai penjualan
                    </h2>
                    <p class="mt-2 text-body-md leading-relaxed text-on-surface-variant">
                        Catat bahan baku, jadwalkan produksi, pantau stok produk, dan rekap penjualan dalam satu aplikasi.
                    </p>

                    <ul class="mt-6 divide-y divide-outline-variant/40 overflow-hidden rounded-tm-md bg-surface-container shadow-xl">
                        <li class="flex items-start gap-3 p-4">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-tm-md bg-surface-container-lowest text-secondary shadow-sm"><x-icon name="inventory_2" class="text-[22px]" /></span>
                            <div>
                                <div class="text-label-lg text-on-surface">Bahan baku dan stok</div>
                                <div class="text-body-sm text-on-surface-variant">Pantau stok kedelai, supplier, dan peringatan stok menipis.</div>
                            </div>
                        </li>
                        <li class="flex items-start gap-3 p-4">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-tm-md bg-surface-container-lowest text-secondary shadow-sm"><x-icon name="factory" class="text-[22px]" /></span>
                            <div>
                                <div class="text-label-lg text-on-surface">Produksi</div>
                                <div class="text-body-sm text-on-surface-variant">Jadwalkan batch dan catat setiap tahap sampai hasil akhir.</div>
                            </div>
                        </li>
                        <li class="flex items-start gap-3 p-4">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-tm-md bg-surface-container-lowest text-secondary shadow-sm"><x-icon name="point_of_sale" class="text-[22px]" /></span>
                            <div>
                                <div class="text-label-lg text-on-surface">Penjualan</div>
                                <div class="text-body-sm text-on-surface-variant">Catat transaksi kasir dan stok produk jadi.</div>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="relative z-10 flex items-center gap-2 text-label-md text-on-surface-variant">
                    <x-icon name="verified_user" class="text-[18px]" />
                    <span>Untuk admin, manajer, karyawan, dan kasir</span>
                </div>
            </aside>

            {{-- PANEL KANAN: konten halaman (form login, dll.) --}}
            <main class="auth flex w-full flex-col items-center justify-center px-6 pb-[max(2.5rem,env(safe-area-inset-bottom))] pt-[max(2.5rem,env(safe-area-inset-top))] sm:px-12 lg:w-[48%] lg:p-16">
                <div class="w-full max-w-md">
                    {{-- Brand ringkas untuk layar kecil (di desktop brand ada di panel kiri) --}}
                    <div class="mb-8 flex items-center gap-3 lg:hidden">
                        <div class="flex h-11 w-11 items-center justify-center rounded-tm-md bg-primary-container text-on-surface shadow-sm">
                            <x-icon name="grain" />
                        </div>
                        <div class="text-headline-sm tracking-tight text-on-surface">TofuMind <span class="text-secondary">AI</span></div>
                    </div>

                    {{ $slot }}
                </div>
            </main>
        </div>
    </body>
</html>
