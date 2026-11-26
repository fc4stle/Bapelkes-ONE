@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-[var(--color-primary)] text-sm font-medium leading-5 text-[var(--color-text)] focus:outline-none focus:border-[var(--color-primary-hover)] transition duration-150 ease-in-out'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-[var(--color-muted)] hover:text-[var(--color-text)] hover:border-[var(--color-line)] focus:outline-none focus:text-[var(--color-text)] focus:border-[var(--color-line)] transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
