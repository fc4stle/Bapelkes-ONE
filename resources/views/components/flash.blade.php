@props(['type' => 'success', 'dismissible' => true])

@if($type === 'success' && session('success'))
    <div class="fixed top-4 right-4 md:right-6 md:top-6 left-4 md:left-auto max-w-sm" 
         x-data="{ show: true }"
         x-init="setTimeout(() => show = false, 4000)"
         x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2">
        
        <div class="rounded-[var(--radius-card)] p-[var(--sp-4)] md:p-[var(--sp-6)] shadow-lg border-l-4 border-[var(--color-success)] flex items-start gap-[var(--sp-4)]" style="background-color: var(--color-surface);">
            <!-- Icon Success -->
            <div class="flex-shrink-0 mt-0.5">
                <svg class="w-5 h-5 text-[var(--color-success)]" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
            </div>

            <!-- Pesan -->
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900">{{ session('success') }}</p>
            </div>

            <!-- Tombol Tutup -->
            @if($dismissible)
                <button @click="show = false" class="flex-shrink-0 text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            @endif
        </div>
    </div>
@endif

@if($type === 'error' && $errors->any())
    <div class="fixed top-4 right-4 md:right-6 md:top-6 left-4 md:left-auto max-w-sm" 
         x-data="{ show: true }">
        
        <div class="rounded-[var(--radius-card)] p-[var(--sp-4)] md:p-[var(--sp-6)] shadow-lg border-l-4 border-[var(--color-danger)] flex items-start gap-[var(--sp-4)]" style="background-color: var(--color-surface);"
             x-show="show"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0">
            
            <!-- Icon Error -->
            <div class="flex-shrink-0 mt-0.5">
                <svg class="w-5 h-5 text-[var(--color-danger)]" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
            </div>

            <!-- Pesan -->
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900">Terjadi kesalahan validasi</p>
                @if($errors->count() === 1)
                    <p class="text-sm text-gray-600 mt-1">{{ $errors->first() }}</p>
                @else
                    <ul class="text-sm text-gray-600 mt-2 list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <!-- Tombol Tutup -->
            @if($dismissible)
                <button @click="show = false" class="flex-shrink-0 text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            @endif
        </div>
    </div>
@endif
