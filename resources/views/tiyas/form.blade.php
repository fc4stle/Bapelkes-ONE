<x-app-layout>
    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <x-card>
                <h2 class="text-2xl font-bold text-gray-900 mb-1">Evaluasi TIYAS</h2>
                <p class="text-gray-600 mb-6">Pengisian Kuesioner Model TIYAS (Training Implementation and Yielded Behavior Evaluation System)</p>

                <form action="{{ route('tiyas.simpan', $pendaftaran) }}" method="POST" class="space-y-8">
                    @csrf

                    <!-- Info Peserta (Read-only) -->
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <h3 class="font-semibold text-gray-900 mb-3">Informasi Peserta</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-600">Nama Peserta</label>
                                <p class="text-gray-900 font-medium">{{ $pendaftaran->peserta->name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600">Pelatihan</label>
                                <p class="text-gray-900 font-medium">{{ $pendaftaran->pelatihan->nama }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Bagian Identitas -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Data Identitas</h3>
                        <div class="space-y-4">
                            <div>
                                <label for="asal_instansi" class="block text-sm font-medium text-gray-700 mb-2">Asal Instansi *</label>
                                <select name="asal_instansi" id="asal_instansi" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                    <option value="">-- Pilih --</option>
                                    @foreach ($config['identitas']['asal_instansi'] as $option)
                                        <option value="{{ $option }}" @if (old('asal_instansi') === $option) selected @endif>{{ $option }}</option>
                                    @endforeach
                                </select>
                                @error('asal_instansi') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label for="asal_kabupaten" class="block text-sm font-medium text-gray-700 mb-2">Asal Kabupaten/Kota *</label>
                                <select name="asal_kabupaten" id="asal_kabupaten" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                    <option value="">-- Pilih --</option>
                                    @foreach ($config['identitas']['asal_kabupaten'] as $option)
                                        <option value="{{ $option }}" @if (old('asal_kabupaten') === $option) selected @endif>{{ $option }}</option>
                                    @endforeach
                                </select>
                                @error('asal_kabupaten') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label for="jenis_profesi" class="block text-sm font-medium text-gray-700 mb-2">Jenis Profesi *</label>
                                <select name="jenis_profesi" id="jenis_profesi" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                    <option value="">-- Pilih --</option>
                                    @foreach ($config['identitas']['jenis_profesi'] as $option)
                                        <option value="{{ $option }}" @if (old('jenis_profesi') === $option) selected @endif>{{ $option }}</option>
                                    @endforeach
                                </select>
                                @error('jenis_profesi') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label for="masa_kerja" class="block text-sm font-medium text-gray-700 mb-2">Masa Kerja *</label>
                                <select name="masa_kerja" id="masa_kerja" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                    <option value="">-- Pilih --</option>
                                    @foreach ($config['identitas']['masa_kerja'] as $option)
                                        <option value="{{ $option }}" @if (old('masa_kerja') === $option) selected @endif>{{ $option }}</option>
                                    @endforeach
                                </select>
                                @error('masa_kerja') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label for="waktu_sejak_selesai" class="block text-sm font-medium text-gray-700 mb-2">Waktu Sejak Pelatihan Selesai *</label>
                                <select name="waktu_sejak_selesai" id="waktu_sejak_selesai" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                    <option value="">-- Pilih --</option>
                                    @foreach ($config['identitas']['waktu_sejak_selesai'] as $option)
                                        <option value="{{ $option }}" @if (old('waktu_sejak_selesai') === $option) selected @endif>{{ $option }}</option>
                                    @endforeach
                                </select>
                                @error('waktu_sejak_selesai') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Bagian Pertanyaan (A, B, C, D) -->
                    @foreach ($config['konstruk'] as $kodeKonstruk => $konstruk)
                        <div class="border-t pt-6">
                            <div class="mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Bagian {{ $konstruk['bagian'] }}: {{ $konstruk['judul'] }}</h3>
                                <p class="text-sm text-gray-600 mt-1">{{ $konstruk['petunjuk'] }}</p>
                            </div>

                            @foreach ($konstruk['dimensi'] as $namaDimensi => $butirs)
                                <div class="mb-6">
                                    <h4 class="font-medium text-gray-800 mb-3">{{ $namaDimensi }}</h4>
                                    <div class="space-y-4">
                                        @foreach ($butirs as $kodeButir => $teksButir)
                                            <div class="bg-white p-4 rounded-lg border border-gray-200">
                                                <p class="text-sm text-gray-900 mb-3"><strong>{{ $kodeButir }}.</strong> {{ $teksButir }}</p>
                                                <div class="flex gap-4 flex-wrap">
                                                    @foreach ($config['skala'][$konstruk['skala']] as $nilai => $label)
                                                        <label class="flex items-center gap-2 cursor-pointer">
                                                            <input 
                                                                type="radio" 
                                                                name="{{ $kodeButir }}" 
                                                                value="{{ $nilai }}" 
                                                                class="w-4 h-4"
                                                                @if (old($kodeButir) == $nilai) checked @endif
                                                                required
                                                            >
                                                            <span class="text-sm">{{ $nilai }}. {{ $label }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                                @error($kodeButir) <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach

                    <!-- Tombol Submit -->
                    <div class="border-t pt-6 flex gap-3">
                        <button type="submit" class="px-6 py-2 rounded-lg font-medium hover:opacity-90" style="background-color: var(--color-primary); color: white;">
                            Simpan Evaluasi
                        </button>
                        <a href="{{ route('pendaftarans.index') }}" class="px-6 py-2 rounded-lg font-medium border border-gray-300 text-gray-700 hover:bg-gray-50">
                            Batalkan
                        </a>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
