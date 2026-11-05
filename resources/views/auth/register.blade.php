<x-guest-layout>
    <x-auth-heading title="Buat akun baru" description="Isi data berikut untuk mendaftar di TofuMind AI." />

    <form method="POST" action="{{ route('register') }}" class="flex flex-col gap-5"
          x-data="{ loading: false }" x-on:submit="loading = true" x-on:pageshow.window="loading = false">
        @csrf

        <!-- Name -->
        <div class="flex flex-col gap-1.5">
            <x-input-label for="name" value="Nama lengkap" />
            <x-text-input id="name" type="text" name="name" :value="old('name')" icon="person"
                          placeholder="Nama Anda" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <!-- Email Address -->
        <div class="flex flex-col gap-1.5">
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" icon="mail"
                          placeholder="nama@email.com" inputmode="email" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <!-- Password -->
        <div class="flex flex-col gap-1.5">
            <x-input-label for="password" value="Kata sandi" />
            <x-password-input id="password" name="password" autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <!-- Confirm Password -->
        <div class="flex flex-col gap-1.5">
            <x-input-label for="password_confirmation" value="Ulangi kata sandi" />
            <x-password-input id="password_confirmation" name="password_confirmation" autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <x-submit-button class="mt-1" icon="arrow_forward" loading-text="Membuat akun…">Daftar</x-submit-button>
    </form>

    <p class="mt-8 text-center text-body-sm text-on-surface-variant">
        Sudah punya akun?
        <a href="{{ route('login') }}" class="rounded-tm-sm text-label-md text-secondary transition-colors hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-container">Masuk</a>
    </p>
</x-guest-layout>
