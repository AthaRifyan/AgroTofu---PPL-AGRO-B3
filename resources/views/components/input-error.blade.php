@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'space-y-1']) }} role="alert">
        @foreach ((array) $messages as $message)
            <li class="flex items-start gap-1.5 text-body-sm text-error">
                <x-icon name="error" class="text-[16px] mt-px shrink-0" />
                <span>{{ $message }}</span>
            </li>
        @endforeach
    </ul>
@endif
