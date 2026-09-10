<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Sertifikat Digital
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6 text-center">
                    <svg class="mx-auto h-16 w-16 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">Sertifikat Belum Tersedia</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        {{ $alasan }}
                    </p>
                    <p class="mt-1 text-sm text-gray-500">
                        Pelatihan: <strong>{{ $pendaftaran->pelatihan->nama }}</strong>
                    </p>
                    <div class="mt-6">
                        <a href="{{ route('pendaftarans.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Kembali ke Daftar Pendaftaran
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
