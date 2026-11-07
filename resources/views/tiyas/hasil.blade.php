<x-app-layout>
    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Hasil Evaluasi TIYAS</h1>
            <p class="text-gray-600 mb-6">{{ $pelatihan->nama }}</p>

            @if ($totalResponses === 0)
                <x-card>
                    <div class="text-center py-8">
                        <p class="text-gray-600">Belum ada peserta yang mengisi evaluasi.</p>
                    </div>
                </x-card>
            @else
                <!-- Ringkasan -->
                <x-card class="mb-6">
                    <p class="text-gray-700 mb-4">
                        <strong>{{ $totalResponses }}</strong> dari <strong>{{ $totalDiverifikasi }}</strong> peserta terverifikasi sudah mengisi evaluasi.
                    </p>
                </x-card>

                <!-- Skor Rata-rata (4 Kartu) -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    @foreach (['TC' => 'Transfer Climate', 'TE' => 'Lingkungan Transfer', 'ITT' => 'Niat Transfer', 'YB' => 'Perilaku Pascapelatihan'] as $kode => $label)
                        <x-card>
                            <h3 class="font-semibold text-gray-900 mb-2">{{ $label }}</h3>
                            <div class="text-4xl font-bold mb-2" style="color: var(--color-primary);">
                                {{ number_format($skorRataRata[$kode]['skor'], 2) }}
                            </div>
                            <div class="inline-block px-3 py-1 rounded-full text-xs font-semibold text-white"
                                @if ($skorRataRata[$kode]['kategori'] === 'Tinggi')
                                    style="background-color: var(--color-success);"
                                @elseif ($skorRataRata[$kode]['kategori'] === 'Sedang')
                                    style="background-color: var(--color-accent);"
                                @else
                                    style="background-color: var(--color-danger);"
                                @endif
                            >
                                {{ $skorRataRata[$kode]['kategori'] }}
                            </div>
                        </x-card>
                    @endforeach
                </div>

                <!-- Tabel Per Peserta -->
                <x-card>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Ringkasan Per Peserta</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead style="background-color: var(--color-bg);">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Nama Peserta</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">TC</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">TE</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">ITT</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">YB</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($dataPerPeserta as $data)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $data['nama'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-700">{{ number_format($data['skor']['TC']['skor'], 2) }} <span class="text-xs text-gray-500">({{ $data['skor']['TC']['kategori'] }})</span></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-700">{{ number_format($data['skor']['TE']['skor'], 2) }} <span class="text-xs text-gray-500">({{ $data['skor']['TE']['kategori'] }})</span></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-700">{{ number_format($data['skor']['ITT']['skor'], 2) }} <span class="text-xs text-gray-500">({{ $data['skor']['ITT']['kategori'] }})</span></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-700">{{ number_format($data['skor']['YB']['skor'], 2) }} <span class="text-xs text-gray-500">({{ $data['skor']['YB']['kategori'] }})</span></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <a href="{{ route('tiyas.detail', $data['id']) }}" class="text-indigo-600 hover:text-indigo-900">
                                                Detail
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Link Export -->
                    <div class="mt-6 pt-6 border-t">
                        <a href="{{ route('tiyas.export', $pelatihan) }}" class="inline-block px-4 py-2 rounded-lg font-medium hover:opacity-90" style="background-color: var(--color-success); color: white;">
                            Download CSV
                        </a>
                    </div>
                </x-card>

                <!-- Tabel Rata-rata Per Dimensi -->
                @if (isset($rataRataDimensi) && !empty($rataRataDimensi))
                    <x-card class="mt-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Rata-rata Per Dimensi</h2>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead style="background-color: var(--color-bg);">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Konstruk</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Dimensi</th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">Rata-rata</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach ($rataRataDimensi as $kodeKonstruk => $dimensis)
                                        @php $isFirstRow = true; @endphp
                                        @foreach ($dimensis as $namaDimensi => $rataRata)
                                            <tr class="hover:bg-gray-50">
                                                @if ($isFirstRow)
                                                    <td rowspan="{{ count($dimensis) }}" class="px-6 py-4 font-medium text-gray-900 align-top">
                                                        {{ $config['konstruk'][$kodeKonstruk]['nama'] }}
                                                    </td>
                                                    @php $isFirstRow = false; @endphp
                                                @endif
                                                <td class="px-6 py-4 text-sm text-gray-700">{{ $namaDimensi }}</td>
                                                <td class="px-6 py-4 text-center text-sm font-medium" style="color: var(--color-primary);">{{ number_format($rataRata, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </x-card>
                @endif
            @endif

            <!-- Kembali -->
            <div class="mt-6">
                <a href="{{ route('pelatihans.index') }}" class="text-indigo-600 hover:text-indigo-900">
                    ← Kembali ke Pelatihan
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
