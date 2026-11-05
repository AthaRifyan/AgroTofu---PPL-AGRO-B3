@props(['title' => null, 'description' => null])

<section {{ $attributes->merge(['class' => 'rounded-tm-md bg-surface-container-lowest p-5 shadow-sm sm:p-6']) }}>
    @if ($title || isset($actions))
        <div class="mb-4 flex items-start justify-between gap-3">
            <div class="min-w-0">
                @if ($title)<h2 class="text-headline-sm font-bold text-on-surface">{{ $title }}</h2>@endif
                @if ($description)<p class="mt-0.5 text-body-sm text-on-surface-variant">{{ $description }}</p>@endif
            </div>
            @isset($actions)
                <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif
    {{ $slot }}
</section>
