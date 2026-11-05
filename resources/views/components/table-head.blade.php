@props(['cols'])

{{-- Baris judul kolom tabel (hanya tampil di layar md ke atas). $cols = kelas grid-template-columns --}}
<div {{ $attributes->merge(['class' => 'hidden gap-3 bg-surface-container-low px-5 py-2.5 text-label-sm uppercase tracking-wider text-on-surface-variant sm:px-6 md:grid ' . $cols]) }}>{{ $slot }}</div>
