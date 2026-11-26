@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-4 text-sm font-medium leading-5 text-white focus:outline-none transition duration-150 ease-in-out'
            : 'inline-flex items-center px-1 pt-1 border-b-4 border-transparent text-sm font-medium leading-5 text-white opacity-80 hover:opacity-100 focus:outline-none focus:opacity-100 transition duration-150 ease-in-out';
@endphp

@php
if ($active ?? false) {
    $classes .= ' border-[var(--color-gold-soft)]';
}
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
