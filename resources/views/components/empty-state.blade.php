@props(['icon' => 'inbox', 'title', 'text' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center rounded-tm-lg border border-dashed border-outline-variant bg-surface-container-low px-6 py-10 text-center']) }}>
    <span class="mb-3 flex h-12 w-12 items-center justify-center rounded-tm-md bg-surface-container-high text-on-surface-variant">
        <x-icon :name="$icon" class="text-[26px]" />
    </span>
    <p class="text-headline-sm text-on-surface">{{ $title }}</p>
    @if ($text)<p class="mt-1 max-w-sm text-body-md text-on-surface-variant">{{ $text }}</p>@endif
    @if (trim((string) $slot) !== '')<div class="mt-4">{{ $slot }}</div>@endif
</div>
