@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-label-md text-on-surface']) }}>
    {{ $value ?? $slot }}
</label>
