@props(['variant' => 'primary', 'size' => 'md', 'href' => null, 'icon' => null, 'type' => 'submit'])

@php
    $variants = [
        'primary'   => 'bg-primary-container text-on-primary-container shadow-sm hover:bg-secondary-container',
        'secondary' => 'bg-surface-container-lowest text-on-surface shadow-sm hover:bg-surface-container',
        'danger'    => 'bg-error text-on-error shadow-sm hover:bg-error/90',
        'ghost'     => 'text-secondary hover:bg-surface-container-high',
    ];
    $sizes = ['sm' => 'px-3 py-1.5 text-label-md', 'md' => 'px-5 py-3 text-label-lg'];
    $classes = 'inline-flex items-center justify-center gap-2 rounded-tm-md transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary focus-visible:ring-offset-2 focus-visible:ring-offset-surface active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-60 '
        . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<x-icon :name="$icon" class="text-[18px]" />@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<x-icon :name="$icon" class="text-[18px]" />@endif
        {{ $slot }}
    </button>
@endif
