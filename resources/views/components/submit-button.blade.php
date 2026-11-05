@props(['loadingText' => 'Memproses…', 'icon' => null])

{{-- Tombol kirim dengan spinner. Form induknya harus punya:
     x-data="{ loading: false }" x-on:submit="loading = true" x-on:pageshow.window="loading = false"
     Kondisi disabled bisa diganti dengan atribut x-bind:disabled="..." --}}
<x-primary-button {{ $attributes->merge(['class' => 'w-full', 'x-bind:disabled' => 'loading']) }}>
    <span x-show="! loading" class="inline-flex items-center gap-2">
        <span>{{ $slot }}</span>
        @if ($icon)
            <x-icon :name="$icon" class="text-[20px]" />
        @endif
    </span>
    <span x-show="loading" x-cloak class="inline-flex items-center gap-2">
        <x-icon name="progress_activity" class="animate-spin text-[20px]" />
        <span>{{ $loadingText }}</span>
    </span>
</x-primary-button>
