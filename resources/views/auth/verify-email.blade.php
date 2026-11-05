<x-guest-layout>
    <x-auth-heading title="Verifikasi email Anda"
                    description="Kami sudah mengirim tautan verifikasi ke email Anda. Klik tautan itu untuk mulai memakai aplikasi. Belum menerima emailnya? Kami bisa mengirim ulang." />

    @if (session('status') == 'verification-link-sent')
        <x-auth-session-status class="mb-5" status="Tautan verifikasi baru sudah dikirim ke email yang Anda pakai saat mendaftar." />
    @endif

    <div class="flex flex-col gap-4">
        <form method="POST" action="{{ route('verification.send') }}"
              x-data="{ loading: false }" x-on:submit="loading = true" x-on:pageshow.window="loading = false">
            @csrf
            <x-submit-button icon="mail" loading-text="Mengirim ulang…">Kirim ulang email verifikasi</x-submit-button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="text-center">
            @csrf
            <button type="submit"
                    class="rounded-tm-sm text-label-md text-secondary transition-colors hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-container">
                Keluar
            </button>
        </form>
    </div>
</x-guest-layout>
