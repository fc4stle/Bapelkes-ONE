@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-[var(--color-primary)] text-start text-base font-medium text-[var(--color-primary)] bg-[#fdf2f4] focus:outline-none focus:text-[var(--color-primary-hover)] focus:bg-[#f8e8eb] focus:border-[var(--color-primary-hover)] transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-[var(--color-muted)] hover:text-[var(--color-text)] hover:bg-[var(--color-bg)] hover:border-[var(--color-line)] focus:outline-none focus:text-[var(--color-text)] focus:bg-[var(--color-bg)] focus:border-[var(--color-line)] transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
