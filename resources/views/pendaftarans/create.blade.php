<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Daftar Pelatihan: {{ $pelatihan->nama }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    @if (session('error'))
                        <div class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-700">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('pelatihans.daftar', $pelatihan) }}" method="POST" enctype="multipart/form-data" id="daftarForm">
                        @csrf

                        <!-- Bagian 1: Data Diri -->
                        <fieldset class="mb-8 border border-gray-200 rounded-md p-4">
                            <legend class="text-lg font-semibold text-gray-900 px-2">1. Data Diri</legend>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <label for="nama" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                                    <input type="text" name="nama" id="nama" value="{{ old('nama') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    @error('nama') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="nik" class="block text-sm font-medium text-gray-700">NIK</label>
                                    <input type="text" name="nik" id="nik" value="{{ old('nik') }}" required maxlength="16" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    @error('nik') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="kontak" class="block text-sm font-medium text-gray-700">Kontak (No. HP / Email)</label>
                                    <input type="text" name="kontak" id="kontak" value="{{ old('kontak') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    @error('kontak') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="profesi" class="block text-sm font-medium text-gray-700">Profesi</label>
                                    <input type="text" name="profesi" id="profesi" value="{{ old('profesi') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    @error('profesi') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="instansi" class="block text-sm font-medium text-gray-700">Instansi Asal</label>
                                    <input type="text" name="instansi" id="instansi" value="{{ old('instansi') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    @error('instansi') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </fieldset>

                        <!-- Bagian 2: Dokumen -->
                        <fieldset class="mb-8 border border-gray-200 rounded-md p-4">
                            <legend class="text-lg font-semibold text-gray-900 px-2">2. Dokumen</legend>

                            <div class="space-y-4">
                                <div>
                                    <label for="surat_tugas" class="block text-sm font-medium text-gray-700">Surat Tugas <span class="text-gray-500 font-normal">(opsional)</span></label>
                                    <input type="file" name="surat_tugas" id="surat_tugas" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                    <p class="mt-1 text-xs text-gray-500">PDF, JPG, PNG (maks. 2MB)</p>
                                    @error('surat_tugas') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="dokumen_lain" class="block text-sm font-medium text-gray-700">Dokumen Persyaratan Lain <span class="text-gray-500 font-normal">(opsional)</span></label>
                                    <input type="file" name="dokumen_lain" id="dokumen_lain" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                    <p class="mt-1 text-xs text-gray-500">PDF, JPG, PNG (maks. 2MB)</p>
                                    @error('dokumen_lain') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </fieldset>

                        <!-- Bagian 3: Asrama -->
                        <fieldset class="mb-8 border border-gray-200 rounded-md p-4 bg-gray-50">
                            <legend class="text-lg font-semibold text-gray-900 px-2">3. Asrama <span class="text-sm font-normal text-gray-500">(opsional)</span></legend>

                            <div class="mb-4">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="butuh_asrama" id="butuh_asrama" value="1" {{ old('butuh_asrama') ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm font-medium text-gray-700">Saya butuh menginap</span>
                                </label>
                            </div>

                            <div id="asrama-fields" class="hidden grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="check_in" class="block text-sm font-medium text-gray-700">Tanggal Check-in</label>
                                    <input type="date" name="check_in" id="check_in" value="{{ old('check_in') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    @error('check_in') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="check_out" class="block text-sm font-medium text-gray-700">Tanggal Check-out</label>
                                    <input type="date" name="check_out" id="check_out" value="{{ old('check_out') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    @error('check_out') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </fieldset>

                        <!-- Bagian 4: Konfirmasi -->
                        <fieldset class="mb-8 border border-gray-200 rounded-md p-4">
                            <legend class="text-lg font-semibold text-gray-900 px-2">4. Konfirmasi</legend>

                            <div class="rounded-md bg-blue-50 p-4 mb-4">
                                <h4 class="text-sm font-medium text-blue-800 mb-2">Ringkasan Pendaftaran</h4>
                                <dl class="text-sm text-blue-700 space-y-1">
                                    <div><dt class="font-medium">Pelatihan</dt><dd>{{ $pelatihan->nama }}</dd></div>
                                    <div><dt class="font-medium">Tanggal Pelatihan</dt><dd>{{ $pelatihan->tanggal_mulai->format('d M Y') }} - {{ $pelatihan->tanggal_selesai->format('d M Y') }}</dd></div>
                                </dl>
                            </div>

                            <p class="text-sm text-gray-600 mb-4">Dengan menekan tombol di bawah, Anda menyatakan bahwa data yang diisi adalah benar.</p>

                            <div class="flex justify-end space-x-3">
                                <a href="{{ route('pelatihans.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Batal
                                </a>
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Kirim Pendaftaran
                                </button>
                            </div>
                        </fieldset>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkbox = document.getElementById('butuh_asrama');
            const fields = document.getElementById('asrama-fields');

            function toggleAsrama() {
                if (checkbox.checked) {
                    fields.classList.remove('hidden');
                } else {
                    fields.classList.add('hidden');
                    document.getElementById('check_in').value = '';
                    document.getElementById('check_out').value = '';
                }
            }

            checkbox.addEventListener('change', toggleAsrama);
            toggleAsrama();
        });
    </script>
    @endpush
</x-app-layout>
