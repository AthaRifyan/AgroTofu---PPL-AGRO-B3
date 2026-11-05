@props(['title', 'description' => null])

<div {{ $attributes->merge(['class' => 'mb-8']) }}>
    <h1 class="text-headline-xl-mobile tracking-tight text-on-surface sm:text-headline-lg">{{ $title }}</h1>
    @if ($description)
        <p class="mt-1.5 text-body-md leading-relaxed text-on-surface-variant">{{ $description }}</p>
    @endif
</div>
