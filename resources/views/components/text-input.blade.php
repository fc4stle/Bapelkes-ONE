@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-[var(--color-primary)] focus:ring-[var(--color-primary)] rounded-md shadow-sm']) }}>
