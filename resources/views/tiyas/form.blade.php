<x-app-layout>
    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <x-card>
                <h2 class="text-2xl font-bold text-gray-900 mb-1">Evaluasi TIYAS</h2>
                <p class="text-sm text-gray-600 mb-6">Pelatihan: <strong>{{ $pendaftaran->pelatihan->nama }}</strong></p>

                @if ($errors->any())
                    <div class="mb-6 p-4 rounded-lg" style="background-color: #fee; border-left: 4px solid var(--color-danger);">
                        <p class="font-semibold text-red-700">Silakan perbaiki kesalahan berikut:</p>
                        <ul class="list-disc list-inside text-red-600 text-sm mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('tiyas.simpan', $pendaftaran) }}" method="POST">
                    @csrf

                    <!-- Identitas Peserta -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Data Diri Peserta</h3>
                        <div class="mb-4 p-3 bg-gray-50 rounded">
                            <p class="text-sm text-gray-700"><strong>Nama:</strong> {{ $pendaftaran->peserta->name }}</p>
                            <p class="text-sm text-gray-700 mt-2"><strong>Email:</strong> {{ $pendaftaran->peserta->email }}</p>
                        </div>

                        <label for="identitas" class="block text-sm font-medium text-gray-700 mb-2">
                            Identitas Peserta <span class="text-red-500">*</span>
                        </label>
                        <select id="identitas" name="identitas" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                            <option value="">-- Pilih Salah Satu --</option>
                            @foreach ($config['identitas'] as $ident)
                                <option value="{{ $ident['kode'] }}" @selected(old('identitas') == $ident['kode'])>
                                    {{ $ident['label'] }}
                                </option>
                            @endforeach
                        </select>
                        @error('identitas')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Bagian A, B, C, D dari Config -->
                    @foreach ($config['bagian'] as $bagian)
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $bagian['judul'] }}</h3>
                            <p class="text-sm text-gray-600 mb-4">{{ $bagian['petunjuk'] }}</p>

                            @foreach ($bagian['dimensi'] as $dimensi)
                                <div class="mb-6 pb-6 border-b border-gray-200 last:border-b-0">
                                    <h4 class="font-semibold text-gray-800 mb-4">{{ $dimensi['label'] }}</h4>

                                    @foreach ($dimensi['butir'] as $butir)
                                        <div class="mb-4 p-4 bg-gray-50 rounded">
                                            <p class="text-sm font-medium text-gray-900 mb-3">
                                                <strong>{{ $butir['kode'] }}.</strong> {{ $butir['teks'] }}
                                            </p>
                                            <div class="flex gap-4 flex-wrap">
                                                @foreach ($config['skala'] as $index => $skala)
                                                    <label class="flex items-center">
                                                        <input type="radio" name="{{ $butir['kode'] }}" value="{{ $index + 1 }}" class="mr-2" 
                                                            @checked(old($butir['kode']) == $index + 1) required>
                                                        <span class="text-sm text-gray-700">{{ $index + 1 }}. {{ $skala }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                            @error($butir['kode'])
                                                <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    @endforeach

                    <!-- Tombol Submit -->
                    <div class="flex gap-4 mt-8">
                        <a href="{{ route('pendaftarans.index') }}" class="px-6 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                            Kembali
                        </a>
                        <button type="submit" class="px-6 py-2 rounded-lg hover:opacity-90" style="background-color: var(--color-primary); color: white;">
                            Simpan Evaluasi
                        </button>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
