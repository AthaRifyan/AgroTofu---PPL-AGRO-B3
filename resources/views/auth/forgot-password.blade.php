<x-guest-layout>
    <x-auth-heading title="Lupa kata sandi?"
                    description="Masukkan email akun Anda untuk mengonfirmasi. Jika email terdaftar, Anda bisa langsung membuat kata sandi baru." />

    <!-- Session Status -->
    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-5" novalidate
          x-data="{ loading: false }" x-on:submit="loading = true" x-on:pageshow.window="loading = false">
        @csrf

        <!-- Email Address -->
        <div class="flex flex-col gap-1.5">
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" icon="mail"
                          placeholder="nama@email.com" inputmode="email" autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <x-submit-button class="mt-1" icon="arrow_forward" loading-text="Memeriksa email…">Konfirmasi email</x-submit-button>
    </form>

    <p class="mt-8 text-center">
        <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 rounded-tm-sm text-label-md text-secondary transition-colors hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-container">
            <x-icon name="arrow_back" class="text-[18px]" />
            <span>Kembali ke halaman masuk</span>
        </a>
    </p>
</x-guest-layout>
