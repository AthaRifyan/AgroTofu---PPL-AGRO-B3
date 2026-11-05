<x-guest-layout>
    <x-auth-heading title="Konfirmasi kata sandi"
                    description="Ini area yang dilindungi. Masukkan kata sandi Anda untuk melanjutkan." />

    <form method="POST" action="{{ route('password.confirm') }}" class="flex flex-col gap-5"
          x-data="{ loading: false }" x-on:submit="loading = true" x-on:pageshow.window="loading = false">
        @csrf

        <!-- Password -->
        <div class="flex flex-col gap-1.5">
            <x-input-label for="password" value="Kata sandi" />
            <x-password-input id="password" name="password" autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <x-submit-button class="mt-1" icon="arrow_forward" loading-text="Memeriksa…">Konfirmasi</x-submit-button>
    </form>
</x-guest-layout>
