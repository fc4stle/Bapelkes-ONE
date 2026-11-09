<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Statistik Pelatihan
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Kartu Ringkasan Global -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Pelatihan -->
                <div class="rounded-lg p-6" style="background-color: var(--color-bg); border-left: 4px solid var(--color-primary);">
                    <div class="flex items-baseline justify-between">
                        <p class="text-sm font-medium text-gray-600">Total Pelatihan</p>
                        <span class="text-3xl font-bold" style="color: var(--color-primary);">{{ $totalPelatihan }}</span>
                    </div>
                </div>

                <!-- Peserta Unik Terverifikasi -->
                <div class="rounded-lg p-6" style="background-color: var(--color-bg); border-left: 4px solid var(--color-primary);">
                    <div class="flex items-baseline justify-between">
                        <p class="text-sm font-medium text-gray-600">Peserta Unik Terverifikasi</p>
                        <span class="text-3xl font-bold" style="color: var(--color-primary);">{{ $pesertaTerverifikasi }}</span>
                    </div>
                </div>

                <!-- Total Pendaftaran Terverifikasi -->
                <div class="rounded-lg p-6" style="background-color: var(--color-bg); border-left: 4px solid var(--color-accent);">
                    <div class="flex items-baseline justify-between">
                        <p class="text-sm font-medium text-gray-600">Total Pendaftaran Terverifikasi</p>
                        <span class="text-3xl font-bold" style="color: var(--color-accent);">{{ $totalPendaftaranTerverifikasi }}</span>
                    </div>
                </div>

                <!-- Tingkat Kehadiran Global -->
                <div class="rounded-lg p-6" style="background-color: var(--color-bg); border-left: 4px solid var(--color-success);">
                    <div class="flex items-baseline justify-between">
                        <p class="text-sm font-medium text-gray-600">Tingkat Kehadiran Global</p>
                        <span class="text-3xl font-bold" style="color: var(--color-success);">
                            {{ $tingkatKehadiranGlobal !== null ? $tingkatKehadiranGlobal . '%' : '-' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Rata-rata TIYAS Global (jika ada) -->
            @if ($rataRataTiyasGlobal !== null)
                <div class="rounded-lg p-6 mb-8" style="background-color: var(--color-bg);">
                    <div class="flex items-baseline justify-between">
                        <p class="text-sm font-medium text-gray-600">Rata-rata Skor TIYAS Global</p>
                        <span class="text-3xl font-bold" style="color: var(--color-primary);">{{ $rataRataTiyasGlobal }}</span>
                    </div>
                </div>
            @endif

            <!-- Tabel Pelatihan per Baris -->
            @if ($pelatihans->isEmpty())
                <div class="rounded-lg p-8 text-center" style="background-color: var(--color-bg);">
                    <p class="text-gray-600">Belum ada data pelatihan.</p>
                </div>
            @else
                <div class="rounded-lg overflow-hidden" style="background-color: var(--color-bg);">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr style="background-color: rgba(0,0,0,0.02); border-bottom: 1px solid #e5e7eb;">
                                    <th class="px-6 py-3 text-left font-semibold" style="color: var(--color-text);">Nama Pelatihan</th>
                                    <th class="px-6 py-3 text-left font-semibold" style="color: var(--color-text);">Tanggal</th>
                                    <th class="px-6 py-3 text-left font-semibold" style="color: var(--color-text);">Status</th>
                                    <th class="px-6 py-3 text-center font-semibold" style="color: var(--color-text);">Kuota</th>
                                    <th class="px-6 py-3 text-center font-semibold" style="color: var(--color-text);">Pendaftar</th>
                                    <th class="px-6 py-3 text-center font-semibold" style="color: var(--color-text);">Terverifikasi</th>
                                    <th class="px-6 py-3 text-center font-semibold" style="color: var(--color-text);">Hadir</th>
                                    <th class="px-6 py-3 text-center font-semibold" style="color: var(--color-text);">% Hadir</th>
                                    <th class="px-6 py-3 text-center font-semibold" style="color: var(--color-text);">Rata-rata TIYAS</th>
                                    <th class="px-6 py-3 text-center font-semibold" style="color: var(--color-text);">Keterisian Asrama</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pelatihans as $pelatihan)
                                    <tr style="border-bottom: 1px solid #e5e7eb;">
                                        <!-- Nama Pelatihan -->
                                        <td class="px-6 py-4" style="color: var(--color-text);">
                                            {{ $pelatihan['nama'] }}
                                        </td>

                                        <!-- Tanggal -->
                                        <td class="px-6 py-4" style="color: var(--color-text);">
                                            {{ \Carbon\Carbon::parse($pelatihan['tanggal_mulai'])->format('d M Y') }}
                                        </td>

                                        <!-- Status -->
                                        <td class="px-6 py-4">
                                            @php
                                                $statusColors = [
                                                    'draft' => 'bg-gray-100 text-gray-800',
                                                    'dibuka' => 'bg-green-100 text-green-800',
                                                    'ditutup' => 'bg-yellow-100 text-yellow-800',
                                                    'selesai' => 'bg-blue-100 text-blue-800',
                                                ];
                                            @endphp
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$pelatihan['status']->value] ?? 'bg-gray-100 text-gray-800' }}">
                                                {{ $pelatihan['status']->label() }}
                                            </span>
                                        </td>

                                        <!-- Kuota -->
                                        <td class="px-6 py-4 text-center" style="color: var(--color-text);">
                                            {{ $pelatihan['kuota'] }}
                                        </td>

                                        <!-- Pendaftar -->
                                        <td class="px-6 py-4 text-center" style="color: var(--color-text);">
                                            {{ $pelatihan['total_pendaftar'] }}
                                        </td>

                                        <!-- Terverifikasi -->
                                        <td class="px-6 py-4 text-center" style="color: var(--color-text);">
                                            {{ $pelatihan['total_terverifikasi'] }}
                                        </td>

                                        <!-- Hadir -->
                                        <td class="px-6 py-4 text-center" style="color: var(--color-text);">
                                            {{ $pelatihan['total_hadir'] }}
                                        </td>

                                        <!-- % Hadir dengan Bar -->
                                        <td class="px-6 py-4">
                                            @if ($pelatihan['persen_hadir'] !== null)
                                                <div class="flex flex-col items-center gap-1">
                                                    <div class="w-full max-w-[120px] h-2 rounded-full" style="background-color: #e5e7eb; overflow: hidden;">
                                                        <div class="h-full" style="width: {{ $pelatihan['persen_hadir'] }}%; background-color: var(--color-primary); transition: width 0.3s ease;"></div>
                                                    </div>
                                                    <span class="text-xs font-semibold" style="color: var(--color-text);">{{ $pelatihan['persen_hadir'] }}%</span>
                                                </div>
                                            @else
                                                <span class="text-gray-500">-</span>
                                            @endif
                                        </td>

                                        <!-- Rata-rata TIYAS -->
                                        <td class="px-6 py-4 text-center" style="color: var(--color-text);">
                                            {{ $pelatihan['rata_rata_tiyas'] !== null ? $pelatihan['rata_rata_tiyas'] : '-' }}
                                        </td>

                                        <!-- Keterisian Asrama -->
                                        <td class="px-6 py-4 text-center" style="color: var(--color-text);">
                                            {{ $pelatihan['keterisian_asrama'] ?? '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
