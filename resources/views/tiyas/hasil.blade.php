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
                @if (empty($deskriptif))
                    <x-card class="mb-6">
                        <h2 class="text-xl font-semibold mb-4" style="color: var(--color-text);">Profil Klaster Peserta (Fuzzy C-Means)</h2>
                        <div style="background-color: color-mix(in srgb, var(--color-accent) 10%, transparent); border-left: 4px solid var(--color-accent); padding: 1rem; border-radius: var(--radius-card);">
                            <p style="color: var(--color-text);" class="text-sm">
                                Belum ada data TIYAS yang terisi untuk pelatihan ini.
                            </p>
                        </div>
                    </x-card>
                @else
                    <x-card class="mb-6">
                        <h2 class="text-xl font-semibold mb-4" style="color: var(--color-text);">Profil Klaster Peserta (Fuzzy C-Means)</h2>

                        <!-- 1. Statistik Deskriptif -->
                        <div class="mb-6">
                            <h3 class="font-semibold mb-3 text-sm" style="color: var(--color-text);">1. Statistik Deskriptif</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-xs" style="border-collapse: collapse;">
                                    <thead>
                                        <tr style="border-bottom: 1px solid color-mix(in srgb, var(--color-text) 15%, transparent);">
                                            <th class="text-left px-2 py-2" style="color: var(--color-text);">Konstruk</th>
                                            <th class="text-center px-2 py-2" style="color: var(--color-text);">Rerata</th>
                                            <th class="text-center px-2 py-2" style="color: var(--color-text);">SD</th>
                                            <th class="text-center px-2 py-2" style="color: var(--color-text);">Min</th>
                                            <th class="text-center px-2 py-2" style="color: var(--color-text);">Maks</th>
                                            <th class="text-left px-2 py-2" style="color: var(--color-text);">Kategori (n / %)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($deskriptif as $konstruk => $stat)
                                            <tr style="border-bottom: 1px solid color-mix(in srgb, var(--color-text) 10%, transparent);">
                                                <td class="px-2 py-2 font-medium" style="color: var(--color-text);">{{ $konstruk }}</td>
                                                <td class="text-center px-2 py-2" style="color: var(--color-primary);">{{ $stat['mean'] }}</td>
                                                <td class="text-center px-2 py-2" style="color: var(--color-primary);">{{ $stat['stdev'] }}</td>
                                                <td class="text-center px-2 py-2" style="color: var(--color-primary);">{{ $stat['min'] }}</td>
                                                <td class="text-center px-2 py-2" style="color: var(--color-primary);">{{ $stat['max'] }}</td>
                                                <td class="px-2 py-2 text-xs" style="color: var(--color-text);">
                                                    Rendah: {{ $stat['kategori']['Rendah']['count'] }} / {{ $stat['kategori']['Rendah']['percent'] }}%<br>
                                                    Sedang: {{ $stat['kategori']['Sedang']['count'] }} / {{ $stat['kategori']['Sedang']['percent'] }}%<br>
                                                    Tinggi: {{ $stat['kategori']['Tinggi']['count'] }} / {{ $stat['kategori']['Tinggi']['percent'] }}%
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        @if ($klasterResult['status'] === 'kurang_data')
                            <!-- Jika kurang_data, tampilkan pesan -->
                            <div style="background-color: color-mix(in srgb, var(--color-accent) 10%, transparent); border-left: 4px solid var(--color-accent); padding: 1rem; border-radius: var(--radius-card); margin-top: 1rem;">
                                <p style="color: var(--color-text);" class="text-sm">
                                    <strong>Profil klaster</strong> butuh minimal 8 peserta yang sudah mengisi. Saat ini: <strong>{{ $klasterResult['jumlah_sekarang'] }}</strong> peserta.
                                </p>
                            </div>
                        @else
                            <!-- Summary: Jumlah klaster terpilih -->
                            <div style="background-color: color-mix(in srgb, var(--color-success) 10%, transparent); border-left: 4px solid var(--color-success); padding: 1rem; border-radius: var(--radius-card); margin-top: 1rem; margin-bottom: 1rem;">
                                <p style="color: var(--color-text);" class="text-sm">
                                    <strong>Jumlah klaster terpilih:</strong> <strong style="color: var(--color-success);">{{ $klasterResult['c_optimal'] }}</strong> | 
                                    <strong>PC:</strong> {{ number_format($klasterResult['fuzzy_partition_coefficient'], 4) }} | 
                                    <strong>PE:</strong> {{ number_format($klasterResult['normalized_classification_entropy'], 4) }}
                                </p>
                            </div>
                            <!-- 2. Tabel Ringkas Klaster -->
                            <div class="mb-6">
                                <h3 class="font-semibold mb-3 text-sm" style="color: var(--color-text);">2. Ringkas Klaster</h3>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-xs" style="border-collapse: collapse;">
                                        <thead>
                                            <tr style="border-bottom: 1px solid color-mix(in srgb, var(--color-text) 15%, transparent);">
                                                <th class="text-left px-2 py-2" style="color: var(--color-text);">Klaster</th>
                                                <th class="text-left px-2 py-2" style="color: var(--color-text);">Profil</th>
                                                <th class="text-center px-2 py-2" style="color: var(--color-text);">N Peserta</th>
                                                <th class="text-left px-2 py-2" style="color: var(--color-text);">TC</th>
                                                <th class="text-left px-2 py-2" style="color: var(--color-text);">TE</th>
                                                <th class="text-left px-2 py-2" style="color: var(--color-text);">ITT</th>
                                                <th class="text-left px-2 py-2" style="color: var(--color-text);">YB</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($klasterResult['tabel_klaster'] as $klaster)
                                                <tr style="border-bottom: 1px solid color-mix(in srgb, var(--color-text) 10%, transparent);">
                                                    <td class="px-2 py-2 font-medium" style="color: var(--color-text);">{{ $klaster['klaster'] }}</td>
                                                    <td class="px-2 py-2" style="color: var(--color-text);">{{ $klaster['profil'] }}</td>
                                                    <td class="text-center px-2 py-2" style="color: var(--color-primary);">{{ $klaster['jumlah_peserta'] }}</td>
                                                    <td class="px-2 py-2" style="color: var(--color-primary);">{{ $klaster['centroid']['TC'] }}</td>
                                                    <td class="px-2 py-2" style="color: var(--color-primary);">{{ $klaster['centroid']['TE'] }}</td>
                                                    <td class="px-2 py-2" style="color: var(--color-primary);">{{ $klaster['centroid']['ITT'] }}</td>
                                                    <td class="px-2 py-2" style="color: var(--color-primary);">{{ $klaster['centroid']['YB'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <!-- 3. Grafik Sebaran Peserta (PCA) -->
                            @if ($pcaData)
                                <div class="mb-6">
                                    <h3 class="font-semibold mb-3 text-sm" style="color: var(--color-text);">3. Grafik Sebaran Peserta (PCA)</h3>
                                    <div class="rounded p-4" style="background-color: color-mix(in srgb, var(--color-bg) 50%, var(--color-text) 5%); border: 1px solid color-mix(in srgb, var(--color-text) 15%, transparent);">
                                        @php
                                            $points = $pcaData['points'];
                                            $variance = $pcaData['variance'];
                                            $tabelPeserta = $klasterResult['tabel_peserta'];
                                            
                                            // Cari scale untuk SVG (padding)
                                            $xVals = array_map(fn($p) => $p[0], $points);
                                            $yVals = array_map(fn($p) => $p[1], $points);
                                            $xMin = min($xVals);
                                            $xMax = max($xVals);
                                            $yMin = min($yVals);
                                            $yMax = max($yVals);
                                            
                                            $xRange = max(0.1, $xMax - $xMin);
                                            $yRange = max(0.1, $yMax - $yMin);
                                            $scale = 250 / max($xRange, $yRange);
                                            
                                            $svgW = 500;
                                            $svgH = 400;
                                            
                                            // Fungsi transform: dari koordinat data ke SVG
                                            $transformX = function($x) use ($xMin, $scale, $svgW) {
                                                return ($x - $xMin) * $scale + 40;
                                            };
                                            $transformY = function($y) use ($yMin, $yMax, $yRange, $scale, $svgH) {
                                                return $svgH - (($y - $yMin) * $scale + 40);
                                            };
                                            
                                            // Warna per klaster dari config (CSS variables)
                                            $clusterColors = [
                                            'var(--color-primary)',
                                            'var(--color-success)',
                                            'var(--color-danger)',
                                            'var(--color-warning)',
                                            'var(--color-accent)',
                                            ];
                                            
                                            // Kelompokkan titik per klaster untuk elips
                                            $pointsByCluster = [];
                                            foreach ($tabelPeserta as $idx => $peserta) {
                                                $k = $peserta['klaster_dominan'];
                                                if (!isset($pointsByCluster[$k])) {
                                                    $pointsByCluster[$k] = [];
                                                }
                                                $pointsByCluster[$k][] = [
                                                    'idx' => $idx,
                                                    'x' => $points[$idx][0] ?? 0,
                                                    'y' => $points[$idx][1] ?? 0,
                                                ];
                                            }
                                            ksort($pointsByCluster);
                                        @endphp
                                        
                                        <svg width="100%" viewBox="0 0 {{ $svgW }} {{ $svgH }}" style="max-height: 420px; border: 1px solid color-mix(in srgb, var(--color-text) 10%, transparent); background: white; border-radius: var(--radius-card);" role="img" aria-label="Grafik PCA sebaran peserta dengan elips keyakinan per klaster, grid axis, dan tooltip">
                                            <!-- Elips keyakinan per klaster: area transparan dengan garis tepi -->
                                            @foreach ($pointsByCluster as $clusterK => $clusterPeserta)
                                                @php
                                                    $clusterPoints = array_map(fn($p) => [$p['x'], $p['y']], $clusterPeserta);
                                                    $kNum = $clusterK;
                                                    $cColor = $clusterColors[($kNum - 1) % count($clusterColors)] ?? 'var(--color-primary)';
                                                    
                                                    if (count($clusterPoints) >= 3) {
                                                        // Hitung pusat (mean) dan matriks kovarians
                                                        $meanX = array_sum(array_map(fn($p) => $p[0], $clusterPoints)) / count($clusterPoints);
                                                        $meanY = array_sum(array_map(fn($p) => $p[1], $clusterPoints)) / count($clusterPoints);
                                                        
                                                        $cov_xx = array_sum(array_map(fn($p) => ($p[0] - $meanX) ** 2, $clusterPoints)) / count($clusterPoints);
                                                        $cov_yy = array_sum(array_map(fn($p) => ($p[1] - $meanY) ** 2, $clusterPoints)) / count($clusterPoints);
                                                        $cov_xy = array_sum(array_map(fn($p) => ($p[0] - $meanX) * ($p[1] - $meanY), $clusterPoints)) / count($clusterPoints);
                                                        
                                                        // Eigen: untuk ellipse 2D
                                                        $trace = $cov_xx + $cov_yy;
                                                        $det = $cov_xx * $cov_yy - $cov_xy ** 2;
                                                        $lambda1 = $trace / 2 + sqrt(($trace / 2) ** 2 - $det);
                                                        $lambda2 = $trace / 2 - sqrt(($trace / 2) ** 2 - $det);
                                                        
                                                        // Standard deviasi ~ sqrt(eigenvalue) * 1.5
                                                        $a = 1.5 * sqrt($lambda1) * $scale;
                                                        $b = 1.5 * sqrt($lambda2) * $scale;
                                                        $angle = atan2($cov_xy, $cov_xx - $lambda1) * (180 / M_PI);
                                                        
                                                        $cx = $transformX($meanX);
                                                        $cy = $transformY($meanY);
                                                        
                                                        // SVG ellipse
                                                        $ellipseHtml = '<ellipse cx="' . $cx . '" cy="' . $cy . '" rx="' . $a . '" ry="' . $b . '" fill="' . $cColor . '" fill-opacity="0.12" stroke="' . $cColor . '" stroke-width="1" stroke-opacity="0.5" transform="rotate(' . $angle . ' ' . $cx . ' ' . $cy . ')" data-klaster-area="' . $kNum . '" />';
                                                    } else {
                                                        // Untuk <3 titik, buat lingkaran kecil di sekitar centroid
                                                        $meanX = array_sum(array_map(fn($p) => $p[0], $clusterPoints)) / count($clusterPoints);
                                                        $meanY = array_sum(array_map(fn($p) => $p[1], $clusterPoints)) / count($clusterPoints);
                                                        $cx = $transformX($meanX);
                                                        $cy = $transformY($meanY);
                                                        $ellipseHtml = '<circle cx="' . $cx . '" cy="' . $cy . '" r="15" fill="' . $cColor . '" fill-opacity="0.12" stroke="' . $cColor . '" stroke-width="1" stroke-opacity="0.5" data-klaster-area="' . $kNum . '" />';
                                                    }
                                                @endphp
                                                {!! $ellipseHtml !!}
                                            @endforeach
                                            
                                            <!-- Grid lines tipis & axis -->
                                            <line x1="40" y1="40" x2="40" y2="{{ $svgH - 30 }}" stroke="color-mix(in srgb, var(--color-text) 20%, transparent)" stroke-width="1.5" />
                                            <line x1="40" y1="{{ $svgH - 30 }}" x2="{{ $svgW - 20 }}" y2="{{ $svgH - 30 }}" stroke="color-mix(in srgb, var(--color-text) 20%, transparent)" stroke-width="1.5" />
                                            
                                            <!-- Grid garis vertikal dengan tick label X -->
                                            @for ($i = 0; $i <= 5; $i++)
                                                @php
                                                    $xPos = 40 + ($i * ($svgW - 60) / 5);
                                                    $xVal = $xMin + ($i * $xRange / 5);
                                                @endphp
                                                @if ($i > 0)
                                                    <line x1="{{ $xPos }}" y1="40" x2="{{ $xPos }}" y2="{{ $svgH - 30 }}" stroke="color-mix(in srgb, var(--color-text) 8%, transparent)" stroke-width="0.5" stroke-dasharray="2,2" />
                                                @endif
                                                <text x="{{ $xPos }}" y="{{ $svgH - 15 }}" font-size="10" fill="var(--color-text)" text-anchor="middle" opacity="0.5">{{ round($xVal, 1) }}</text>
                                            @endfor
                                            
                                            <!-- Grid garis horisontal dengan tick label Y -->
                                            @for ($i = 0; $i <= 4; $i++)
                                                @php
                                                    $yPos = 40 + ($i * ($svgH - 70) / 4);
                                                    $yVal = $yMax - ($i * $yRange / 4);
                                                @endphp
                                                @if ($i > 0)
                                                    <line x1="40" y1="{{ $yPos }}" x2="{{ $svgW - 20 }}" y2="{{ $yPos }}" stroke="color-mix(in srgb, var(--color-text) 8%, transparent)" stroke-width="0.5" stroke-dasharray="2,2" />
                                                @endif
                                                <text x="30" y="{{ $yPos + 3 }}" font-size="10" fill="var(--color-text)" text-anchor="end" opacity="0.5">{{ round($yVal, 1) }}</text>
                                            @endfor
                                            
                                            <!-- Axis labels -->
                                            <text x="{{ $svgW / 2 }}" y="{{ $svgH - 5 }}" font-size="12" fill="var(--color-text)" text-anchor="middle" opacity="0.7">
                                                Komponen 1 ({{ $variance[0] }}% varians)
                                            </text>
                                            <text x="15" y="{{ $svgH / 2 }}" font-size="12" fill="var(--color-text)" text-anchor="middle" opacity="0.7" transform="rotate(-90 15 {{ $svgH / 2 }})">
                                                Komponen 2 ({{ $variance[1] }}% varians)
                                            </text>
                                            
                                            <!-- Titik peserta dengan tooltip -->
                                            @foreach ($tabelPeserta as $idx => $peserta)
                                                @php
                                                    $x = $transformX($points[$idx][0]);
                                                    $y = $transformY($points[$idx][1]);
                                                    $klasterIdx = $peserta['klaster_dominan'] - 1;
                                                    $color = $clusterColors[$klasterIdx % count($clusterColors)] ?? 'var(--color-primary)';
                                                    $opacity = min(0.8, max(0.35, max($peserta['memberships']) / 100)); // memberships already in 0-100
                                                    
                                                    // Shape berbeda per klaster
                                                    $shapes = ['circle', 'rect', 'triangle', 'diamond', 'star'];
                                                    $shape = $shapes[$klasterIdx % count($shapes)];
                                                    
                                                    // Tooltip
                                                    $dominantMembership = max($peserta['memberships']);
                                                    $tooltipText = "{$peserta['nama']} | Klaster {$peserta['klaster_dominan']}: {$dominantMembership}%";
                                                @endphp
                                                
                                                <g>
                                                    <title>{{ $tooltipText }}</title>
                                                    @if ($shape === 'circle')
                                                        <circle cx="{{ $x }}" cy="{{ $y }}" r="4" fill="{{ $color }}" opacity="{{ $opacity }}" stroke="var(--color-text)" stroke-width="1" />
                                                    @elseif ($shape === 'rect')
                                                        <rect x="{{ $x - 3 }}" y="{{ $y - 3 }}" width="6" height="6" fill="{{ $color }}" opacity="{{ $opacity }}" stroke="var(--color-text)" stroke-width="1" />
                                                    @elseif ($shape === 'triangle')
                                                        <polygon points="{{ $x }},{{ $y - 4 }} {{ $x + 4 }},{{ $y + 3 }} {{ $x - 4 }},{{ $y + 3 }}" fill="{{ $color }}" opacity="{{ $opacity }}" stroke="var(--color-text)" stroke-width="1" />
                                                    @elseif ($shape === 'diamond')
                                                        <polygon points="{{ $x }},{{ $y - 4 }} {{ $x + 4 }},{{ $y }} {{ $x }},{{ $y + 4 }} {{ $x - 4 }},{{ $y }}" fill="{{ $color }}" opacity="{{ $opacity }}" stroke="var(--color-text)" stroke-width="1" />
                                                    @else
                                                        <circle cx="{{ $x }}" cy="{{ $y }}" r="4" fill="{{ $color }}" opacity="{{ $opacity }}" stroke="var(--color-text)" stroke-width="1" />
                                                    @endif
                                                </g>
                                            @endforeach
                                            
                                            <!-- Centroid per klaster dengan label K1, K2, ... -->
                                            @foreach ($klasterResult['tabel_klaster'] as $klasterItem)
                                                @php
                                                    $cData = [
                                                        $klasterItem['centroid']['TC'],
                                                        $klasterItem['centroid']['TE'],
                                                        $klasterItem['centroid']['ITT'],
                                                        $klasterItem['centroid']['YB'],
                                                    ];
                                                    // Proyeksikan centroid dengan transformasi PCA yang sama
                                                    $pc1 = 0;
                                                    $pc2 = 0;
                                                    for ($d = 0; $d < 4; $d++) {
                                                        $pc1 += $cData[$d] * $pcaData['loadings'][0][$d];
                                                        $pc2 += $cData[$d] * $pcaData['loadings'][1][$d];
                                                    }
                                                    $cx = $transformX($pc1);
                                                    $cy = $transformY($pc2);
                                                    $klasterNum = $klasterItem['klaster'];
                                                    $cColor = $clusterColors[($klasterNum - 1) % count($clusterColors)] ?? 'var(--color-primary)';
                                                @endphp
                                                <!-- Centroid lingkaran besar -->
                                                <circle cx="{{ $cx }}" cy="{{ $cy }}" r="7" fill="none" stroke="{{ $cColor }}" stroke-width="2" opacity="0.8" data-centroid-klaster="{{ $klasterNum }}" />
                                                <circle cx="{{ $cx }}" cy="{{ $cy }}" r="5" fill="{{ $cColor }}" opacity="0.2" />
                                                <!-- Label K1, K2, ... -->
                                                <text x="{{ $cx }}" y="{{ $cy + 2 }}" font-size="9" font-weight="bold" fill="{{ $cColor }}" text-anchor="middle" opacity="0.9">K{{ $klasterNum }}</text>
                                            @endforeach
                                        </svg>
                                    </div>
                                    <p style="color: var(--color-text); opacity: 0.6;" class="text-xs mt-2">
                                        <strong>Catatan:</strong> Setiap bentuk mewakili klaster berbeda. Opacity menunjukkan derajat keanggotaan terhadap klaster dominan (35%-100%).
                                    </p>
                                    
                                    <!-- Legenda HTML di bawah SVG -->
                                    <div style="background-color: color-mix(in srgb, var(--color-bg) 50%, var(--color-text) 2%); border: 1px solid color-mix(in srgb, var(--color-text) 10%, transparent); border-radius: var(--radius-card); padding: 1rem; margin-top: 1rem;">
                                        <p style="color: var(--color-text);" class="font-semibold text-sm mb-3">Legenda Klaster:</p>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                            @foreach ($klasterResult['tabel_klaster'] as $klasterItem)
                                                @php
                                                    $klasterNum = $klasterItem['klaster'];
                                                    $profil = $klasterItem['profil'];
                                                    $cColor = $clusterColors[($klasterNum - 1) % count($clusterColors)] ?? 'var(--color-primary)';
                                                    $shapes = ['circle', 'rect', 'triangle', 'diamond', 'star'];
                                                    $shape = $shapes[($klasterNum - 1) % count($shapes)];
                                                    
                                                    // Count peserta di klaster ini
                                                    $pesertaCount = collect($tabelPeserta)->filter(
                                                        fn($p) => $p['klaster_dominan'] === $klasterNum
                                                    )->count();
                                                @endphp
                                                <div class="flex items-center gap-3" style="border: 1px solid color-mix(in srgb, var(--color-text) 8%, transparent); border-radius: calc(var(--radius-card) - 2px); padding: 0.75rem;">
                                                    <!-- Simbol klaster (SVG kecil) -->
                                                    <svg width="32" height="32" viewBox="0 0 32 32" style="flex-shrink: 0;">
                                                        @if ($shape === 'circle')
                                                            <circle cx="16" cy="16" r="6" fill="{{ $cColor }}" opacity="0.7" stroke="var(--color-text)" stroke-width="1" />
                                                        @elseif ($shape === 'rect')
                                                            <rect x="10" y="10" width="12" height="12" fill="{{ $cColor }}" opacity="0.7" stroke="var(--color-text)" stroke-width="1" />
                                                        @elseif ($shape === 'triangle')
                                                            <polygon points="16,6 26,24 6,24" fill="{{ $cColor }}" opacity="0.7" stroke="var(--color-text)" stroke-width="1" />
                                                        @elseif ($shape === 'diamond')
                                                            <polygon points="16,5 26,16 16,27 6,16" fill="{{ $cColor }}" opacity="0.7" stroke="var(--color-text)" stroke-width="1" />
                                                        @else
                                                            <circle cx="16" cy="16" r="6" fill="{{ $cColor }}" opacity="0.7" stroke="var(--color-text)" stroke-width="1" />
                                                        @endif
                                                    </svg>
                                                    <!-- Label klaster dengan peserta count -->
                                                    <div class="flex-1 min-w-0">
                                                        <p style="color: var(--color-text);" class="font-semibold text-sm">Klaster {{ $klasterNum }}: {{ $profil }} <span style="opacity: 0.7;" class="text-xs">({{ $pesertaCount }} peserta)</span></p>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- 4. Indeks Validitas Klaster -->
                            <div class="mb-6">
                                <h3 class="font-semibold mb-3 text-sm" style="color: var(--color-text);">4. Indeks Validitas Klaster</h3>
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

                            <!-- 4. Penjelasan Otomatis per Klaster -->
                            <div class="mb-6">
                                <h3 class="font-semibold mb-3 text-sm" style="color: var(--color-text);">5. Profil Klaster</h3>
                                <div class="space-y-4">
                                    @foreach ($klasterResult['tabel_klaster'] as $klaster)
                                        <div style="border: 1px solid color-mix(in srgb, var(--color-text) 15%, transparent); border-radius: var(--radius-card); padding: 1rem;">
                                            <!-- Penjelasan otomatis -->
                                            <p style="color: var(--color-text);" class="text-sm mb-3">
                                                {{ $clusterExplanations[$klaster['klaster']] ?? '—' }}
                                            </p>
                                            
                                            <!-- Karakteristik dan Rekomendasi -->
                                            <p style="color: var(--color-text);" class="text-sm mb-2">
                                                <strong>Karakteristik:</strong> {{ $klaster['karakteristik'] }}
                                            </p>
                                            <p style="color: var(--color-text);" class="text-sm">
                                                <strong>Rekomendasi:</strong> {{ $klaster['rekomendasi'] }}
                                            </p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- 5. Kotak Penjelasan "Cara Membaca Hasil" -->
                            <div class="mb-6" style="background-color: color-mix(in srgb, var(--color-accent) 8%, transparent); border-left: 4px solid var(--color-accent); padding: 1rem; border-radius: var(--radius-card);">
                                <h3 class="font-semibold mb-3 text-sm" style="color: var(--color-text);">Cara Membaca Hasil Ini</h3>
                                <div class="space-y-2 text-sm" style="color: var(--color-text);">
                                    <p><strong>1. Fuzzy C-Means (FCM):</strong> Algoritma mengelompokkan peserta berdasarkan kemiripan skor TC, TE, ITT, YB. Setiap peserta boleh sebagian masuk beberapa klaster (fuzzy), bukan kategori tegas.</p>
                                    <p><strong>2. Derajat Keanggotaan:</strong> Persentase menunjukkan seberapa kuat peserta "masuk" ke tiap klaster. Contoh: 58% di K1, 42% di K2 = peserta berada di perbatasan dua profil.</p>
                                    <p><strong>3. Indeks Validitas (PC, PE, XB, Silhouette):</strong> Mengukur kualitas pengelompokan. Silhouette tinggi = klaster terpisah jelas. XB rendah = klaster kompak dan terpisah.</p>
                                    <p><strong>4. Profil A–D:</strong> Ditentukan dari pola centroid terhadap profil konseptual. "Belum Cocok" = pola data tidak sesuai 4 profil utama.</p>
                                    <p><strong>5. Skor dan Centroid:</strong> Menggunakan rerata skala 1–4 (bukan estimasi PCM). Centroid adalah nilai rata-rata skor semua peserta dalam klaster.</p>
                                    <p><strong>6. PCA (Grafik):</strong> Proyeksi 4D → 2D untuk visualisasi. Setiap bentuk/warna = klaster berbeda. Ukuran opacity = kuat lemahnya keanggotaan dominan.</p>
                                </div>
                            </div>

                            <!-- Tabel derajat keanggotaan peserta -->
                            <div>
                                <h3 class="font-semibold mb-3 text-sm" style="color: var(--color-text);">Derajat Keanggotaan Peserta (%)</h3>

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
                                                @foreach (range(1, $klasterResult['c_optimal']) as $k)
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
                        @endif
                    </x-card>
                @endif
            @endif
            <div class="mt-6">
                <a href="{{ route('pelatihans.index') }}" style="color: var(--color-primary);" class="hover:opacity-80">← Kembali ke Pelatihan</a>
            </div>
        </div>
    </div>
</x-app-layout>
