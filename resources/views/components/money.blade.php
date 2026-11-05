@props(['value' => 0])
<span {{ $attributes }}>{{ 'Rp ' . number_format((float) $value, 0, ',', '.') }}</span>
