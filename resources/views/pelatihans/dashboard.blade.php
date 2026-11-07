<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard Rekap Panitia
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Bagian 1: Kartu Perlu Tindakan -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Menunggu Verifikasi -->
                <div class="rounded-lg p-6" style="background-color: var(--color-krem); border-left: 4px solid var(--color-marun);">
                    <div class="flex items-baseline justify-between">
                        <p class="text-sm font-medium text-gray-600">Menunggu Verifikasi</p>
                        <span class="text-3xl font-bold" style="color: var(--color-marun);">{{ $total_pending }}</span>
                    </div>
                    <div class="mt-4">
                        @if ($total_pending > 0 && $pelatihan_pending_terbanyak)
                            <a href="{{ route('pelatihan.pendaftar', $pelatihan_pending_terbanyak) }}" 
                               class="inline-block px-3 py-2 text-sm font-medium rounded transition"
                               style="background-color: var(--color-marun); color: white;">
                                Tinjau
                            </a>
                        @else
                            <span class="text-sm text-gray-500">Tidak ada</span>
                        @endif
                    </div>
                </div>

                <!-- Pelatihan Perhatian (0 Pendaftar atau >=90% Kuota) -->
                <div class="rounded-lg p-6" style="background-color: var(--color-krem); border-left: 4px solid var(--color-emas);">
                    <div class="flex items-baseline justify-between">
                        <p class="text-sm font-medium text-gray-600">Perhatian Kuota</p>
                        <span class="text-3xl font-bold" style="color: var(--color-emas);">{{ $pelatihan_perhatian }}</span>
                    </div>
                    <div class="mt-4">
                        @if ($pelatihan_perhatian > 0)
                            <a href="{{ route('pelatihans.index') }}" 
                               class="inline-block px-3 py-2 text-sm font-medium rounded transition"
                               style="background-color: var(--color-emas); color: white;">
                                Lihat
                            </a>
                        @else
                            <span class="text-sm text-gray-500">Tidak ada</span>
                        @endif
                    </div>
                </div>

                <!-- Pelatihan Tanpa TIYAS -->
                <div class="rounded-lg p-6" style="background-color: var(--color-krem); border-left: 4px solid #ef4444;">
                    <div class="flex items-baseline justify-between">
                        <p class="text-sm font-medium text-gray-600">Tanpa TIYAS</p>
                        <span class="text-3xl font-bold text-red-600">{{ $pelatihan_tanpa_tiyas }}</span>
                    </div>
                    <div class="mt-4">
                        @if ($pelatihan_tanpa_tiyas > 0)
                            <a href="{{ route('pelatihans.index') }}" 
                               class="inline-block px-3 py-2 text-sm font-medium rounded transition"
                               style="background-color: #ef4444; color: white;">
                                Tinjau
                            </a>
                        @else
                            <span class="text-sm text-gray-500">Tidak ada</span>
                        @endif
                    </div>
                </div>

                <!-- Peserta Butuh Asrama (Future - placeholder) -->
                <div class="rounded-lg p-6" style="background-color: var(--color-krem); border-left: 4px solid var(--color-marun);">
                    <div class="flex items-baseline justify-between">
                        <p class="text-sm font-medium text-gray-600">Asrama Pending</p>
                        <span class="text-3xl font-bold" style="color: var(--color-marun);">0</span>
                    </div>
                    <div class="mt-4">
                        <span class="text-sm text-gray-500">Tidak ada</span>
                    </div>
                </div>
            </div>

            <!-- Bagian 2: Antrian Verifikasi -->
            <div class="mb-8" style="background-color: var(--color-krem); border-radius: 0.5rem; padding: 1.5rem;">
                <h3 class="text-lg font-semibold mb-6" style="color: var(--color-marun);">Antrian Verifikasi</h3>
                
                @if ($antrian_verifikasi->isEmpty())
                    <div class="text-center py-8">
                        <p class="text-gray-600">Semua pendaftaran sudah diverifikasi.</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($antrian_verifikasi as $item)
                            <div class="flex items-center justify-between p-4 bg-white rounded-lg border-l-2" style="border-left-color: var(--color-marun);">
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900">{{ $item->peserta->name }}</p>
                                    <p class="text-sm text-gray-600">{{ $item->pelatihan->nama }}</p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Terdaftar: {{ $item->created_at->format('d M Y H:i') }}
                                    </p>
                                </div>
                                <a href="{{ route('pelatihan.pendaftar', $item->pelatihan) }}" 
                                   class="ml-4 px-3 py-2 text-sm font-medium rounded transition"
                                   style="background-color: var(--color-marun); color: white;">
                                    Tinjau
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Bagian 3: Hasil TIYAS Terbaru -->
            <div class="mb-8" style="background-color: var(--color-krem); border-radius: 0.5rem; padding: 1.5rem;">
                <h3 class="text-lg font-semibold mb-6" style="color: var(--color-marun);">Hasil TIYAS Terbaru</h3>
                
                @if ($tiyas_terbaru->isEmpty())
                    <div class="text-center py-8">
                        <p class="text-gray-600">Belum ada evaluasi TIYAS yang masuk.</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($tiyas_terbaru as $item)
                            <div class="flex items-center justify-between p-4 bg-white rounded-lg border-l-2" style="border-left-color: var(--color-emas);">
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900">{{ $item->nama }}</p>
                                    <p class="text-sm text-gray-600">
                                        <span class="font-semibold" style="color: var(--color-emas);">{{ $item->tiyas_responses_count }}</span>
                                        respons TIYAS
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Selesai: {{ $item->tanggal_selesai->format('d M Y') }}
                                    </p>
                                </div>
                                <a href="{{ route('tiyas.hasil', $item) }}" 
                                   class="ml-4 px-3 py-2 text-sm font-medium rounded transition"
                                   style="background-color: var(--color-emas); color: white;">
                                    Lihat
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Bagian 4: Tabel Rekap (Existing) -->
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
                                                <a href="{{ route('pelatihan.pendaftar', $pelatihan) }}" class="font-medium" style="color: var(--color-primary);">{{ $pelatihan->nama }}</a>
                                                <div class="text-xs text-gray-500">{{ $pelatihan->tanggal_mulai->format('d M Y') }} - {{ $pelatihan->tanggal_selesai->format('d M Y') }}</div>
                                            </td>
                                            <td class="px-4 py-4 text-center text-sm text-gray-900 font-bold">{{ $pelatihan->total_pendaftar }}</td>
                                            <td class="px-4 py-4 text-center text-sm text-green-600 font-medium">{{ $pelatihan->terverifikasi }}</td>
                                            <td class="px-4 py-4 text-center text-sm text-yellow-600 font-medium">{{ $pelatihan->menunggu_verifikasi }}</td>
                                            <td class="px-4 py-4 text-center text-sm text-red-600 font-medium">{{ $pelatihan->ditolak }}</td>
                                            <td class="px-4 py-4 text-center text-sm font-medium" style="color: var(--color-primary);">{{ $pelatihan->hadir }}</td>
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
