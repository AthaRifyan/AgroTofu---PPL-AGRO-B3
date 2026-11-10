<x-guest-layout>
    <x-auth-heading title="Buat kata sandi baru" description="Email Anda sudah dikonfirmasi. Buat kata sandi baru untuk akun ini." />

    {{-- Email dikirim controller ($email); hanya ditampilkan, tidak bisa diubah --}}
    <div class="mb-5 flex items-center gap-3 rounded-tm-md bg-surface-container px-4 py-3">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-tm-md bg-surface-container-lowest text-secondary shadow-sm"><x-icon name="mail" class="text-[20px]" /></span>
        <div class="min-w-0">
            <div class="text-label-sm uppercase tracking-wider text-on-surface-variant">Email akun</div>
            <div class="break-all text-label-lg text-on-surface">{{ $email }}</div>
        </div>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="flex flex-col gap-5" novalidate
          x-data="{ loading: false }" x-on:submit="loading = true" x-on:pageshow.window="loading = false">
        @csrf

        <!-- Password -->
        <div class="flex flex-col gap-1.5">
            <x-input-label for="password" value="Kata sandi baru" />
            <x-password-input id="password" name="password" autocomplete="new-password" :required="false" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <!-- Confirm Password -->
        <div class="flex flex-col gap-1.5">
            <x-input-label for="password_confirmation" value="Ulangi kata sandi baru" />
            <x-password-input id="password_confirmation" name="password_confirmation" autocomplete="new-password" :required="false" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <x-submit-button class="mt-1" icon="arrow_forward" loading-text="Menyimpan…">Simpan kata sandi baru</x-submit-button>
    </form>

    <p class="mt-6 flex items-start justify-center gap-1.5 text-center text-body-sm text-on-surface-variant">
        <x-icon name="schedule" class="mt-px text-[16px]" />
        <span>Konfirmasi email berlaku 10 menit. Setelah itu Anda perlu mengonfirmasi email lagi.</span>
    </p>

    <p class="mt-4 text-center">
        <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 rounded-tm-sm text-label-md text-secondary transition-colors hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-container">
            <x-icon name="arrow_back" class="text-[18px]" />
            <span>Batal dan kembali ke halaman masuk</span>
        </a>
    </p>
</x-guest-layout>
