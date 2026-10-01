@props(['variant' => 'normal'])

@php
$base = 'rounded-[var(--radius-card)] border p-[var(--sp-4)] shadow-[var(--shadow-card)] bg-[var(--color-surface)] transition';
$variants = [
    'normal' => 'border-gray-200',
    'selected' => 'border-[var(--color-primary)] ring-2 ring-[var(--color-primary)]',
    'unavailable' => 'border-gray-200 opacity-50 pointer-events-none',
];
@endphp

<div {{ $attributes->merge(['class' => $base . ' ' . $variants[$variant]]) }}>
    {{ $slot }}
</div>