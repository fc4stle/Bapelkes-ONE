@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border border-[var(--color-line)] rounded-[var(--radius-btn)] bg-[var(--color-surface)] text-[var(--color-text)] px-3 py-2 focus:outline-none focus:border-[var(--color-primary)] focus:ring-1 focus:ring-[var(--color-primary)] transition']) }}>
