<section class="space-y-5">
    <header>
        <h2 class="text-headline-sm text-on-surface">Hapus akun</h2>
        <p class="mt-1 text-body-md text-on-surface-variant">Setelah akun dihapus, seluruh data akun akan hilang permanen. Tindakan ini tidak bisa dibatalkan.</p>
    </header>

    <x-danger-button type="button" x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')">Hapus akun</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-headline-sm text-on-surface">Yakin ingin menghapus akun?</h2>
            <p class="mt-1 text-body-md text-on-surface-variant">Seluruh data akun akan dihapus permanen. Masukkan kata sandi Anda untuk mengonfirmasi.</p>

            <div class="mt-5 flex flex-col gap-1.5">
                <x-input-label for="password" value="Kata sandi" class="sr-only" />
                <x-password-input id="password" name="password" bag="userDeletion" placeholder="Kata sandi" />
                <x-input-error :messages="$errors->userDeletion->get('password')" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                <x-danger-button>Hapus akun</x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
