@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-semibold text-[13px] text-[var(--color-text)]']) }}>
    {{ $value ?? $slot }}
</label>
