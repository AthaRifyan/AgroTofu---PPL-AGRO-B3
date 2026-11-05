<section>
    <header>
        <h2 class="text-headline-sm text-on-surface">Informasi profil</h2>
        <p class="mt-1 text-body-md text-on-surface-variant">Ubah nama dan alamat email akun Anda.</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 flex flex-col gap-5"
          x-data="{ loading: false }" x-on:submit="loading = true" x-on:pageshow.window="loading = false">
        @csrf
        @method('patch')

        <x-field name="name" label="Nama lengkap">
            <x-text-input id="name" name="name" type="text" icon="person" :value="old('name', $user->name)" required autofocus autocomplete="name" />
        </x-field>

        <x-field name="email" label="Email">
            <x-text-input id="email" name="email" type="email" icon="mail" :value="old('email', $user->email)" required autocomplete="username" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <p class="mt-1 text-body-sm text-on-surface-variant">
                    Email Anda belum diverifikasi.
                    <button form="send-verification" class="rounded-tm-sm text-label-md text-secondary hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-container">Kirim ulang email verifikasi</button>
                </p>

                @if (session('status') === 'verification-link-sent')
                    <x-auth-session-status class="mt-2" status="Tautan verifikasi baru sudah dikirim ke email Anda." />
                @endif
            @endif
        </x-field>

        <div class="flex items-center gap-4">
            <x-submit-button class="w-auto" icon="save" loading-text="Menyimpan…">Simpan perubahan</x-submit-button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)"
                   class="flex items-center gap-1 text-label-md text-on-surface-variant"><x-icon name="check_circle" class="text-[18px] text-secondary" /> Tersimpan</p>
            @endif
        </div>
    </form>
</section>
