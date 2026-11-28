@props(['variant' => 'normal', 'interactive' => false])

@php
$base = 'rounded-[var(--radius-card)] border bg-[var(--color-surface)] p-[var(--sp-4)] sm:p-[var(--sp-6)] transition';
$variants = [
    'normal' => 'border-[var(--color-line)]',
    'accent' => 'border-t-[3px] border-t-[var(--color-accent)] border-[var(--color-line)]',
    'selected' => 'border-[var(--color-primary)] ring-2 ring-[var(--color-primary)]',
    'unavailable' => 'border-[var(--color-line)] opacity-50 pointer-events-none',
];
$interactive_classes = $interactive ? 'hover:-translate-y-0.5 hover:border-[var(--color-primary)]/40 focus-within:ring focus-within:ring-[var(--color-primary)]' : '';
@endphp

<div {{ $attributes->merge(['class' => $base . ' ' . $variants[$variant] . ' ' . $interactive_classes]) }}>
    {{ $slot }}
</div>
