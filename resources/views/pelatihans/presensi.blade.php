<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Presensi: {{ $pelatihan->nama }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="mb-6">
                        <h1 class="text-2xl font-bold text-gray-900">Presensi Peserta</h1>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ $pelatihan->nama }} &middot;
                            {{ $pelatihan->tanggal_mulai->format('d M Y') }} - {{ $pelatihan->tanggal_selesai->format('d M Y') }}
                            &middot; {{ $pelatihan->lokasi }}
                        </p>
                        <p class="mt-1 text-sm text-gray-500">
                            Masukkan kode presensi dari kartu peserta (hasil scan QR code).
                        </p>
                    </div>

                    @if (session('success'))
                        <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-700 flex items-center">
                            <svg class="h-5 w-5 mr-2 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-700 flex items-center">
                            <svg class="h-5 w-5 mr-2 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            {{ session('error') }}
                        </div>
                    @endif

                    @if (session('warning'))
                        <div class="mb-4 rounded-md bg-yellow-50 p-4 text-sm text-yellow-700 flex items-center">
                            <svg class="h-5 w-5 mr-2 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            {{ session('warning') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('pelatihan.presensi.proses', $pelatihan) }}" class="space-y-4">
                        @csrf
                        <div>
                            <label for="kode_presensi" class="block text-sm font-medium text-gray-700">Kode Presensi</label>
                            <input type="text" name="kode_presensi" id="kode_presensi" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border px-3 py-2 font-mono uppercase"
                                placeholder="Masukkan kode dari QR/Scanner"
                                autofocus>
                            @error('kode_presensi')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Tandai Hadir
                        </button>
                    </form>

                    <div class="mt-6 border-t border-gray-200 pt-4">
                        <p class="text-xs text-gray-400">
                            Tips: Gunakan aplikasi scanner QR code di HP untuk scan kartu peserta, lalu copy-paste hasilnya ke kolom di atas.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
