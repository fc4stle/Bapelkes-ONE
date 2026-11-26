@props(['status' => 'pending'])

@php
$labels = [
    'pending' => 'Menunggu Verifikasi',
    'approved' => 'Terverifikasi',
    'rejected' => 'Ditolak',
];
$styles = [
    'pending' => 'background-color: color-mix(in srgb, var(--color-warning) 15%, white); color: var(--color-warning);',
    'approved' => 'background-color: color-mix(in srgb, var(--color-success) 15%, white); color: var(--color-success);',
    'rejected' => 'background-color: color-mix(in srgb, var(--color-danger) 15%, white); color: var(--color-danger);',
];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-[3px] px-2.5 py-0.5 text-xs font-semibold', 'style' => $styles[$status]]) }}>
    {{ $labels[$status] }}
</span>
