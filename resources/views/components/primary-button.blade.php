<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2.5 bg-[var(--color-primary)] border border-transparent rounded-[var(--radius-btn)] font-semibold text-[13px] text-white transition-colors duration-150 hover:bg-[var(--color-primary-hover)] active:scale-[.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-accent)] focus-visible:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed']) }}>
    {{ $slot }}
</button>
