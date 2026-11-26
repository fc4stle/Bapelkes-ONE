@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 text-start text-base font-medium text-white focus:outline-none transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-white opacity-80 hover:opacity-100 focus:outline-none focus:opacity-100 transition duration-150 ease-in-out';
@endphp

@php
if ($active ?? false) {
    $classes .= ' border-[var(--color-gold-soft)]';
}
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
