@props(['sebelum', 'sesudah'])

@php
    // Selisih = stok sesudah − stok sebelum, berlaku untuk semua tipe mutasi
    // (masuk = +, keluar = −, opname/koreksi = selisih terhadap stok sistem).
    $d = round((float) $sesudah - (float) $sebelum, 2);
    $angka = rtrim(rtrim(number_format(abs($d), 2, ',', '.'), '0'), ',');
@endphp

<span {{ $attributes->merge(['class' => 'font-bold ' . ($d > 0 ? 'text-tertiary' : ($d < 0 ? 'text-error' : 'text-on-surface-variant'))]) }}>{{ $d > 0 ? '+' : ($d < 0 ? '−' : '') }}{{ $d == 0 ? '0' : $angka }}</span>
