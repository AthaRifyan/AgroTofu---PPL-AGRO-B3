@props(['tone' => 'neutral', 'dot' => false])

@php
    $tones = [
        'neutral' => 'bg-surface-container-high text-on-surface-variant',
        'primary' => 'bg-primary-container text-on-primary-container',
        'success' => 'bg-tertiary-fixed text-on-tertiary-fixed',
        'danger'  => 'bg-error-container text-on-error-container',
        'info'    => 'bg-secondary-fixed text-on-secondary-fixed-variant',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 whitespace-nowrap rounded-tm-lg px-2.5 py-1 text-label-sm ' . ($tones[$tone] ?? $tones['neutral'])]) }}>
    @if ($dot)<span class="h-1.5 w-1.5 rounded-full bg-current opacity-70"></span>@endif
    {{ $slot }}
</span>
