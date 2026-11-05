<x-guest-layout>
    <x-auth-heading title="Buat kata sandi baru" description="Pilih kata sandi baru untuk akun Anda." />

    <form method="POST" action="{{ route('password.store') }}" class="flex flex-col gap-5"
          x-data="{ loading: false }" x-on:submit="loading = true" x-on:pageshow.window="loading = false">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div class="flex flex-col gap-1.5">
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" type="email" name="email" :value="old('email', $request->email)" icon="mail"
                          inputmode="email" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <!-- Password -->
        <div class="flex flex-col gap-1.5">
            <x-input-label for="password" value="Kata sandi baru" />
            <x-password-input id="password" name="password" autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <!-- Confirm Password -->
        <div class="flex flex-col gap-1.5">
            <x-input-label for="password_confirmation" value="Ulangi kata sandi baru" />
            <x-password-input id="password_confirmation" name="password_confirmation" autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <x-submit-button class="mt-1" icon="arrow_forward" loading-text="Menyimpan…">Simpan kata sandi baru</x-submit-button>
    </form>
</x-guest-layout>
