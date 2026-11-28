@if (session('success'))
    <div x-data="{ open: true }" @mouseenter="clearInterval($el._closeTimer)" @mouseleave="$el._closeTimer = setTimeout(() => open = false, 5000)" @focusin="clearInterval($el._closeTimer)" @focusout="$el._closeTimer = setTimeout(() => open = false, 5000)" x-show="open" x-transition class="mb-4 p-4 rounded-[var(--radius-card)] border-2 border-[var(--color-success)]" style="background-color: color-mix(in srgb, var(--color-success) 15%, transparent);" role="status">
        <div class="flex items-start justify-between gap-3">
            <div class="flex-1" style="color: var(--color-success);">
                {{ session('success') }}
            </div>
            <button @click="open = false" type="button" aria-label="Tutup notifikasi" style="color: var(--color-success);" class="hover:opacity-75 focus:outline-none">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
        <script>
            const closeEl = document.currentScript.parentElement;
            if (closeEl._closeTimer === undefined) {
                closeEl._closeTimer = setTimeout(() => closeEl.style.display = 'none', 5000);
            }
        </script>
    </div>
@endif

@if (session('error'))
    <div x-show="true" x-transition class="mb-4 p-4 rounded-[var(--radius-card)] border-2 border-[var(--color-danger)]" style="background-color: color-mix(in srgb, var(--color-danger) 15%, transparent);" role="alert">
        <div class="flex items-start justify-between gap-3">
            <div class="flex-1" style="color: var(--color-danger);">
                {{ session('error') }}
            </div>
            <button onclick="this.parentElement.parentElement.style.display='none'" type="button" aria-label="Tutup notifikasi" style="color: var(--color-danger);" class="hover:opacity-75 focus:outline-none">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    </div>
@endif
