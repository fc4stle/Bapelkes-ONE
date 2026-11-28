@props(['active' => false])

<a {{ $attributes->merge(['class' => ($active ? 'opacity-100 bg-white bg-opacity-20' : 'opacity-90 hover:opacity-100') . ' rounded-full px-4 py-2 text-white font-medium transition-colors']) }}>
    {{ $slot }}
</a>
