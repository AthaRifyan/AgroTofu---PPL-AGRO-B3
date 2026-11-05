@props(['icon', 'label', 'href' => null, 'tone' => 'neutral'])

@php
    $el = $href ? 'a' : 'div';
    $tones = [
        'neutral' => 'bg-surface-container-high text-primary',
        'primary' => 'bg-primary-container text-on-primary-container',
        'danger'  => 'bg-error-container text-on-error-container',
        'success' => 'bg-tertiary-fixed text-on-tertiary-fixed',
    ];
    // Angka panjang (mis. Rupiah) memakai ukuran lebih kecil agar tidak terpotong
    $panjang = mb_strlen(trim(strip_tags((string) $slot))) > 10;
@endphp

<{{ $el }} @if ($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'flex flex-col justify-between rounded-tm-md bg-surface-container-lowest p-5 shadow-sm transition-shadow hover:shadow-md' . ($href ? ' focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary' : '')]) }}>
    <div class="flex items-start justify-between gap-3">
        <span class="min-w-0 text-label-sm uppercase tracking-wider text-on-surface-variant">{{ $label }}</span>
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-tm-md {{ $tones[$tone] ?? $tones['neutral'] }}">
            <x-icon :name="$icon" class="text-[22px]" />
        </span>
    </div>
    <div class="mt-2 font-extrabold tracking-tight text-on-surface {{ $panjang ? 'text-headline-md' : 'text-headline-lg' }}">{{ $slot }}</div>
    @isset($footer)
        <div class="mt-4 flex items-center justify-between gap-2 rounded-tm-sm bg-surface-container-low px-3 py-1.5 text-label-sm text-on-surface-variant">{{ $footer }}</div>
    @endisset
</{{ $el }}>
