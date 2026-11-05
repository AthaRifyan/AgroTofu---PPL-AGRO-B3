@props(['disabled' => false])

@php
    $name = $attributes->get('name');
    $invalid = $name && $errors->has($name);
@endphp

<select @disabled($disabled) @if ($invalid) aria-invalid="true" @endif {{ $attributes->merge(['class' => 'w-full rounded-tm-md border bg-surface-container-low py-3 pl-4 pr-10 text-body-md text-on-surface transition-all focus:bg-surface-bright focus:outline-none focus:ring-2 disabled:opacity-60 ' . ($invalid ? 'border-error focus:border-error focus:ring-error/40' : 'border-transparent focus:border-primary-container focus:ring-primary-container')]) }}>{{ $slot }}</select>
