<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard Rekap Panitia
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h1 class="text-2xl font-bold text-gray-900 mb-6">Dashboard Rekap Pelatihan</h1>

                    @if ($pelatihans->isEmpty())
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada pelatihan</h3>
                            <p class="mt-1 text-sm text-gray-500">Data rekap akan muncul setelah ada pelatihan.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pelatihan</th>
                                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Terverifikasi</th>
                                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Menunggu</th>
                                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Ditolak</th>
                                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Hadir</th>
                                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Asrama</th>
                                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Sertifikat</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($pelatihans as $pelatihan)
                                        <tr>
                                            <td class="px-4 py-4">
                                                <div class="text-sm font-medium text-gray-900">{{ $pelatihan->nama }}</div>
                                                <div class="text-xs text-gray-500">{{ $pelatihan->tanggal_mulai->format('d M Y') }} - {{ $pelatihan->tanggal_selesai->format('d M Y') }}</div>
                                            </td>
                                            <td class="px-4 py-4 text-center text-sm text-gray-900 font-bold">{{ $pelatihan->total_pendaftar }}</td>
                                            <td class="px-4 py-4 text-center text-sm text-green-600 font-medium">{{ $pelatihan->terverifikasi }}</td>
                                            <td class="px-4 py-4 text-center text-sm text-yellow-600 font-medium">{{ $pelatihan->menunggu_verifikasi }}</td>
                                            <td class="px-4 py-4 text-center text-sm text-red-600 font-medium">{{ $pelatihan->ditolak }}</td>
                                            <td class="px-4 py-4 text-center text-sm text-blue-600 font-medium">{{ $pelatihan->hadir }}</td>
                                            <td class="px-4 py-4 text-center text-sm text-gray-600">{{ $pelatihan->butuh_asrama }}</td>
                                            <td class="px-4 py-4 text-center text-sm text-gray-600">{{ $pelatihan->sertifikat_terunduh }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $pelatihans->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
