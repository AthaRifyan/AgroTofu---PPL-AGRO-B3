
<x-guest-layout>
    <x-auth-heading
        title="Tambah pengguna"
        description="Buat akun karyawan, kasir, atau manajer AgroTofu AI."
    />

    @if (session('success'))
        <div class="rounded-tm-md bg-secondary-container px-4 py-3 text-body-sm text-on-secondary-container">
            {{ session('success') }}
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('admin.register.store') }}"
        class="flex flex-col gap-5"
        x-data="{ loading: false }"
        x-on:submit="loading = true"
        x-on:pageshow.window="loading = false"
    >
        @csrf

        {{-- Nama --}}
        <div class="flex flex-col gap-1.5">
            <x-input-label for="name" value="Nama lengkap" />

            <x-text-input
                id="name"
                type="text"
                name="name"
                :value="old('name')"
                icon="person"
                placeholder="Nama pengguna"
                required
                autofocus
                autocomplete="name"
            />

            <x-input-error :messages="$errors->get('name')" />
        </div>

        {{-- Email --}}
        <div class="flex flex-col gap-1.5">
            <x-input-label for="email" value="Email" />

            <x-text-input
                id="email"
                type="email"
                name="email"
                :value="old('email')"
                icon="mail"
                placeholder="nama@email.com"
                inputmode="email"
                required
                autocomplete="username"
            />

            <x-input-error :messages="$errors->get('email')" />
        </div>

        {{-- Role --}}
        <div class="flex flex-col gap-1.5">
            <x-input-label for="role" value="Jabatan / Role" />

            <select
                id="role"
                name="role"
                required
                class="w-full rounded-tm-md border border-outline-variant bg-surface px-4 py-3 text-body-md text-on-surface focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
            >
                <option value="">Pilih role pengguna</option>

                <option value="karyawan" @selected(old('role') === 'karyawan')>
                    Karyawan
                </option>

                <option value="kasir" @selected(old('role') === 'kasir')>
                    Kasir
                </option>

                <option value="manajer" @selected(old('role') === 'manajer')>
                    Manajer
                </option>
            </select>

            <x-input-error :messages="$errors->get('role')" />
        </div>

        {{-- Password --}}
        <div class="flex flex-col gap-1.5">
            <x-input-label for="password" value="Kata sandi" />

            <x-password-input
                id="password"
                name="password"
                autocomplete="new-password"
            />

            <x-input-error :messages="$errors->get('password')" />
        </div>

        {{-- Konfirmasi Password --}}
        <div class="flex flex-col gap-1.5">
            <x-input-label
                for="password_confirmation"
                value="Ulangi kata sandi"
            />

            <x-password-input
                id="password_confirmation"
                name="password_confirmation"
                autocomplete="new-password"
            />
        </div>

        <x-submit-button
            class="mt-1"
            icon="person"
            loading-text="Membuat akun…"
        >
            Buat akun pengguna
        </x-submit-button>
    </form>

    <div class="mt-6 text-center">
        <a
            href="{{ route('dashboard') }}"
            class="text-label-md text-secondary hover:underline"
        >
            Kembali ke dashboard
        </a>
    </div>
</x-guest-layout>
