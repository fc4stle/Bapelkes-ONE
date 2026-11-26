@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-[var(--color-text)]']) }}>
    {{ $value ?? $slot }}
</label>
