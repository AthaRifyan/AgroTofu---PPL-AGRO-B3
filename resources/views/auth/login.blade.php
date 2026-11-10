<x-guest-layout>
    {{-- Popup setelah kata sandi berhasil diubah (flash 'password_diubah' dari NewPasswordController) --}}
    @if (session('password_diubah'))
        <div x-data="{ buka: true }" x-show="buka" x-cloak
             x-init="$nextTick(() => $refs.tutup && $refs.tutup.focus())"
             x-on:keydown.escape.window="buka = false"
             class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="judul-sandi-diubah">
            <div x-show="buka" x-transition.opacity x-on:click="buka = false" class="absolute inset-0 bg-inverse-surface/60"></div>
            <div x-show="buka" x-transition class="relative w-full max-w-sm rounded-tm-md bg-surface-container-lowest p-6 text-center shadow-xl">
                <span class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-tertiary-fixed text-on-tertiary-fixed">
                    <x-icon name="check_circle" class="text-[32px]" />
                </span>
                <h2 id="judul-sandi-diubah" class="text-headline-sm font-bold text-on-surface">{{ session('password_diubah') }}</h2>
                <p class="mt-1 text-body-md text-on-surface-variant">Silakan masuk menggunakan kata sandi yang baru.</p>
                <x-btn type="button" x-ref="tutup" x-on:click="buka = false" class="mt-5 w-full">Masuk sekarang</x-btn>
            </div>
        </div>
    @endif

    <div class="mb-8">
        <div class="mb-3 inline-flex items-center gap-2 rounded-tm-lg bg-surface-container-high px-3 py-1 text-label-sm text-on-surface-variant">
            <x-icon name="factory" class="text-[15px] text-secondary" />
            <span>Portal Operasional Produksi</span>
        </div>
        <h1 class="text-headline-xl-mobile tracking-tight text-on-surface sm:text-headline-lg">Selamat datang kembali</h1>
        <p class="mt-1.5 text-body-md leading-relaxed text-on-surface-variant">
            Masuk ke AgroTofu AI untuk memantau produksi hari ini.
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-5" novalidate
          x-data="{ show: false, loading: false }"
          x-on:submit="loading = true"
          x-on:pageshow.window="loading = false">
        @csrf

        <!-- Email Address -->
        <div class="flex flex-col gap-1.5">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" icon="mail"
                          placeholder="nama@email.com" inputmode="email"
                          autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <!-- Password -->
        <div class="flex flex-col gap-1.5">
            <x-input-label for="password" value="Kata sandi" />
            <x-text-input id="password" type="password" name="password" icon="lock"
                          placeholder="••••••••" x-bind:type="show ? 'text' : 'password'"
                          autocomplete="current-password">
                <x-slot:trailing>
                    <button type="button" x-on:click="show = ! show"
                            x-bind:aria-label="show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                            aria-label="Tampilkan kata sandi"
                            class="absolute right-2.5 flex h-8 w-8 items-center justify-center rounded-tm-md text-on-surface-variant hover:text-on-surface focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-container">
                        <x-icon name="visibility" class="text-[20px]" x-text="show ? 'visibility_off' : 'visibility'" />
                    </button>
                </x-slot:trailing>
            </x-text-input>
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <!-- Remember Me & Forgot Password -->
        <div class="flex items-center justify-between gap-4">
            <label for="remember_me" class="inline-flex cursor-pointer select-none items-center gap-2.5">
                <input id="remember_me" type="checkbox" name="remember"
                       class="h-5 w-5 rounded-tm-sm border-outline text-secondary shadow-sm focus:ring-2 focus:ring-secondary focus:ring-offset-0">
                <span class="text-label-md text-on-surface-variant">Ingat saya di perangkat ini</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}"
                   class="shrink-0 rounded-tm-sm text-label-md text-secondary transition-colors hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-container">
                    Lupa kata sandi?
                </a>
            @endif
        </div>

        <x-primary-button class="mt-1 w-full" x-bind:disabled="loading">
            <span x-show="! loading" class="inline-flex items-center gap-2">
                <span>Masuk</span>
                <x-icon name="arrow_forward" class="text-[20px]" />
            </span>
            <span x-show="loading" x-cloak class="inline-flex items-center gap-2">
                <x-icon name="progress_activity" class="animate-spin text-[20px]" />
                <span>Memeriksa akun…</span>
            </span>
        </x-primary-button>
    </form>

    <p class="mt-8 text-center text-body-sm text-on-surface-variant">
        Butuh akses atau akun Anda bermasalah? Hubungi administrator.
    </p>
</x-guest-layout>
