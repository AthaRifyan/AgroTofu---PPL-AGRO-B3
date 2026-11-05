@props(['status'])

@php
    $status = $status instanceof \BackedEnum ? $status->value : (string) $status;
    $tone = ['terjadwal' => 'info', 'berjalan' => 'primary', 'selesai' => 'success', 'batal' => 'danger', 'menunggu' => 'neutral'][$status] ?? 'neutral';
@endphp

<x-badge :tone="$tone" :dot="true" {{ $attributes }}>{{ ucfirst($status) }}</x-badge>
