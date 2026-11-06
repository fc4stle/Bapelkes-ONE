<x-app-layout>
    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-6">Detail Evaluasi TIYAS</h1>

            <x-card class="mb-8">
                <!-- Data Peserta -->
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Data Peserta</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 p-3 bg-gray-50 rounded">
                    <p><strong>Nama:</strong> {{ $tiyasResponse->pendaftaran->peserta->name }}</p>
                    <p><strong>Email:</strong> {{ $tiyasResponse->pendaftaran->peserta->email }}</p>
                    <p><strong>Pelatihan:</strong> {{ $tiyasResponse->pendaftaran->pelatihan->nama }}</p>
                    <p><strong>Identitas:</strong> 
                        @foreach ($config['identitas'] as $ident)
                            @if ($ident['kode'] == $tiyasResponse->identitas['pilihan'])
                                {{ $ident['label'] }}
                            @endif
                        @endforeach
                    </p>
                </div>

                <!-- Skor Per Dimensi -->
                <h2 class="text-xl font-semibold text-gray-900 mb-4 mt-8">Skor Per Dimensi</h2>
                <div class="overflow-x-auto mb-6">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead style="background-color: var(--color-bg);">
                            <tr>
                                <th class="px-4 py-2 font-semibold text-gray-900">Dimensi</th>
                                <th class="px-4 py-2 font-semibold text-gray-900 text-center">Skor</th>
                                <th class="px-4 py-2 font-semibold text-gray-900">Kategori</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($skor as $konstruk => $data)
                                <tr class="border-b border-gray-200">
                                    <td class="px-4 py-2 font-semibold text-gray-900">{{ $konstruk }}</td>
                                    <td class="px-4 py-2 text-center font-semibold">{{ $data['skor'] }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ $data['kategori'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Interpretasi -->
                <h2 class="text-xl font-semibold text-gray-900 mb-4 mt-8">Interpretasi</h2>
                <div class="p-4 rounded-lg" style="background-color: #e3f2fd; border-left: 4px solid var(--color-primary);">
                    <p class="text-gray-800">{{ $interpretasi }}</p>
                </div>

                <!-- Form Catatan Supervisor dan Rekan Kerja -->
                <h2 class="text-xl font-semibold text-gray-900 mb-4 mt-8">Catatan Wawancara</h2>
                <form action="{{ route('tiyas.simpanCatatan', $tiyasResponse) }}" method="POST">
                    @csrf

                    <div class="mb-6">
                        <label for="catatan_supervisor" class="block text-sm font-medium text-gray-700 mb-2">
                            Catatan Supervisor
                        </label>
                        <textarea id="catatan_supervisor" name="catatan_supervisor" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" maxlength="2000">{{ old('catatan_supervisor', $tiyasResponse->catatan_supervisor) }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">Maksimal 2000 karakter</p>
                        @error('catatan_supervisor')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label for="catatan_rekan_kerja" class="block text-sm font-medium text-gray-700 mb-2">
                            Catatan Rekan Kerja
                        </label>
                        <textarea id="catatan_rekan_kerja" name="catatan_rekan_kerja" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" maxlength="2000">{{ old('catatan_rekan_kerja', $tiyasResponse->catatan_rekan_kerja) }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">Maksimal 2000 karakter</p>
                        @error('catatan_rekan_kerja')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex gap-4">
                        <button type="submit" class="px-6 py-2 rounded-lg hover:opacity-90" style="background-color: var(--color-primary); color: white;">
                            Simpan Catatan
                        </button>
                        <a href="javascript:history.back()" class="px-6 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                            Kembali
                        </a>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
