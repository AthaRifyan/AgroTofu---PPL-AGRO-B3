@props(['id', 'name', 'value' => null, 'required' => false, 'placeholder' => '0'])

@php
    $invalid = $errors->has($name);

    // Nilai awal dibersihkan: "12000.00" -> "12000", "1500.50" -> "1500.5"
    $awal = ($value === null || $value === '') ? '' : (string) $value;
    if ($awal !== '' && is_numeric($awal)) {
        $awal = rtrim(rtrim(number_format((float) $awal, 2, '.', ''), '0'), '.');
    }
@endphp

{{-- Input rupiah: tampilan memakai pemisah ribuan (12.000), tetapi yang dikirim ke server
     adalah angka polos lewat input hidden bernama $name (12000). --}}
<div x-data="rupiahInput(@js($awal), @js($name))" x-on:isi-rupiah.window="isi($event.detail)"
     class="flex items-stretch rounded-tm-md border bg-surface-container-low transition-all focus-within:bg-surface-bright focus-within:ring-2 {{ $invalid ? 'border-error focus-within:ring-error/40' : 'border-transparent focus-within:border-primary-container focus-within:ring-primary-container' }}">
    <span class="flex select-none items-center border-r border-outline-variant/60 px-3.5 text-label-lg text-on-surface-variant">Rp.</span>
    <input type="text" id="{{ $id }}" inputmode="decimal" autocomplete="off" placeholder="{{ $placeholder }}"
           @if ($required) required @endif @if ($invalid) aria-invalid="true" @endif
           x-bind:value="tampil" x-on:input="ketik($event)"
           {{ $attributes->merge(['class' => 'min-w-0 flex-1 border-0 bg-transparent px-3 py-3 text-body-md text-on-surface placeholder:text-outline focus:outline-none focus:ring-0']) }}>
    <input type="hidden" name="{{ $name }}" value="{{ $awal }}" x-bind:value="mentah">
</div>
