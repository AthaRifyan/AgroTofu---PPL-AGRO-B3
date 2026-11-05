@props([
    'id',
    'name' => 'password',
    'autocomplete' => 'current-password',
    'placeholder' => '••••••••',
    'bag' => 'default',
])

{{-- Input kata sandi dengan ikon kunci dan tombol tampil/sembunyi --}}
<div x-data="{ show: false }">
    <x-text-input :id="$id" :name="$name" type="password" icon="lock"
                  :placeholder="$placeholder" :autocomplete="$autocomplete" :bag="$bag"
                  x-bind:type="show ? 'text' : 'password'" required {{ $attributes }}>
        <x-slot:trailing>
            <button type="button" x-on:click="show = ! show"
                    x-bind:aria-label="show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                    aria-label="Tampilkan kata sandi"
                    class="absolute right-2.5 flex h-8 w-8 items-center justify-center rounded-tm-md text-on-surface-variant hover:text-on-surface focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-container">
                <x-icon name="visibility" class="text-[20px]" x-text="show ? 'visibility_off' : 'visibility'" />
            </button>
        </x-slot:trailing>
    </x-text-input>
</div>
