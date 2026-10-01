@props(['status' => 'pending'])

@php
$labels = [
    'pending' => 'Menunggu Verifikasi',
    'approved' => 'Terverifikasi',
    'rejected' => 'Ditolak',
];
$colors = [
    'pending' => 'bg-[var(--color-warning)]',
    'approved' => 'bg-[var(--color-success)]',
    'rejected' => 'bg-[var(--color-danger)]',
];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-[var(--sp-3)] py-1 rounded-full text-xs font-semibold text-white ' . $colors[$status]]) }}>
    {{ $labels[$status] }}
</span>