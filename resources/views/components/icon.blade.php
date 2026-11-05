@props(['name'])

{{-- Ikon Material Symbols (font lokal). Daftar ikon yang tersedia: resources/fonts/README.md --}}
<span {{ $attributes->merge(['class' => 'material-symbols-outlined', 'aria-hidden' => 'true']) }}>{{ $name }}</span>
