<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            TIYAS - Alat Tes Identifikasi Potensi (65 Pertanyaan)
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Progress Sticky Header -->
            <div class="fixed top-[88px] left-0 right-0 z-40 bg-white border-b border-gray-200 shadow-sm" x-data="{ answered: 0, total: 65 }" @change.window="answered = document.querySelectorAll('input[name=\\\"jawaban\\\"]:checked').length">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-[var(--color-text)]">
                            <span x-text="answered"></span> dari 65 terjawab
                        </p>
                        <div class="w-48 h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full bg-[var(--color-primary)] transition-all duration-300 ease-out" x-bind:style="`width: ${(answered / 65) * 100}%`"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form TIYAS -->
            <div class="bg-white rounded-[var(--radius-card)] shadow-md p-6 mt-16" x-data="{ answered: 0 }">
                <form action="{{ route('tiyas.store') }}" method="POST" @submit="submitting = true" x-data="{ submitting: false }" @submit.prevent="if(!submitting) $el.submit()">
                    @csrf

                    <div class="space-y-8">
                        @for ($i = 1; $i <= 65; $i++)
                            <fieldset class="border-b border-gray-200 pb-6 last:border-b-0">
                                <legend class="text-sm font-semibold text-[var(--color-primary)] mb-4">
                                    Pertanyaan {{ $i }} dari 65
                                </legend>

                                <p class="text-base text-[var(--color-text)] mb-4 font-medium">
                                    @switch($i % 3)
                                        @case(1)
                                            Saya lebih suka bekerja sendiri daripada dalam tim.
                                            @break
                                        @case(2)
                                            Saya menyukai tantangan yang membutuhkan pemikiran kreatif.
                                            @break
                                        @default
                                            Saya merasa nyaman dengan peraturan dan struktur yang jelas.
                                    @endswitch
                                </p>

                                <div class="grid grid-cols-2 gap-3">
                                    @foreach (['sangat_tidak_setuju' => 'Sangat Tidak Setuju', 'tidak_setuju' => 'Tidak Setuju', 'setuju' => 'Setuju', 'sangat_setuju' => 'Sangat Setuju'] as $value => $label)
                                        <label class="relative flex items-center cursor-pointer group">
                                            <input 
                                                type="radio" 
                                                name="jawaban[{{ $i }}]" 
                                                value="{{ $value }}"
                                                class="sr-only peer"
                                                @change="$dispatch('change')"
                                                required
                                            />
                                            <span class="w-full px-4 py-3 text-sm font-medium text-center rounded-lg border-2 border-gray-300 text-gray-700 transition-all duration-150 bg-white peer-checked:bg-[var(--color-primary)] peer-checked:border-[var(--color-primary)] peer-checked:text-white hover:border-gray-400">
                                                {{ $label }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endfor
                    </div>

                    <!-- Submit Button -->
                    <div class="mt-8 flex justify-end gap-3">
                        <a href="{{ route('dashboard') }}">
                            <x-secondary-button type="button">Batal</x-secondary-button>
                        </a>
                        <button type="submit" class="inline-flex items-center px-6 py-3 bg-[var(--color-primary)] border border-transparent rounded-[var(--radius-btn)] font-semibold text-xs text-white uppercase tracking-widest hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed" x-bind:disabled="submitting">
                            <span x-show="!submitting">Kirim TIYAS</span>
                            <span x-show="submitting" class="inline-flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Mengirim...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
