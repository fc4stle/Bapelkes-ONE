@props(['active' => false])

<a {{ $attributes->merge(['class' => ($active ? 'bg-white bg-opacity-10' : 'hover:bg-white hover:bg-opacity-10') . ' block w-full ps-3 pe-4 py-2 border-l-4 text-base font-medium text-white transition-colors']) }}>
    {{ $slot }}
</a>
