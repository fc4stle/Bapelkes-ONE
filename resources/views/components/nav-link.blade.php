@props(['active' => false])

<a {{ $attributes->merge(['class' => ($active ? 'inline-flex items-center h-10 px-3 whitespace-nowrap leading-none text-sm rounded-full border border-white/35 bg-white/20 font-medium transition-colors text-white' : 'inline-flex items-center h-10 px-3 whitespace-nowrap leading-none text-sm rounded-full border border-transparent font-medium transition-colors text-white/90 hover:bg-white/10') . ' ' . ($attributes->get('class') ?? '')]) }}
    @if ($active)
        x-transition
    @endif
>
    {{ $slot }}
</a>
