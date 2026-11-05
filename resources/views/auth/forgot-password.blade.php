<x-guest-layout>
    <x-auth-heading title="Lupa kata sandi?"
                    description="Masukkan email akun Anda. Kami akan mengirim tautan untuk membuat kata sandi baru." />

    <!-- Session Status -->
    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-5"
          x-data="{ loading: false }" x-on:submit="loading = true" x-on:pageshow.window="loading = false">
        @csrf

        <!-- Email Address -->
        <div class="flex flex-col gap-1.5">
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" icon="mail"
                          placeholder="nama@email.com" inputmode="email" required autofocus />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <x-submit-button class="mt-1" icon="arrow_forward" loading-text="Mengirim tautan…">Kirim tautan reset</x-submit-button>
    </form>

    <p class="mt-8 text-center">
        <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 rounded-tm-sm text-label-md text-secondary transition-colors hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-container">
            <x-icon name="arrow_back" class="text-[18px]" />
            <span>Kembali ke halaman masuk</span>
        </a>
    </p>
</x-guest-layout>
