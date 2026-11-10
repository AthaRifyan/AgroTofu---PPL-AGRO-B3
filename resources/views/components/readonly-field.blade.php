@props(['label', 'value', 'icon' => 'lock'])

{{-- Kolom baca-saja (tidak dikirim ke server) --}}
<div {{ $attributes->merge(['class' => 'flex flex-col gap-1.5']) }}>
    <span class="text-label-md text-on-surface">{{ $label }}</span>
    <div class="flex items-center gap-2 rounded-tm-md bg-surface-container px-4 py-3 text-body-md text-on-surface-variant" aria-readonly="true">
        <x-icon :name="$icon" class="text-[18px] text-outline" />
        <span class="min-w-0 break-words">{{ $value }}</span>
    </div>
</div>
