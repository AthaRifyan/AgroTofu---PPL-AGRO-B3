@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'flex items-start gap-2 rounded-tm-md bg-tertiary-fixed px-4 py-3 text-label-md text-on-tertiary-fixed']) }} role="status">
        <x-icon name="check_circle" class="text-[18px] shrink-0" />
        <span>{{ $status }}</span>
    </div>
@endif
