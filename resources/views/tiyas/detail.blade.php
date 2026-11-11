<x-app-layout>
    <div class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold mb-6" style="color: var(--color-text);">Detail Evaluasi TIYAS</h1>

            <x-card class="mb-6">
                <h2 class="text-xl font-semibold mb-4" style="color: var(--color-text);">Peserta: {{ $tiyasResponse->pendaftaran->peserta->name }}</h2>
                
                <!-- Identitas -->
                <div class="grid grid-cols-2 gap-4 mb-6 pb-6" style="border-bottom: 1px solid color-mix(in srgb, var(--color-text) 15%, transparent);">
                    @foreach ($tiyasResponse->identitas as $fieldName => $value)
                        <div>
                            <p class="text-sm" style="color: var(--color-text); opacity: 0.6;">{{ ucfirst(str_replace('_', ' ', $fieldName)) }}</p>
                            <p class="font-medium" style="color: var(--color-text);">{{ $value }}</p>
                        </div>
                    @endforeach
                </div>

                <!-- Skor Per Konstruk -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-4" style="color: var(--color-text);">Skor Per Konstruk</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        @foreach ($skor as $konstruk => $data)
                            @php
                                $warna = match($data['kategori']) {
                                    'Tinggi' => 'var(--color-success)',
                                    'Sedang' => 'var(--color-accent)',
                                    'Rendah' => 'var(--color-danger)',
                                    default => 'var(--color-text)',
                                };
                            @endphp
                            <div style="background-color: var(--color-bg); padding: 1rem; border-radius: var(--radius-card); border: 1px solid color-mix(in srgb, var(--color-text) 15%, transparent);">
                                <p class="text-sm mb-1" style="color: var(--color-text); opacity: 0.6;">{{ $config['konstruk'][$konstruk]['judul'] }}</p>
                                <p class="text-3xl font-bold mb-2" style="color: var(--color-primary);">{{ number_format($data['skor'], 2) }}</p>
                                <span class="inline-block px-2 py-1 rounded text-xs font-semibold text-white" style="background-color: {{ $warna }};">
                                    {{ $data['kategori'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                    
                    <!-- Skor Per Dimensi -->
                    @if (isset($skorDimensi) && !empty($skorDimensi))
                        <div class="mt-6 pt-6" style="border-top: 1px solid color-mix(in srgb, var(--color-text) 15%, transparent);">
                            <h4 class="text-md font-semibold mb-4" style="color: var(--color-text);">Skor Per Dimensi</h4>
                            <div class="space-y-4">
                                @foreach ($skorDimensi as $konstruk => $dimensis)
                                    <div style="background-color: var(--color-bg); padding: 1rem; border-radius: var(--radius-card); border: 1px solid color-mix(in srgb, var(--color-text) 15%, transparent);">
                                        <p class="text-sm font-medium mb-3" style="color: var(--color-text);">{{ $config['konstruk'][$konstruk]['judul'] }}</p>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                            @foreach ($dimensis as $namaDimensi => $skorDim)
                                                <div class="flex justify-between items-center p-2 rounded" style="background-color: var(--color-surface);">
                                                    <span class="text-sm" style="color: var(--color-text); opacity: 0.7;">{{ $namaDimensi }}</span>
                                                    <span class="font-medium" style="color: var(--color-primary);">{{ number_format($skorDim, 2) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="mb-6 p-4 rounded-lg" style="background-color: var(--color-bg);">
                    <h3 class="font-semibold mb-2" style="color: var(--color-text);">Interpretasi</h3>
                    <p style="color: var(--color-text);">{{ $interpretasi }}</p>
                </div>

                <!-- Form Catatan -->
                <form action="{{ route('tiyas.catatan', $tiyasResponse) }}" method="POST">
                    @csrf
                    @method('PATCH')

                    <h3 class="text-lg font-semibold mb-4" style="color: var(--color-text);">Catatan</h3>

                    <div class="mb-4">
                        <label for="catatan_supervisor" class="block text-sm font-medium mb-2" style="color: var(--color-text);">Catatan Supervisor</label>
                        <textarea name="catatan_supervisor" id="catatan_supervisor" rows="4" maxlength="2000" class="block w-full rounded-lg shadow-sm focus:ring-2" style="border: 1px solid color-mix(in srgb, var(--color-text) 20%, transparent); background-color: var(--color-surface); color: var(--color-text); focus-color: var(--color-primary);" placeholder="Masukkan catatan dari supervisor...">{{ old('catatan_supervisor', $tiyasResponse->catatan_supervisor) }}</textarea>
                        <p class="text-xs mt-1" style="color: var(--color-text); opacity: 0.6;">Maksimal 2000 karakter</p>
                    </div>

                    <div class="mb-6">
                        <label for="catatan_rekan_kerja" class="block text-sm font-medium mb-2" style="color: var(--color-text);">Catatan Rekan Kerja</label>
                        <textarea name="catatan_rekan_kerja" id="catatan_rekan_kerja" rows="4" maxlength="2000" class="block w-full rounded-lg shadow-sm focus:ring-2" style="border: 1px solid color-mix(in srgb, var(--color-text) 20%, transparent); background-color: var(--color-surface); color: var(--color-text);" placeholder="Masukkan catatan dari rekan kerja...">{{ old('catatan_rekan_kerja', $tiyasResponse->catatan_rekan_kerja) }}</textarea>
                        <p class="text-xs mt-1" style="color: var(--color-text); opacity: 0.6;">Maksimal 2000 karakter</p>
                    </div>

                    <button type="submit" class="px-6 py-2 rounded-lg font-medium hover:opacity-90" style="background-color: var(--color-primary); color: white;">
                        Simpan Catatan
                    </button>
                </form>
            </x-card>

            <!-- Kembali -->
            <div>
                <a href="{{ route('tiyas.hasil', $tiyasResponse->pendaftaran->pelatihan) }}" style="color: var(--color-primary);" class="hover:opacity-80">
                    ← Kembali ke Hasil
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
