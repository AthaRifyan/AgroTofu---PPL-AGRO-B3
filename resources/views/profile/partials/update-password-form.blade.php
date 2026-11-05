<section>
    <header>
        <h2 class="text-headline-sm text-on-surface">Ubah kata sandi</h2>
        <p class="mt-1 text-body-md text-on-surface-variant">Pakai kata sandi yang panjang dan sulit ditebak agar akun tetap aman.</p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 flex flex-col gap-5"
          x-data="{ loading: false }" x-on:submit="loading = true" x-on:pageshow.window="loading = false">
        @csrf
        @method('put')

        <x-field name="current_password" bag="updatePassword" for="update_password_current_password" label="Kata sandi saat ini">
            <x-password-input id="update_password_current_password" name="current_password" bag="updatePassword" autocomplete="current-password" />
        </x-field>

        <x-field name="password" bag="updatePassword" for="update_password_password" label="Kata sandi baru">
            <x-password-input id="update_password_password" name="password" bag="updatePassword" autocomplete="new-password" />
        </x-field>

        <x-field name="password_confirmation" bag="updatePassword" for="update_password_password_confirmation" label="Ulangi kata sandi baru">
            <x-password-input id="update_password_password_confirmation" name="password_confirmation" bag="updatePassword" autocomplete="new-password" />
        </x-field>

        <div class="flex items-center gap-4">
            <x-submit-button class="w-auto" icon="save" loading-text="Menyimpan…">Simpan kata sandi</x-submit-button>

            @if (session('status') === 'password-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)"
                   class="flex items-center gap-1 text-label-md text-on-surface-variant"><x-icon name="check_circle" class="text-[18px] text-secondary" /> Tersimpan</p>
            @endif
        </div>
    </form>
</section>
