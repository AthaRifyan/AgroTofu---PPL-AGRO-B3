@props(['disabled' => false, 'icon' => null, 'bag' => 'default'])

@php
    $name = $attributes->get('name');
    $invalid = $name && $errors->getBag($bag)->has($name);

    $base = 'w-full rounded-tm-md border bg-surface-container-low text-body-md text-on-surface transition-all placeholder:text-outline focus:bg-surface-bright focus:outline-none focus:ring-2 disabled:opacity-60 [.auth_&]:bg-surface-container-lowest [.auth_&]:shadow-sm '
        . ($invalid
            ? 'border-error focus:border-error focus:ring-error/40'
            : 'border-transparent [.auth_&]:border-outline-variant focus:border-primary-container focus:ring-primary-container');
@endphp

@if ($icon || isset($trailing))
    {{-- Input dengan ikon di kiri dan/atau tombol di kanan (slot "trailing") --}}
    <div class="relative flex items-center">
        @if ($icon)
            <x-icon :name="$icon" class="pointer-events-none absolute left-3.5 text-[20px] text-on-surface-variant" />
        @endif

        <input @disabled($disabled) @if ($invalid) aria-invalid="true" @endif {{ $attributes->merge(['class' => $base . ' py-3 ' . ($icon ? 'pl-11 ' : 'pl-4 ') . (isset($trailing) ? 'pr-11' : 'pr-4')]) }}>

        @isset($trailing)
            {{ $trailing }}
        @endisset
    </div>
@else
    <input @disabled($disabled) @if ($invalid) aria-invalid="true" @endif {{ $attributes->merge(['class' => $base . ' px-4 py-3']) }}>
@endif
