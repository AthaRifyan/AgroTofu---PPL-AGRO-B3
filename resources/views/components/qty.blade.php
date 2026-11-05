@props(['value' => 0, 'unit' => null])
@php
    $angka = rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
@endphp
<span {{ $attributes }}>{{ $angka }}@if ($unit) {{ $unit }}@endif</span>
