<x-app-layout>
    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Hasil Evaluasi TIYAS</h1>
            <p class="text-gray-600 mb-6">Pelatihan: <strong>{{ $pelatihan->nama }}</strong></p>

            <!-- Ringkasan -->
            <x-card class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Ringkasan</h2>
                <p class="text-gray-700">
                    <strong>{{ $jumlahSudahMengisi }}</strong> dari <strong>{{ $jumlahTerverifikasi }}</strong> peserta terverifikasi sudah mengisi evaluasi.
                </p>

                @if ($jumlahSudahMengisi == 0)
                    <div class="mt-6 p-4 rounded-lg bg-yellow-50 border border-yellow-200">
                        <p class="text-yellow-800">Belum ada peserta yang mengisi evaluasi.</p>
                    </div>
                @else
                    <!-- Kartu 4 Konstruk Skor Rata-Rata -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
                        @foreach (['TC', 'TE', 'ITT', 'YB'] as $konstruk)
                            @php
                                $skor = $skorAkhir[$konstruk];
                                $bgColor = match ($skor['kategori']) {
                                    'Rendah' => '#ffebee',
                                    'Sedang' => '#fff3e0',
                                    'Tinggi' => '#e8f5e9',
                                    default => '#f5f5f5',
                                };
                                $textColor = match ($skor['kategori']) {
                                    'Rendah' => '#c62828',
                                    'Sedang' => '#e65100',
                                    'Tinggi' => '#2e7d32',
                                    default => '#666',
                                };
                            @endphp
                            <div class="p-4 rounded-lg" style="background-color: {{ $bgColor }};">
                                <p class="text-sm font-semibold text-gray-600 mb-2">{{ $konstruk }}</p>
                                <p class="text-3xl font-bold" style="color: {{ $textColor }};">{{ $skor['skor'] }}</p>
                                <p class="text-xs text-gray-600 mt-2">{{ $skor['kategori'] }}</p>
                            </div>
                        @endforeach
                    </div>

                    <!-- Tabel Rata-Rata Per Dimensi -->
                    <div class="mt-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Rata-Rata Per Dimensi</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm border-collapse">
                                <thead style="background-color: var(--color-bg);">
                                    <tr>
                                        <th class="px-4 py-2 font-semibold text-gray-900">Kode</th>
                                        <th class="px-4 py-2 font-semibold text-gray-900">Dimensi</th>
                                        <th class="px-4 py-2 font-semibold text-gray-900 text-right">Rata-Rata</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($tabelDimensi as $row)
                                        <tr class="border-b border-gray-200">
                                            <td class="px-4 py-2 font-mono text-gray-700">{{ $row['kode'] }}</td>
                                            <td class="px-4 py-2 text-gray-700">{{ $row['label'] }}</td>
                                            <td class="px-4 py-2 text-right font-semibold text-gray-900">{{ $row['rata_rata'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tabel Per Peserta -->
                    <div class="mt-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Detail Per Peserta</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm border-collapse">
                                <thead style="background-color: var(--color-bg);">
                                    <tr>
                                        <th class="px-4 py-2 font-semibold text-gray-900">Nama Peserta</th>
                                        <th class="px-4 py-2 font-semibold text-gray-900 text-center">TC</th>
                                        <th class="px-4 py-2 font-semibold text-gray-900 text-center">TE</th>
                                        <th class="px-4 py-2 font-semibold text-gray-900 text-center">ITT</th>
                                        <th class="px-4 py-2 font-semibold text-gray-900 text-center">YB</th>
                                        <th class="px-4 py-2 font-semibold text-gray-900">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($tabelPeserta as $item)
                                        <tr class="border-b border-gray-200">
                                            <td class="px-4 py-2 text-gray-900">{{ $item['peserta']->name }}</td>
                                            <td class="px-4 py-2 text-center font-semibold">{{ $item['skor']['TC']['skor'] }}</td>
                                            <td class="px-4 py-2 text-center font-semibold">{{ $item['skor']['TE']['skor'] }}</td>
                                            <td class="px-4 py-2 text-center font-semibold">{{ $item['skor']['ITT']['skor'] }}</td>
                                            <td class="px-4 py-2 text-center font-semibold">{{ $item['skor']['YB']['skor'] }}</td>
                                            <td class="px-4 py-2">
                                                <a href="{{ route('tiyas.detail', $item['tiyas_response']) }}" class="text-indigo-600 hover:text-indigo-900 underline">
                                                    Detail
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-4 py-2 text-center text-gray-600">Belum ada peserta yang mengisi evaluasi.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Link Export -->
                    <div class="mt-8">
                        <a href="{{ route('tiyas.export', $pelatihan) }}" class="inline-block px-4 py-2 rounded-lg hover:opacity-90" style="background-color: var(--color-primary); color: white;">
                            Unduh CSV
                        </a>
                    </div>
                @endif
            </x-card>

            <!-- Kembali -->
            <a href="javascript:history.back()" class="text-indigo-600 hover:text-indigo-900 underline">
                ← Kembali
            </a>
        </div>
    </div>
</x-app-layout>
