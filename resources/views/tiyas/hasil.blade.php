<x-app-layout>
    <div class="py-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header dengan judul, nama pelatihan, dan tombol Unduh CSV (selalu tampil) -->
            <div class="mb-6">
                <div class="flex flex-col sm:flex-row sm:items-baseline sm:justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-bold" style="color: var(--color-text);">Hasil Evaluasi TIYAS</h1>
                        <p style="color: var(--color-text); opacity: 0.7;" class="mt-1">{{ $pelatihan->nama }}</p>
                    </div>
                    <a href="{{ route('tiyas.export', $pelatihan) }}" class="px-4 py-2 rounded-lg font-medium hover:opacity-90 w-full sm:w-auto text-center" style="background-color: var(--color-success); color: white;">
                        Unduh CSV
                    </a>
                </div>
            </div>

            @if ($totalResponses === 0)
                <x-card>
                    <div class="text-center py-8">
                        <p style="color: var(--color-text);">Belum ada peserta yang mengisi evaluasi.</p>
                    </div>
                </x-card>
            @else
                <!-- Ringkasan: N dari M peserta terverifikasi sudah mengisi evaluasi -->
                <x-card class="mb-6">
                    <div class="flex flex-col gap-3">
                        <p style="color: var(--color-text);">
                            <strong>{{ $totalResponses }}</strong> dari <strong>{{ $totalDiverifikasi }}</strong> peserta terverifikasi sudah mengisi evaluasi.
                        </p>
                        <!-- Batang progres -->
                        <div class="w-full" style="background-color: color-mix(in srgb, var(--color-text) 15%, transparent); border-radius: var(--radius-btn); overflow: hidden; height: 8px;">
                            <div style="background-color: var(--color-primary); height: 100%; width: {{ $totalDiverifikasi > 0 ? round(($totalResponses / $totalDiverifikasi) * 100) : 0 }}%;"></div>
                        </div>
                    </div>
                </x-card>

                <!-- Empat kartu skor: grid 2 kolom di HP, 4 kolom di desktop -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    @foreach (['TC', 'TE', 'ITT', 'YB'] as $kode)
                        @php
                            $konstruk = config('tiyas.konstruk')[$kode];
                            $data = $skorRataRata[$kode];
                            $judul = $konstruk['judul'];
                            $skor = $data['skor'];
                            $kategori = $data['kategori'];
                            $warna = match($kategori) {
                                'Tinggi' => 'var(--color-success)',
                                'Sedang' => 'var(--color-accent)',
                                'Rendah' => 'var(--color-danger)',
                                default => 'var(--color-text)',
                            };
                        @endphp
                        <x-card>
                            <div class="flex flex-col h-full">
                                <!-- Nama konstruk dan kode -->
                                <h3 class="font-semibold text-sm sm:text-base" style="color: var(--color-text);">{{ $judul }}</h3>
                                <p style="color: var(--color-text); opacity: 0.6;" class="text-xs">{{ $kode }}</p>

                                <!-- Skor besar -->
                                <div class="text-3xl sm:text-4xl font-bold my-2" style="color: var(--color-primary);">
                                    {{ number_format($skor, 2) }}
                                </div>
                                <p style="color: var(--color-text); opacity: 0.7;" class="text-xs mb-3">dari 4,00</p>

                                <!-- Batang progres horizontal -->
                                <div class="w-full" style="background-color: color-mix(in srgb, var(--color-text) 15%, transparent); border-radius: 2px; overflow: hidden; height: 6px; margin-bottom: 8px;">
                                    <div style="background-color: {{ $warna }}; height: 100%; width: {{ round(($skor / 4) * 100) }}%;"></div>
                                </div>

                                <!-- Badge kategori -->
                                <div class="inline-block px-2 py-1 rounded text-xs font-semibold text-white w-fit" style="background-color: {{ $warna }};">
                                    {{ $kategori }}
                                </div>
                            </div>
                        </x-card>
                    @endforeach
                </div>

                <!-- Legenda: membaca dari config('tiyas.kategori') -->
                <x-card class="mb-6">
                    <p style="color: var(--color-text); opacity: 0.7;" class="text-xs">
                        Skala 1–4.
                        @php
                            $kategori = config('tiyas.kategori');
                            $legenda = [];
                            foreach ($kategori as $item) {
                                $legenda[] = $item['label'] . ' ≤ ' . number_format($item['maks'], 2);
                            }
                        @endphp
                        {{ implode(' · ', $legenda) }}
                    </p>
                </x-card>

                <!-- Ringkasan Per Peserta: kartu di HP, tabel di md+ -->
                <x-card class="mb-6">
                    <h2 class="text-xl font-semibold mb-4" style="color: var(--color-text);">Ringkasan Per Peserta</h2>

                    <!-- Kartu per peserta (md:hidden) -->
                    <div class="md:hidden space-y-4">
                        @foreach ($dataPerPeserta as $data)
                            <div style="border: 1px solid color-mix(in srgb, var(--color-text) 15%, transparent); border-radius: var(--radius-card); padding: 1rem;">
                                <div class="font-medium mb-3" style="color: var(--color-text);">{{ $data['nama'] }}</div>
                                <div class="grid grid-cols-2 gap-2 mb-3">
                                    @foreach (['TC', 'TE', 'ITT', 'YB'] as $kode)
                                        @php
                                            $skorData = $data['skor'][$kode];
                                            $skorVal = $skorData['skor'];
                                            $kategoriVal = $skorData['kategori'];
                                            $warnaVal = match($kategoriVal) {
                                                'Tinggi' => 'var(--color-success)',
                                                'Sedang' => 'var(--color-accent)',
                                                'Rendah' => 'var(--color-danger)',
                                                default => 'var(--color-text)',
                                            };
                                        @endphp
                                        <div class="text-xs">
                                            <span style="color: var(--color-text); opacity: 0.7;">{{ $kode }}</span>
                                            <div style="color: var(--color-primary);" class="font-semibold">{{ number_format($skorVal, 2) }}</div>
                                            <div style="background-color: {{ $warnaVal }}; color: white;" class="inline-block px-1 py-0.5 rounded text-xs mt-1">{{ $kategoriVal }}</div>
                                        </div>
                                    @endforeach
                                </div>
                                <a href="{{ route('tiyas.detail', $data['id']) }}" style="color: var(--color-primary);" class="text-sm hover:opacity-80">Detail →</a>
                            </div>
                        @endforeach
                    </div>

                    <!-- Tabel per peserta (hidden md:block) -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="min-w-full divide-y" style="border-color: color-mix(in srgb, var(--color-text) 15%, transparent);">
                            <thead style="background-color: var(--color-bg);">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--color-text);">Nama Peserta</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider" style="color: var(--color-text);">TC</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider" style="color: var(--color-text);">TE</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider" style="color: var(--color-text);">ITT</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider" style="color: var(--color-text);">YB</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--color-text);">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y" style="border-color: color-mix(in srgb, var(--color-text) 15%, transparent);">
                                @foreach ($dataPerPeserta as $data)
                                    <tr style="background-color: transparent;" class="hover:opacity-75">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" style="color: var(--color-text);">{{ $data['nama'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm" style="color: var(--color-text);">
                                            {{ number_format($data['skor']['TC']['skor'], 2) }}
                                            <span style="opacity: 0.6;">({{ $data['skor']['TC']['kategori'] }})</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm" style="color: var(--color-text);">
                                            {{ number_format($data['skor']['TE']['skor'], 2) }}
                                            <span style="opacity: 0.6;">({{ $data['skor']['TE']['kategori'] }})</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm" style="color: var(--color-text);">
                                            {{ number_format($data['skor']['ITT']['skor'], 2) }}
                                            <span style="opacity: 0.6;">({{ $data['skor']['ITT']['kategori'] }})</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm" style="color: var(--color-text);">
                                            {{ number_format($data['skor']['YB']['skor'], 2) }}
                                            <span style="opacity: 0.6;">({{ $data['skor']['YB']['kategori'] }})</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-left">
                                            <a href="{{ route('tiyas.detail', $data['id']) }}" style="color: var(--color-primary);" class="hover:opacity-80">Detail</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>

                <!-- Rata-rata Per Dimensi: kartu di HP, tabel di md+ -->
                <x-card class="mb-6">
                    <h2 class="text-xl font-semibold mb-4" style="color: var(--color-text);">Rata-rata Per Dimensi</h2>

                    <!-- Kartu per konstruk (md:hidden) -->
                    <div class="md:hidden space-y-4">
                        @foreach (config('tiyas.konstruk') as $kodeKonstruk => $konstruk)
                            <div style="border: 1px solid color-mix(in srgb, var(--color-text) 15%, transparent); border-radius: var(--radius-card); padding: 1rem;">
                                <h3 class="font-semibold text-sm mb-2" style="color: var(--color-text);">{{ $konstruk['judul'] }}</h3>
                                <div class="space-y-1">
                                    @foreach ($konstruk['dimensi'] as $namaDimensi => $butirs)
                                        <div class="flex justify-between text-xs">
                                            <span style="color: var(--color-text); opacity: 0.8;">{{ $namaDimensi }}</span>
                                            <span style="color: var(--color-primary); font-medium;">{{ $rataRataDimensi[$kodeKonstruk][$namaDimensi] ?? 0 }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Tabel dengan rowspan di md+ (hidden md:block) -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="min-w-full divide-y" style="border-color: color-mix(in srgb, var(--color-text) 15%, transparent);">
                            <thead style="background-color: var(--color-bg);">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--color-text);">Konstruk</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider" style="color: var(--color-text);">Dimensi</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider" style="color: var(--color-text);">Rata-rata</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y" style="border-color: color-mix(in srgb, var(--color-text) 15%, transparent);">
                                @foreach (config('tiyas.konstruk') as $kodeKonstruk => $konstruk)
                                    @php
                                        $dimensiCount = count($konstruk['dimensi']);
                                    @endphp
                                    @foreach ($konstruk['dimensi'] as $namaDimensi => $butirs)
                                        <tr style="background-color: transparent;" class="hover:opacity-75">
                                            @if ($loop->first)
                                                <td rowspan="{{ $dimensiCount }}" class="px-6 py-4 font-medium align-top" style="color: var(--color-text);">
                                                    {{ $konstruk['judul'] }}
                                                </td>
                                            @endif
                                            <td class="px-6 py-4 text-sm" style="color: var(--color-text);">{{ $namaDimensi }}</td>
                                            <td class="px-6 py-4 text-center text-sm font-medium" style="color: var(--color-primary);">{{ $rataRataDimensi[$kodeKonstruk][$namaDimensi] ?? 0 }}</td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>

                <!-- Profil Klaster Peserta (Fuzzy C-Means) -->
                @if ($klasterResult['status'] === 'kurang_data')
                    <x-card class="mb-6">
                        <h2 class="text-xl font-semibold mb-4" style="color: var(--color-text);">Profil Klaster Peserta (Fuzzy C-Means)</h2>
                        <div style="background-color: color-mix(in srgb, var(--color-accent) 10%, transparent); border-left: 4px solid var(--color-accent); padding: 1rem; border-radius: var(--radius-card);">
                            <p style="color: var(--color-text);" class="text-sm">
                                Profil klaster butuh minimal 8 peserta yang sudah mengisi. Saat ini: <strong>{{ $klasterResult['jumlah_sekarang'] }}</strong> peserta.
                            </p>
                        </div>
                    </x-card>
                @else
                    <x-card class="mb-6">
                        <h2 class="text-xl font-semibold mb-4" style="color: var(--color-text);">Profil Klaster Peserta (Fuzzy C-Means)</h2>

                        <!-- Ringkasan: c terpilih dan tabel indeks -->
                        <div class="mb-6">
                            <p style="color: var(--color-text);" class="mb-3 text-sm">
                                <strong>Jumlah klaster terpilih:</strong> {{ $klasterResult['c_optimal'] }}
                            </p>
                            <p style="color: var(--color-text); opacity: 0.7;" class="text-xs mb-3">Indeks validitas per jumlah klaster:</p>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-xs" style="border-collapse: collapse;">
                                    <thead>
                                        <tr style="border-bottom: 1px solid color-mix(in srgb, var(--color-text) 15%, transparent);">
                                            <th class="text-left px-2 py-2" style="color: var(--color-text);">c</th>
                                            <th class="text-center px-2 py-2" style="color: var(--color-text);">PC</th>
                                            <th class="text-center px-2 py-2" style="color: var(--color-text);">PE</th>
                                            <th class="text-center px-2 py-2" style="color: var(--color-text);">XB</th>
                                            <th class="text-center px-2 py-2" style="color: var(--color-text);">Silhouette</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($klasterResult['validity_table'] as $row)
                                            <tr style="border-bottom: 1px solid color-mix(in srgb, var(--color-text) 10%, transparent);" class="hover:opacity-75">
                                                <td class="px-2 py-2" style="color: var(--color-text);">
                                                    <strong>{{ $row['c'] }}</strong>
                                                    @if ($row['is_optimal'])
                                                        <span style="background-color: var(--color-success); color: white;" class="ml-1 px-1.5 py-0.5 rounded text-xs">Terpilih</span>
                                                    @endif
                                                </td>
                                                <td class="text-center px-2 py-2" style="color: var(--color-primary);">{{ number_format($row['pc'], 4) }}</td>
                                                <td class="text-center px-2 py-2" style="color: var(--color-primary);">{{ number_format($row['pe'], 4) }}</td>
                                                <td class="text-center px-2 py-2" style="color: var(--color-primary);">{{ number_format($row['xb'], 4) }}</td>
                                                <td class="text-center px-2 py-2" style="color: var(--color-primary);">{{ number_format($row['silhouette'], 4) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Centroid dan profil per klaster -->
                        <div class="mb-6">
                            <p style="color: var(--color-text); opacity: 0.7;" class="text-xs mb-3">Profil klaster berdasarkan nilai centroid:</p>
                            <div class="space-y-4">
                                @foreach ($klasterResult['tabel_klaster'] as $klaster)
                                    <div style="border: 1px solid color-mix(in srgb, var(--color-text) 15%, transparent); border-radius: var(--radius-card); padding: 1rem;">
                                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-2 mb-3">
                                            <div>
                                                <h3 class="font-semibold" style="color: var(--color-text);">Klaster {{ $klaster['klaster'] }}: {{ $klaster['profil'] }}</h3>
                                                <p style="color: var(--color-text); opacity: 0.6;" class="text-xs">{{ $klaster['jumlah_peserta'] }} peserta</p>
                                            </div>
                                            <span style="background-color: var(--color-accent); color: white;" class="inline-block px-2 py-1 rounded text-xs whitespace-nowrap">
                                                Centroid
                                            </span>
                                        </div>

                                        <!-- Centroid values (TC, TE, ITT, YB) -->
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-3">
                                            <div>
                                                <p style="color: var(--color-text); opacity: 0.7;" class="text-xs">TC</p>
                                                <p style="color: var(--color-primary);" class="font-semibold">{{ number_format($klaster['centroid']['TC'], 2) }}</p>
                                            </div>
                                            <div>
                                                <p style="color: var(--color-text); opacity: 0.7;" class="text-xs">TE</p>
                                                <p style="color: var(--color-primary);" class="font-semibold">{{ number_format($klaster['centroid']['TE'], 2) }}</p>
                                            </div>
                                            <div>
                                                <p style="color: var(--color-text); opacity: 0.7;" class="text-xs">ITT</p>
                                                <p style="color: var(--color-primary);" class="font-semibold">{{ number_format($klaster['centroid']['ITT'], 2) }}</p>
                                            </div>
                                            <div>
                                                <p style="color: var(--color-text); opacity: 0.7;" class="text-xs">YB</p>
                                                <p style="color: var(--color-primary);" class="font-semibold">{{ number_format($klaster['centroid']['YB'], 2) }}</p>
                                            </div>
                                        </div>

                                        <!-- Karakteristik -->
                                        <p style="color: var(--color-text);" class="text-sm mb-2">
                                            <strong>Karakteristik:</strong> {{ $klaster['karakteristik'] }}
                                        </p>

                                        <!-- Rekomendasi -->
                                        <p style="color: var(--color-text);" class="text-sm">
                                            <strong>Rekomendasi:</strong> {{ $klaster['rekomendasi'] }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Tabel derajat keanggotaan peserta -->
                        <div>
                            <p style="color: var(--color-text); opacity: 0.7;" class="text-xs mb-3">Derajat keanggotaan (%): mewakili probabilitas peserta terhadap tiap klaster</p>

                            <!-- Tabel scrollable (md:hidden untuk kartu di HP) -->
                            <div class="md:hidden space-y-3">
                                @foreach ($klasterResult['tabel_peserta'] as $peserta)
                                    <div style="border: 1px solid color-mix(in srgb, var(--color-text) 15%, transparent); border-radius: var(--radius-card); padding: 1rem;">
                                        <p class="font-medium text-sm mb-2" style="color: var(--color-text);">{{ $peserta['nama'] }}</p>
                                        <p class="text-xs mb-2" style="color: var(--color-primary); font-semibold;">Klaster dominan: {{ $peserta['klaster_dominan'] }}</p>
                                        <div class="space-y-1">
                                            @foreach ($peserta['memberships'] as $k => $m)
                                                <div class="flex justify-between text-xs">
                                                    <span style="color: var(--color-text);">Klaster {{ $k }}</span>
                                                    <span style="color: var(--color-text); font-medium;">{{ $m }}%</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Tabel di desktop (hidden md:block) -->
                            <div class="hidden md:block overflow-x-auto">
                                <table class="min-w-full text-sm divide-y" style="border-color: color-mix(in srgb, var(--color-text) 15%, transparent);">
                                    <thead style="background-color: var(--color-bg);">
                                        <tr>
                                            <th class="px-4 py-3 text-left font-medium" style="color: var(--color-text);">Peserta</th>
                                            @foreach (range(0, $klasterResult['c_optimal'] - 1) as $k)
                                                <th class="px-4 py-3 text-center font-medium" style="color: var(--color-text);">Klaster {{ $k }}</th>
                                            @endforeach
                                            <th class="px-4 py-3 text-center font-medium" style="color: var(--color-text);">Dominan</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y" style="border-color: color-mix(in srgb, var(--color-text) 15%, transparent);">
                                        @foreach ($klasterResult['tabel_peserta'] as $peserta)
                                            <tr style="background-color: transparent;" class="hover:opacity-75">
                                                <td class="px-4 py-3 font-medium" style="color: var(--color-text);">{{ $peserta['nama'] }}</td>
                                                @foreach ($peserta['memberships'] as $m)
                                                    <td class="px-4 py-3 text-center" style="color: var(--color-primary);">{{ $m }}%</td>
                                                @endforeach
                                                <td class="px-4 py-3 text-center font-semibold" style="color: var(--color-success);">{{ $peserta['klaster_dominan'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Catatan kecil -->
                        <div class="mt-4 pt-4" style="border-top: 1px solid color-mix(in srgb, var(--color-text) 15%, transparent);">
                            <p style="color: var(--color-text); opacity: 0.6;" class="text-xs">
                                <strong>Catatan:</strong> FCM (m=2). Skor memakai rerata skala 1–4, bukan estimasi PCM.
                            </p>
                        </div>
                    </x-card>
                @endif
            @endif
            <div class="mt-6">
                <a href="{{ route('pelatihans.index') }}" style="color: var(--color-primary);" class="hover:opacity-80">← Kembali ke Pelatihan</a>
            </div>
        </div>
    </div>
</x-app-layout>
