@props(['name', 'label', 'for' => null, 'hint' => null, 'bag' => 'default'])

{{-- Label + kontrol (slot) + hint + pesan error untuk satu field --}}
<div {{ $attributes->merge(['class' => 'flex flex-col gap-1.5']) }}>
    <x-input-label :for="$for ?? $name" :value="$label" />
    {{ $slot }}
    @if ($hint)<p class="text-body-sm text-on-surface-variant">{{ $hint }}</p>@endif
    <x-input-error :messages="$errors->getBag($bag)->get($name)" />
</div>
