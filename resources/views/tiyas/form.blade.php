<x-app-layout>
    <div class="py-12">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8" x-data="tiyasForm()" @input="updateProgress()" @change="updateProgress()">
            <!-- Progress Indicator Sticky -->
            <div class="sticky top-0 z-10 mb-6 rounded-lg shadow-md p-4" style="border-left: 4px solid var(--color-primary); background-color: var(--color-bg);">
                <div class="flex justify-between items-center mb-2">
                    <span class="font-semibold" style="color: var(--color-text);">
                        <span x-text="answered"></span> dari 65 terjawab
                    </span>
                    <span class="text-sm" style="color: var(--color-text); opacity: 0.6;" x-text="percentComplete()"></span>
                </div>
                <div class="w-full h-2 rounded-full overflow-hidden" style="background-color: color-mix(in srgb, var(--color-text) 15%, transparent);">
                    <div class="h-full rounded-full transition-all duration-300" style="background-color: var(--color-primary);" x-bind:style="'width: ' + (answered / 65 * 100) + '%'"></div>
                </div>
            </div>

            <x-card>
                <h2 class="text-2xl font-bold mb-1" style="color: var(--color-text);">Evaluasi TIYAS</h2>
                <p class="mb-6" style="color: var(--color-text); opacity: 0.7;">Pengisian Kuesioner Model TIYAS (Training Implementation and Yielded Behavior Evaluation System)</p>

                <form action="{{ route('tiyas.simpan', $pendaftaran) }}" method="POST" class="space-y-8" @submit="$nextTick(() => { if (! $event.defaultPrevented) submitting = true })">
                    @csrf

                    <!-- Info Peserta (Read-only) -->
                    <div class="p-4 rounded-lg" style="background-color: var(--color-bg); border: 1px solid color-mix(in srgb, var(--color-text) 15%, transparent);">
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

                    <!-- Bagian Identitas -->
                    <div style="border-top: 1px solid color-mix(in srgb, var(--color-text) 15%, transparent); padding-top: 1.5rem;">
                        <h3 class="text-lg font-semibold mb-4" style="color: var(--color-text);">Data Identitas</h3>
                        <div class="space-y-4">
                            <div>
                                <label for="asal_instansi" class="block text-sm font-medium mb-2" style="color: var(--color-text);">Asal Instansi *</label>
                                <select name="asal_instansi" id="asal_instansi" class="block w-full rounded-lg shadow-sm focus:ring-2" style="border: 1px solid color-mix(in srgb, var(--color-text) 20%, transparent); background-color: var(--color-surface); color: var(--color-text);" required>
                                    <option value="">-- Pilih --</option>
                                    @foreach ($config['identitas']['asal_instansi'] as $option)
                                        <option value="{{ $option }}" @if (old('asal_instansi') === $option) selected @endif>{{ $option }}</option>
                                    @endforeach
                                </select>
                                @error('asal_instansi') <span class="text-sm" style="color: var(--color-danger);">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label for="asal_kabupaten" class="block text-sm font-medium mb-2" style="color: var(--color-text);">Asal Kabupaten/Kota *</label>
                                <select name="asal_kabupaten" id="asal_kabupaten" class="block w-full rounded-lg shadow-sm focus:ring-2" style="border: 1px solid color-mix(in srgb, var(--color-text) 20%, transparent); background-color: var(--color-surface); color: var(--color-text);" required>
                                    <option value="">-- Pilih --</option>
                                    @foreach ($config['identitas']['asal_kabupaten'] as $option)
                                        <option value="{{ $option }}" @if (old('asal_kabupaten') === $option) selected @endif>{{ $option }}</option>
                                    @endforeach
                                </select>
                                @error('asal_kabupaten') <span class="text-sm" style="color: var(--color-danger);">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label for="jenis_profesi" class="block text-sm font-medium mb-2" style="color: var(--color-text);">Jenis Profesi *</label>
                                <select name="jenis_profesi" id="jenis_profesi" class="block w-full rounded-lg shadow-sm focus:ring-2" style="border: 1px solid color-mix(in srgb, var(--color-text) 20%, transparent); background-color: var(--color-surface); color: var(--color-text);" required>
                                    <option value="">-- Pilih --</option>
                                    @foreach ($config['identitas']['jenis_profesi'] as $option)
                                        <option value="{{ $option }}" @if (old('jenis_profesi') === $option) selected @endif>{{ $option }}</option>
                                    @endforeach
                                </select>
                                @error('jenis_profesi') <span class="text-sm" style="color: var(--color-danger);">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label for="masa_kerja" class="block text-sm font-medium mb-2" style="color: var(--color-text);">Masa Kerja *</label>
                                <select name="masa_kerja" id="masa_kerja" class="block w-full rounded-lg shadow-sm focus:ring-2" style="border: 1px solid color-mix(in srgb, var(--color-text) 20%, transparent); background-color: var(--color-surface); color: var(--color-text);" required>
                                    <option value="">-- Pilih --</option>
                                    @foreach ($config['identitas']['masa_kerja'] as $option)
                                        <option value="{{ $option }}" @if (old('masa_kerja') === $option) selected @endif>{{ $option }}</option>
                                    @endforeach
                                </select>
                                @error('masa_kerja') <span class="text-sm" style="color: var(--color-danger);">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label for="waktu_sejak_selesai" class="block text-sm font-medium mb-2" style="color: var(--color-text);">Waktu Sejak Pelatihan Selesai *</label>
                                <select name="waktu_sejak_selesai" id="waktu_sejak_selesai" class="block w-full rounded-lg shadow-sm focus:ring-2" style="border: 1px solid color-mix(in srgb, var(--color-text) 20%, transparent); background-color: var(--color-surface); color: var(--color-text);" required>
                                    <option value="">-- Pilih --</option>
                                    @foreach ($config['identitas']['waktu_sejak_selesai'] as $option)
                                        <option value="{{ $option }}" @if (old('waktu_sejak_selesai') === $option) selected @endif>{{ $option }}</option>
                                    @endforeach
                                </select>
                                @error('waktu_sejak_selesai') <span class="text-sm" style="color: var(--color-danger);">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Bagian Pertanyaan (A, B, C, D) -->
                    @foreach ($config['konstruk'] as $kodeKonstruk => $konstruk)
                        <div style="border-top: 1px solid color-mix(in srgb, var(--color-text) 15%, transparent); padding-top: 1.5rem;">
                            <div class="mb-4">
                                <h3 class="text-lg font-semibold" style="color: var(--color-text);">Bagian {{ $konstruk['bagian'] }}: {{ $konstruk['judul'] }}</h3>
                                <p class="text-sm mt-1" style="color: var(--color-text); opacity: 0.6;">{{ $konstruk['petunjuk'] }}</p>
                            </div>

                            @foreach ($konstruk['dimensi'] as $namaDimensi => $butirs)
                                <div class="mb-6">
                                    <h4 class="font-medium mb-3" style="color: var(--color-text);">{{ $namaDimensi }}</h4>
                                    <div class="space-y-4">
                                        @foreach ($butirs as $kodeButir => $teksButir)
                                            <div class="p-4 rounded-lg" style="background-color: var(--color-surface); border: 1px solid color-mix(in srgb, var(--color-text) 15%, transparent);">
                                                <p class="text-sm mb-3" style="color: var(--color-text);"><strong>{{ $kodeButir }}.</strong> {{ $teksButir }}</p>
                                                <div class="flex gap-3 flex-wrap">
                                                    @foreach ($config['skala'][$konstruk['skala']] as $nilai => $label)
                                                        <label class="relative inline-flex items-center gap-2 py-2 px-3 rounded-full border-2 cursor-pointer transition-all duration-150 hover:opacity-80 peer-checked:text-white" style="touch-action: manipulation; min-height: 44px; border-color: color-mix(in srgb, var(--color-text) 30%, transparent); color: var(--color-text);">
                                                            <input 
                                                                type="radio" 
                                                                name="{{ $kodeButir }}" 
                                                                value="{{ $nilai }}" 
                                                                class="peer w-0 h-0 opacity-0 peer-checked:[background-color:var(--color-primary)] peer-checked:[border-color:var(--color-primary)]"
                                                                @if (old($kodeButir) == $nilai) checked @endif
                                                                required
                                                            >
                                                            <span class="text-sm font-medium">{{ $nilai }}. {{ $label }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                                @error($kodeButir) <span class="text-xs mt-1 block" style="color: var(--color-danger);">{{ $message }}</span> @enderror
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach

                    <!-- Tombol Submit -->
                    <div style="border-top: 1px solid color-mix(in srgb, var(--color-text) 15%, transparent); padding-top: 1.5rem;" class="flex gap-3">
                        <button type="submit" class="px-6 py-2 rounded-lg font-medium text-white transition-all duration-150 hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed" style="background-color: var(--color-primary);" x-bind:disabled="submitting">
                            <span x-show="!submitting">Simpan Evaluasi</span>
                            <span x-show="submitting">Menyimpan...</span>
                        </button>
                        <a href="{{ route('pendaftarans.index') }}" class="px-6 py-2 rounded-lg font-medium transition-colors duration-150 hover:opacity-80" style="border: 1px solid color-mix(in srgb, var(--color-text) 20%, transparent); color: var(--color-text);">
                            Batalkan
                        </a>
                    </div>
                </form>
            </x-card>
        </div>
    </div>

    <script>
        function tiyasForm() {
            return {
                answered: 0,
                totalQuestions: 65,
                submitting: false,
                
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
                },
                
                percentComplete() {
                    const percent = Math.round((this.answered / this.totalQuestions) * 100);
                    return percent + '%';
                },
                
                init() {
                    this.updateProgress();
                }
            };
        }
    </script>
</x-app-layout>
