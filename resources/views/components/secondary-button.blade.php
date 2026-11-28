<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2.5 bg-[var(--color-surface)] border border-[var(--color-primary)] rounded-[var(--radius-btn)] font-semibold text-[13px] text-[var(--color-primary)] transition-colors duration-150 hover:bg-[var(--color-primary-soft)] active:scale-[.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-accent)] focus-visible:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed']) }}>
    {{ $slot }}
</button>
