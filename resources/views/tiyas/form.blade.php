<x-app-layout>
    <div class="py-12">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8" x-data="tiyasForm(@json($totalQuestions), @json($initialStep), @json($errorsFields))" @input="updateProgress()" @change="updateProgress()">
            <!-- Header Progress (Non-Sticky) -->
            <div class="mb-6 p-4 rounded-lg" style="background-color: var(--color-surface); border: 1px solid color-mix(in srgb, var(--color-text) 15%, transparent);">
                <div class="mb-3">
                    <p class="font-semibold" style="color: var(--color-text);">
                        <span x-text="'Langkah ' + currentStep + ' dari 5'"></span> — <span x-text="stepTitle"></span>
                    </p>
                </div>
                <div class="flex justify-between items-center mb-3 text-sm" style="color: var(--color-text); opacity: 0.7;">
                    <span><span x-text="answered"></span> dari <span x-text="totalQuestions"></span> terjawab</span>
                </div>
                <div class="w-full h-2 rounded-full overflow-hidden" style="background-color: color-mix(in srgb, var(--color-text) 15%, transparent);">
                    <div class="h-full rounded-full transition-all duration-300" style="background-color: var(--color-primary);" x-bind:style="'width: ' + (answered / totalQuestions * 100) + '%'"></div>
                </div>
            </div>

            <!-- Tautan Batalkan (di luar bar tombol) -->
            <div class="mb-4">
                <a href="{{ route('pendaftarans.index') }}" class="inline-flex items-center text-sm font-medium transition-colors duration-150 hover:opacity-80" style="color: var(--color-text);">
                    ← Batalkan
                </a>
            </div>

            <x-card>
                <h2 class="text-2xl font-bold mb-1" style="color: var(--color-text);">Evaluasi TIYAS</h2>
                <p class="mb-6" style="color: var(--color-text); opacity: 0.7;">Isi kuesioner ini sesuai kondisi Anda. Tidak ada jawaban benar atau salah.</p>

                <form action="{{ route('tiyas.simpan', $pendaftaran) }}" method="POST" class="space-y-8" novalidate @submit="handleSubmit($event)">
                    @csrf

                    <!-- Langkah 1: Data Identitas -->
                    <div data-step="1" x-show="currentStep === 1" class="space-y-6">
                        <div>
                            <p class="text-sm mb-2" style="color: var(--color-text); opacity: 0.7;">Draf tersimpan otomatis di perangkat ini</p>
                        </div>

                        <div class="p-3 sm:p-4 rounded-lg" style="background-color: var(--color-bg); border: 1px solid color-mix(in srgb, var(--color-text) 15%, transparent);">
                            <h3 class="font-semibold mb-3" style="color: var(--color-text);">Informasi Peserta</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm" style="color: var(--color-text); opacity: 0.6;">Nama Peserta</label>
                                    <p class="font-medium" style="color: var(--color-text);">{{ $pendaftaran->peserta->name }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm" style="color: var(--color-text); opacity: 0.6;">Pelatihan</label>
                                    <p class="font-medium" style="color: var(--color-text);">{{ $pendaftaran->pelatihan->nama }}</p>
                                </div>
                            </div>
                        </div>

                        <div style="border-top: 1px solid color-mix(in srgb, var(--color-text) 15%, transparent); padding-top: 1.5rem;">
                            <h3 class="text-lg font-semibold mb-4" style="color: var(--color-text);">Data Identitas</h3>
                            <div class="space-y-4">
                                <div>
                                    <label for="asal_instansi" class="block text-sm font-medium mb-2" style="color: var(--color-text);">Asal Instansi *</label>
                                    <select name="asal_instansi" id="asal_instansi" class="block w-full rounded-lg shadow-sm focus:ring-2" style="border: 1px solid color-mix(in srgb, var(--color-text) 20%, transparent); background-color: var(--color-surface); color: var(--color-text);" required @change="onItemChange($event)">
                                        <option value="">-- Pilih --</option>
                                        @foreach ($config['identitas']['asal_instansi'] as $option)
                                            <option value="{{ $option }}" @if (old('asal_instansi') === $option) selected @endif>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                    @error('asal_instansi') <span class="text-sm" style="color: var(--color-danger);">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label for="asal_kabupaten" class="block text-sm font-medium mb-2" style="color: var(--color-text);">Asal Kabupaten/Kota *</label>
                                    <select name="asal_kabupaten" id="asal_kabupaten" class="block w-full rounded-lg shadow-sm focus:ring-2" style="border: 1px solid color-mix(in srgb, var(--color-text) 20%, transparent); background-color: var(--color-surface); color: var(--color-text);" required @change="onItemChange($event)">
                                        <option value="">-- Pilih --</option>
                                        @foreach ($config['identitas']['asal_kabupaten'] as $option)
                                            <option value="{{ $option }}" @if (old('asal_kabupaten') === $option) selected @endif>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                    @error('asal_kabupaten') <span class="text-sm" style="color: var(--color-danger);">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label for="jenis_profesi" class="block text-sm font-medium mb-2" style="color: var(--color-text);">Jenis Profesi *</label>
                                    <select name="jenis_profesi" id="jenis_profesi" class="block w-full rounded-lg shadow-sm focus:ring-2" style="border: 1px solid color-mix(in srgb, var(--color-text) 20%, transparent); background-color: var(--color-surface); color: var(--color-text);" required @change="onItemChange($event)">
                                        <option value="">-- Pilih --</option>
                                        @foreach ($config['identitas']['jenis_profesi'] as $option)
                                            <option value="{{ $option }}" @if (old('jenis_profesi') === $option) selected @endif>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                    @error('jenis_profesi') <span class="text-sm" style="color: var(--color-danger);">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label for="masa_kerja" class="block text-sm font-medium mb-2" style="color: var(--color-text);">Masa Kerja *</label>
                                    <select name="masa_kerja" id="masa_kerja" class="block w-full rounded-lg shadow-sm focus:ring-2" style="border: 1px solid color-mix(in srgb, var(--color-text) 20%, transparent); background-color: var(--color-surface); color: var(--color-text);" required @change="onItemChange($event)">
                                        <option value="">-- Pilih --</option>
                                        @foreach ($config['identitas']['masa_kerja'] as $option)
                                            <option value="{{ $option }}" @if (old('masa_kerja') === $option) selected @endif>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                    @error('masa_kerja') <span class="text-sm" style="color: var(--color-danger);">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label for="waktu_sejak_selesai" class="block text-sm font-medium mb-2" style="color: var(--color-text);">Waktu Sejak Pelatihan Selesai *</label>
                                    <select name="waktu_sejak_selesai" id="waktu_sejak_selesai" class="block w-full rounded-lg shadow-sm focus:ring-2" style="border: 1px solid color-mix(in srgb, var(--color-text) 20%, transparent); background-color: var(--color-surface); color: var(--color-text);" required @change="onItemChange($event)">
                                        <option value="">-- Pilih --</option>
                                        @foreach ($config['identitas']['waktu_sejak_selesai'] as $option)
                                            <option value="{{ $option }}" @if (old('waktu_sejak_selesai') === $option) selected @endif>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                    @error('waktu_sejak_selesai') <span class="text-sm" style="color: var(--color-danger);">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Langkah 2, 3, 4, 5: Bagian Pertanyaan (A, B, C, D) -->
                    @php
                        $stepCounter = 2;
                    @endphp
                    @foreach ($config['konstruk'] as $kodeKonstruk => $konstruk)
                        <div data-step="{{ $stepCounter }}" x-show="currentStep === {{ $stepCounter }}" class="space-y-6">
                            <div style="border-bottom: 1px solid color-mix(in srgb, var(--color-text) 15%, transparent); padding-bottom: 1.5rem;">
                                <h3 class="text-lg font-semibold mb-2" style="color: var(--color-text);">Bagian {{ $konstruk['bagian'] }}: {{ $konstruk['judul'] }}</h3>
                                <p class="text-sm" style="color: var(--color-text); opacity: 0.6;">{{ $konstruk['petunjuk'] }}</p>
                            </div>

                            @foreach ($konstruk['dimensi'] as $namaDimensi => $butirs)
                                <div class="mb-8">
                                    <h4 class="font-medium mb-4" style="color: var(--color-text);">{{ $namaDimensi }}</h4>
                                    <div class="space-y-6">
                                        @foreach ($butirs as $kodeButir => $teksButir)
                                            <div class="p-3 sm:p-4 rounded-lg" style="background-color: var(--color-surface); border: 1px solid color-mix(in srgb, var(--color-text) 15%, transparent);" data-item="{{ $kodeButir }}" data-invalid="false" @change="onItemChange($event)">
                                                <p class="text-[15px] leading-relaxed mb-4" style="color: var(--color-text);"><strong>{{ $kodeButir }}.</strong> {{ $teksButir }}</p>
                                                <div class="grid grid-cols-4 gap-2">
                                                    @foreach ($config['skala'][$konstruk['skala']] as $nilai => $label)
                                                        <label class="flex flex-col items-center justify-center py-2 px-2 rounded-lg border-2 cursor-pointer transition-all duration-150 hover:opacity-80 has-[:checked]:text-white has-[:checked]:bg-[var(--color-primary)] has-[:checked]:border-[var(--color-primary)] focus-within:ring-2" style="touch-action: manipulation; min-height: 56px; border-color: color-mix(in srgb, var(--color-text) 30%, transparent); color: var(--color-text);">
                                                            <input 
                                                                type="radio" 
                                                                name="{{ $kodeButir }}" 
                                                                value="{{ $nilai }}" 
                                                                class="sr-only"
                                                                @if (old($kodeButir) == $nilai) checked @endif
                                                                required
                                                            >
                                                            <span class="font-bold text-lg">{{ $nilai }}</span>
                                                            <span class="text-[11px] leading-tight text-center break-words hyphens-auto">{{ $label }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                                @error($kodeButir) <span class="text-xs mt-2 block" style="color: var(--color-danger);">{{ $message }}</span> @enderror
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @php
                            $stepCounter++;
                        @endphp
                    @endforeach

                    <!-- Tombol Navigasi (Fixed Bottom) -->
                    <div class="fixed bottom-0 left-0 right-0 z-40" style="background-color: var(--color-bg); border-top: 1px solid color-mix(in srgb, var(--color-text) 15%, transparent); box-shadow: 0 -2px 8px color-mix(in srgb, var(--color-text) 10%, transparent); padding-bottom: max(1rem, env(safe-area-inset-bottom));">
                        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center gap-3 py-4">
                            <button type="button" x-show="currentStep > 1" @click="previousStep()" class="px-6 py-2 rounded-lg font-medium transition-colors duration-150" style="border: 1px solid color-mix(in srgb, var(--color-text) 20%, transparent); color: var(--color-text);">
                                Kembali
                            </button>
                            <button type="button" x-show="currentStep < 5" @click="nextStep()" class="px-6 py-2 rounded-lg font-medium text-white transition-all duration-150 hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed flex-1 sm:flex-none sm:ml-auto" style="background-color: var(--color-primary);">
                                Lanjut
                            </button>
                            <button type="submit" x-show="currentStep === 5" class="px-6 py-2 rounded-lg font-medium text-white transition-all duration-150 hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed flex-1 sm:flex-none sm:ml-auto" style="background-color: var(--color-primary);" x-bind:disabled="submitting">
                                <span x-show="!submitting">Simpan Evaluasi</span>
                                <span x-show="submitting">Menyimpan...</span>
                            </button>
                        </div>
                    </div>

                    <!-- Spacer untuk button sticky -->
                    <div class="h-24"></div>
                </form>
            </x-card>
        </div>
    </div>

    <style>
        [data-invalid="true"] {
            border-color: var(--color-danger) !important;
        }
        [data-invalid="false"] {
            border-color: color-mix(in srgb, var(--color-text) 15%, transparent) !important;
        }
    </style>

    <script>
        function tiyasForm(totalQuestions, initialStep, errorsFields) {
            return {
                answered: 0,
                totalQuestions: totalQuestions,
                submitting: false,
                currentStep: initialStep,
                errorFields: errorsFields,
                stepTitles: {
                    1: 'Data Identitas',
                    2: 'Bagian A: Iklim Transfer',
                    3: 'Bagian B: Lingkungan Transfer',
                    4: 'Bagian C: Niat Transfer',
                    5: 'Bagian D: Perilaku Pascapelatihan',
                },

                get stepTitle() {
                    return this.stepTitles[this.currentStep] || '';
                },

                updateProgress() {
                    const form = this.$el.closest('.max-w-2xl').querySelector('form');
                    const radios = form.querySelectorAll('input[type="radio"]');
                    const answeredSet = new Set();
                    
                    radios.forEach(radio => {
                        if (radio.checked) {
                            answeredSet.add(radio.name);
                        }
                    });
                    
                    this.answered = answeredSet.size;
                    this.saveDraft();
                },

                saveDraft() {
                    if (typeof localStorage === 'undefined') return;
                    try {
                        const form = this.$el.closest('.max-w-2xl').querySelector('form');
                        const data = new FormData(form);
                        const draftData = {};
                        for (let [key, value] of data.entries()) {
                            // Jangan simpan kunci yang diawali "_" (_token, _method, dll)
                            if (!key.startsWith('_')) {
                                draftData[key] = value;
                            }
                        }
                        localStorage.setItem('tiyas-draft-{{ $pendaftaran->id }}', JSON.stringify(draftData));
                    } catch (e) {
                        console.warn('Gagal menyimpan draf:', e);
                    }
                },

                restoreDraft() {
                    if (typeof localStorage === 'undefined') return;
                    try {
                        const draft = localStorage.getItem('tiyas-draft-{{ $pendaftaran->id }}');
                        if (draft && !this.hasErrors()) {
                            const draftData = JSON.parse(draft);
                            const form = this.$el.closest('.max-w-2xl').querySelector('form');
                            for (let [key, value] of Object.entries(draftData)) {
                                // Jangan pulihkan kunci yang diawali "_"
                                if (key.startsWith('_')) continue;
                                const field = form.elements[key];
                                if (field) {
                                    if (field.type === 'radio') {
                                        const radio = form.querySelector(`input[name="${key}"][value="${value}"]`);
                                        if (radio) radio.checked = true;
                                    } else {
                                        field.value = value;
                                    }
                                }
                            }
                            this.updateProgress();
                        }
                    } catch (e) {
                        console.warn('Gagal memulihkan draf:', e);
                    }
                },

                hasErrors() {
                    return this.errorFields.length > 0;
                },

                nextStep() {
                    if (this.validateStep(this.currentStep)) {
                        if (this.currentStep < 5) {
                            this.currentStep++;
                            this.$nextTick(() => {
                                window.scrollTo({ top: 0, behavior: 'smooth' });
                            });
                        }
                    }
                },

                previousStep() {
                    if (this.currentStep > 1) {
                        this.currentStep--;
                        this.$nextTick(() => {
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                        });
                    }
                },

                validateStep(step) {
                    const form = this.$el.closest('.max-w-2xl').querySelector('form');
                    const emptyFields = [];

                    if (step === 1) {
                        ['asal_instansi', 'asal_kabupaten', 'jenis_profesi', 'masa_kerja', 'waktu_sejak_selesai'].forEach(field => {
                            const input = form.elements[field];
                            if (!input || !input.value) {
                                emptyFields.push(field);
                            }
                        });
                    } else {
                        const stepDiv = form.querySelector('[data-step="' + step + '"]');
                        if (stepDiv) {
                            const radios = stepDiv.querySelectorAll('input[type="radio"]');
                            const groupedRadios = {};
                            radios.forEach(radio => {
                                if (!groupedRadios[radio.name]) {
                                    groupedRadios[radio.name] = false;
                                }
                                if (radio.checked) {
                                    groupedRadios[radio.name] = true;
                                }
                            });
                            for (let name in groupedRadios) {
                                if (!groupedRadios[name]) {
                                    emptyFields.push(name);
                                }
                            }
                        }
                    }

                    if (emptyFields.length > 0) {
                        // SET currentStep dulu, lalu $nextTick, baru tampilkan error
                        this.currentStep = step;
                        this.$nextTick(() => {
                            this.showError(emptyFields.length);
                            this.markErrors(emptyFields);
                            this.scrollToFirstError(emptyFields[0]);
                        });
                        return false;
                    }

                    this.clearError();
                    return true;
                },

                showError(count) {
                    const form = this.$el.closest('.max-w-2xl').querySelector('form');
                    let errorDiv = form.querySelector('[data-error-message]');
                    if (!errorDiv) {
                        errorDiv = document.createElement('div');
                        errorDiv.setAttribute('data-error-message', 'true');
                        errorDiv.setAttribute('role', 'alert');
                        errorDiv.style.cssText = `color: var(--color-danger); font-size: 0.875rem; margin-bottom: 1rem; padding: 0.75rem; background-color: color-mix(in srgb, var(--color-danger) 10%, transparent); border-radius: 0.5rem; border-left: 3px solid var(--color-danger);`;
                        form.insertBefore(errorDiv, form.firstChild.nextSibling);
                    }
                    errorDiv.textContent = `Masih ada ${count} pertanyaan yang belum dijawab`;
                },

                clearError() {
                    const form = this.$el.closest('.max-w-2xl').querySelector('form');
                    const errorDiv = form.querySelector('[data-error-message]');
                    if (errorDiv) {
                        errorDiv.remove();
                    }
                },

                markErrors(fields) {
                    const form = this.$el.closest('.max-w-2xl').querySelector('form');
                    // Hapus penandaan lama dari semua kartu butir
                    form.querySelectorAll('[data-item]').forEach(item => {
                        item.dataset.invalid = 'false';
                    });
                    // Hapus penandaan lama dari semua select
                    form.querySelectorAll('select').forEach(select => {
                        select.dataset.invalid = 'false';
                    });
                    
                    // Tandai kartu butir dan select yang kosong
                    fields.forEach(fieldName => {
                        const itemCard = form.querySelector(`[data-item="${fieldName}"]`);
                        if (itemCard) {
                            itemCard.dataset.invalid = 'true';
                        }
                        
                        const select = form.querySelector(`select[name="${fieldName}"]`);
                        if (select) {
                            select.dataset.invalid = 'true';
                        }
                    });
                },

                onItemChange(event) {
                    const item = event.target.closest('[data-item]');
                    if (item) {
                        item.dataset.invalid = 'false';
                    }
                    
                    // Untuk select di step 1
                    if (event.target.tagName === 'SELECT') {
                        event.target.dataset.invalid = 'false';
                    }
                    
                    this.clearError();
                },

                scrollToFirstError(fieldName) {
                    const form = this.$el.closest('.max-w-2xl').querySelector('form');
                    
                    // Cek di kartu butir dulu
                    const itemCard = form.querySelector(`[data-item="${fieldName}"]`);
                    if (itemCard) {
                        itemCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        return;
                    }
                    
                    // Cek di select step 1
                    const select = form.querySelector(`select[name="${fieldName}"]`);
                    if (select) {
                        select.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                },

                handleSubmit(event) {
                    if (!this.validateAllSteps()) {
                        event.preventDefault();
                        return false;
                    }
                    this.submitting = true;
                    this.clearDraft();
                },

                validateAllSteps() {
                    for (let step = 1; step <= 5; step++) {
                        if (!this.validateStep(step)) {
                            // validateStep sudah set currentStep, tunggu render
                            return false;
                        }
                    }
                    return true;
                },

                clearDraft() {
                    if (typeof localStorage === 'undefined') return;
                    try {
                        localStorage.removeItem('tiyas-draft-{{ $pendaftaran->id }}');
                    } catch (e) {
                        console.warn('Gagal menghapus draf:', e);
                    }
                },

                init() {
                    this.restoreDraft();
                    this.updateProgress();
                }
            };
        }
    </script>
</x-app-layout>
