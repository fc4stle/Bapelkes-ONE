@props(['active' => false])

<a {{ $attributes->merge(['class' => ($active ? 'text-white bg-white/20 border border-white/35 rounded-full px-4 py-2' : 'text-white/90 rounded-full px-4 py-2 border border-transparent hover:bg-white/10') . ' font-medium transition-colors']) }}>
    {{ $slot }}
</a>
