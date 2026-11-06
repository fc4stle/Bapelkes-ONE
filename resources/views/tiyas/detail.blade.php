<x-app-layout>
    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-6">Detail Evaluasi TIYAS</h1>

            <x-card class="mb-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Peserta: {{ $tiyasResponse->pendaftaran->peserta->name }}</h2>
                
                <!-- Identitas -->
                <div class="grid grid-cols-2 gap-4 mb-6 pb-6 border-b">
                    @foreach ($tiyasResponse->identitas as $fieldName => $value)
                        <div>
                            <p class="text-sm text-gray-600">{{ ucfirst(str_replace('_', ' ', $fieldName)) }}</p>
                            <p class="text-gray-900 font-medium">{{ $value }}</p>
                        </div>
                    @endforeach
                </div>

                <!-- Skor Per Konstruk -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Skor Per Konstruk</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        @foreach ($skor as $konstruk => $data)
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                <p class="text-sm text-gray-600 mb-1">{{ $config['konstruk'][$konstruk]['nama'] }}</p>
                                <p class="text-3xl font-bold" style="color: var(--color-primary);">{{ number_format($data['skor'], 2) }}</p>
                                <span class="inline-block mt-2 px-2 py-1 rounded text-xs font-semibold text-white"
                                    @if ($data['kategori'] === 'Tinggi')
                                        style="background-color: var(--color-success);"
                                    @elseif ($data['kategori'] === 'Sedang')
                                        style="background-color: var(--color-accent);"
                                    @else
                                        style="background-color: var(--color-danger);"
                                    @endif
                                >
                                    {{ $data['kategori'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Interpretasi -->
                <div class="mb-6 p-4 rounded-lg" style="background-color: var(--color-bg);">
                    <h3 class="font-semibold text-gray-900 mb-2">Interpretasi</h3>
                    <p class="text-gray-800">{{ $interpretasi }}</p>
                </div>

                <!-- Form Catatan -->
                <form action="{{ route('tiyas.catatan', $tiyasResponse) }}" method="POST">
                    @csrf
                    @method('PATCH')

                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Catatan</h3>

                    <div class="mb-4">
                        <label for="catatan_supervisor" class="block text-sm font-medium text-gray-700 mb-2">Catatan Supervisor</label>
                        <textarea name="catatan_supervisor" id="catatan_supervisor" rows="4" maxlength="2000" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Masukkan catatan dari supervisor...">{{ old('catatan_supervisor', $tiyasResponse->catatan_supervisor) }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">Maksimal 2000 karakter</p>
                    </div>

                    <div class="mb-6">
                        <label for="catatan_rekan_kerja" class="block text-sm font-medium text-gray-700 mb-2">Catatan Rekan Kerja</label>
                        <textarea name="catatan_rekan_kerja" id="catatan_rekan_kerja" rows="4" maxlength="2000" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Masukkan catatan dari rekan kerja...">{{ old('catatan_rekan_kerja', $tiyasResponse->catatan_rekan_kerja) }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">Maksimal 2000 karakter</p>
                    </div>

                    <button type="submit" class="px-6 py-2 rounded-lg font-medium hover:opacity-90" style="background-color: var(--color-primary); color: white;">
                        Simpan Catatan
                    </button>
                </form>
            </x-card>

            <!-- Kembali -->
            <div>
                <a href="{{ route('tiyas.hasil', $tiyasResponse->pendaftaran->pelatihan) }}" class="text-indigo-600 hover:text-indigo-900">
                    ← Kembali ke Hasil
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
