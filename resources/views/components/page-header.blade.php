@props(['title', 'subtitle' => null, 'back' => null, 'eyebrow' => null])

<div {{ $attributes->merge(['class' => 'mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-end']) }}>
    <div class="min-w-0">
        @if ($back)
            <a href="{{ $back }}" class="mb-1.5 inline-flex items-center gap-1 rounded-tm-sm text-label-md text-secondary transition-colors hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-container">
                <x-icon name="arrow_back" class="text-[16px]" /> Kembali
            </a>
        @endif
        @if ($eyebrow)
            <div class="mb-1 flex items-center gap-1.5 text-label-sm uppercase tracking-wider">
                <span class="text-outline">Operasional</span>
                <x-icon name="chevron_right" class="text-[14px] text-outline" />
                <span class="font-bold text-primary">{{ $eyebrow }}</span>
            </div>
        @endif
        <h1 class="text-headline-xl-mobile tracking-tight text-on-surface sm:text-headline-lg">{{ $title }}</h1>
        @if ($subtitle)<p class="mt-0.5 max-w-2xl text-body-md text-on-surface-variant">{{ $subtitle }}</p>@endif
    </div>
    @isset($actions)
        <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
