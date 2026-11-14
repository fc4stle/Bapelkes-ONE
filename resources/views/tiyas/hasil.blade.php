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
<!-- Section 3: Deskriptif Statistik & Grafik FCM -->
<div class="mb-6">
    <!-- 3.1 Tabel Deskriptif Statistik per Konstruk -->
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-slate-900 mb-4">3. Deskriptif Statistik</h3>
        
        <!-- Tabel Deskriptif -->
        <div class="overflow-x-auto mb-6">
            <table class="w-full text-sm border-collapse">
                <thead class="bg-slate-100">
                    <tr>
                        <th class="border border-slate-300 px-4 py-2 text-left">Konstruk</th>
                        <th class="border border-slate-300 px-4 py-2 text-right">Rerata</th>
                        <th class="border border-slate-300 px-4 py-2 text-right">SD</th>
                        <th class="border border-slate-300 px-4 py-2 text-right">Min</th>
                        <th class="border border-slate-300 px-4 py-2 text-right">Max</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (['TC' => 'Teamwork & Collaboration', 'TE' => 'Technical Excellence', 'ITT' => 'Innovation & IT', 'YB' => 'Yayasan Belief'] as $kode => $label)
                        @if (isset($deskriptif[$kode]))
                            <tr class="hover:bg-slate-50">
                                <td class="border border-slate-300 px-4 py-2 font-medium">{{ $label }} ({{ $kode }})</td>
                                <td class="border border-slate-300 px-4 py-2 text-right">{{ $deskriptif[$kode]['mean'] }}</td>
                                <td class="border border-slate-300 px-4 py-2 text-right">{{ $deskriptif[$kode]['stdev'] }}</td>
                                <td class="border border-slate-300 px-4 py-2 text-right">{{ $deskriptif[$kode]['min'] }}</td>
                                <td class="border border-slate-300 px-4 py-2 text-right">{{ $deskriptif[$kode]['max'] }}</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Chart 1: Rerata & Deviasi Standar per Konstruk (Bars dengan band SD) -->
        <div class="mb-6">
            <h4 class="text-md font-medium text-slate-800 mb-3">Rata-rata Skor dan Kisaran Variabilitas per Konstruk</h4>
            <svg viewBox="0 0 800 400" class="w-full border border-slate-200 rounded bg-white">
                <!-- Title -->
                <text x="400" y="25" text-anchor="middle" class="text-base font-semibold">Rata-rata ± SD per Konstruk</text>
                
                <!-- Y-axis labels (0-5 scale) -->
                @for ($i = 0; $i <= 5; $i++)
                    <text x="50" y="{{ 380 - ($i * 60) }}" text-anchor="end" dy="0.3em" class="text-xs fill-slate-500">{{ $i }}</text>
                    <line x1="55" y1="{{ 380 - ($i * 60) }}" x2="750" y2="{{ 380 - ($i * 60) }}" stroke="#e2e8f0" stroke-width="1" />
                @endfor
                
                <!-- Y-axis -->
                <line x1="55" y1="50" x2="55" y2="380" stroke="#1e293b" stroke-width="2" />
                <!-- X-axis -->
                <line x1="55" y1="380" x2="750" y2="380" stroke="#1e293b" stroke-width="2" />
                
                <!-- Bars untuk setiap konstruk -->
                @php
                    $konstruks = ['TC', 'TE', 'ITT', 'YB'];
                    $colors = ['#ef4444', '#3b82f6', '#10b981', '#f59e0b']; // red, blue, green, amber
                    $barWidth = 60;
                    $spacing = 140;
                    $startX = 120;
                @endphp
                
                @foreach ($konstruks as $idx => $kode)
                    @if (isset($deskriptif[$kode]))
                        @php
                            $mean = floatval($deskriptif[$kode]['mean']);
                            $stdev = floatval($deskriptif[$kode]['stdev']);
                            $barX = $startX + ($idx * $spacing);
                            $barHeight = ($mean / 5) * 300;
                            $bandHeight = ($stdev / 5) * 300;
                            $topY = 380 - $barHeight;
                            $bandTopY = 380 - ($barHeight + $bandHeight);
                        @endphp
                        
                        <!-- SD band (lighter) -->
                        <rect x="{{ $barX - $barWidth/2 + 5 }}" y="{{ $bandTopY }}" width="{{ $barWidth - 10 }}" height="{{ $bandHeight * 2 }}" 
                            fill="{{ $colors[$idx] }}" opacity="0.2" />
                        
                        <!-- Main bar -->
                        <rect x="{{ $barX - $barWidth/2 }}" y="{{ $topY }}" width="{{ $barWidth }}" height="{{ $barHeight }}" 
                            fill="{{ $colors[$idx] }}" />
                        
                        <!-- Value label on bar -->
                        <text x="{{ $barX }}" y="{{ $topY - 5 }}" text-anchor="middle" class="text-sm font-semibold fill-slate-900">
                            {{ $deskriptif[$kode]['mean'] }}
                        </text>
                        
                        <!-- X-axis label -->
                        <text x="{{ $barX }}" y="400" text-anchor="middle" class="text-sm font-medium fill-slate-700">
                            {{ $kode }}
                        </text>
                    @endif
                @endforeach
            </svg>
            <p class="text-xs text-slate-600 mt-2">Warna terang menunjukkan kisaran ±1 SD dari rata-rata</p>
        </div>

        <!-- Chart 2: Distribusi Kategori (Stacked Bar %) -->
        <div class="mb-6">
            <h4 class="text-md font-medium text-slate-800 mb-3">Distribusi Kategori per Konstruk</h4>
            <svg viewBox="0 0 800 400" class="w-full border border-slate-200 rounded bg-white">
                <!-- Title -->
                <text x="400" y="25" text-anchor="middle" class="text-base font-semibold">Proporsi Kategori (%)</text>
                
                <!-- Konstruk labels di kiri -->
                @php
                    $konstrukLabels = ['TC', 'TE', 'ITT', 'YB'];
                    $barHeight = 50;
                    $barSpacing = 80;
                    $startY = 80;
                @endphp
                
                @foreach ($konstrukLabels as $idx => $kode)
                    @php
                        $barY = $startY + ($idx * $barSpacing);
                    @endphp
                    
                    <text x="45" y="{{ $barY + 15 }}" text-anchor="end" class="text-sm font-medium fill-slate-700">{{ $kode }}</text>
                    
                    @if (isset($deskriptif[$kode]['kategori']))
                        @php
                            $categories = $deskriptif[$kode]['kategori'];
                            $categoryColors = ['Rendah' => '#ef4444', 'Sedang' => '#f59e0b', 'Tinggi' => '#10b981'];
                            $xPos = 60;
                        @endphp
                        
                        @foreach ($categories as $catLabel => $catData)
                            @php
                                $percent = $catData['percent'];
                                $catWidth = ($percent / 100) * 650;
                                $catColor = $categoryColors[$catLabel] ?? '#94a3b8';
                            @endphp
                            
                            <!-- Segment bar -->
                            <rect x="{{ $xPos }}" y="{{ $barY }}" width="{{ $catWidth }}" height="{{ $barHeight }}" 
                                fill="{{ $catColor }}" stroke="#fff" stroke-width="1" />
                            
                            <!-- Percent label (if > 10%) -->
                            @if ($percent >= 10)
                                <text x="{{ $xPos + $catWidth/2 }}" y="{{ $barY + $barHeight/2 + 5 }}" 
                                    text-anchor="middle" class="text-xs font-bold fill-white">
                                    {{ $percent }}%
                                </text>
                            @endif
                            
                            @php
                                $xPos += $catWidth;
                            @endphp
                        @endforeach
                    @endif
                @endforeach
                
                <!-- Legend -->
                @php
                    $legX = 60;
                    $legY = 370;
                @endphp
                
                @foreach (['Rendah', 'Sedang', 'Tinggi'] as $idx => $catLabel)
                    @php
                        $categoryColors = ['Rendah' => '#ef4444', 'Sedang' => '#f59e0b', 'Tinggi' => '#10b981'];
                        $catColor = $categoryColors[$catLabel];
                        $legXOffset = $legX + ($idx * 150);
                    @endphp
                    
                    <rect x="{{ $legXOffset }}" y="{{ $legY }}" width="12" height="12" fill="{{ $catColor }}" />
                    <text x="{{ $legXOffset + 18 }}" y="{{ $legY + 10 }}" class="text-xs fill-slate-700">{{ $catLabel }}</text>
                @endforeach
            </svg>
        </div>

        <!-- Category Count Table -->
        <div class="overflow-x-auto mb-6">
            <table class="w-full text-sm border-collapse">
                <thead class="bg-slate-100">
                    <tr>
                        <th class="border border-slate-300 px-4 py-2 text-left">Konstruk</th>
                        <th class="border border-slate-300 px-4 py-2 text-center">Rendah</th>
                        <th class="border border-slate-300 px-4 py-2 text-center">Sedang</th>
                        <th class="border border-slate-300 px-4 py-2 text-center">Tinggi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($konstruks as $kode)
                        @if (isset($deskriptif[$kode]['kategori']))
                            <tr class="hover:bg-slate-50">
                                <td class="border border-slate-300 px-4 py-2 font-medium">{{ $kode }}</td>
                                <td class="border border-slate-300 px-4 py-2 text-center">
                                    {{ $deskriptif[$kode]['kategori']['Rendah']['count'] ?? 0 }} ({{ $deskriptif[$kode]['kategori']['Rendah']['percent'] ?? 0 }}%)
                                </td>
                                <td class="border border-slate-300 px-4 py-2 text-center">
                                    {{ $deskriptif[$kode]['kategori']['Sedang']['count'] ?? 0 }} ({{ $deskriptif[$kode]['kategori']['Sedang']['percent'] ?? 0 }}%)
                                </td>
                                <td class="border border-slate-300 px-4 py-2 text-center">
                                    {{ $deskriptif[$kode]['kategori']['Tinggi']['count'] ?? 0 }} ({{ $deskriptif[$kode]['kategori']['Tinggi']['percent'] ?? 0 }}%)
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- 3.2 Grafik Centroid Klaster FCM -->
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-slate-900 mb-4">3.2 Centroid Klaster (Fuzzy C-Means)</h3>
        
        <!-- Chart 3: Centroid per Klaster (Grouped Bars) -->
        @if (!empty($klasterResult['tabel_klaster']))
            <div class="mb-6">
                <h4 class="text-md font-medium text-slate-800 mb-3">Nilai Centroid per Klaster dan Konstruk</h4>
                <svg viewBox="0 0 1000 500" class="w-full border border-slate-200 rounded bg-white">
                    <!-- Title -->
                    <text x="500" y="25" text-anchor="middle" class="text-base font-semibold">Centroid Setiap Klaster</text>
                    
                    <!-- Y-axis labels -->
                    @for ($i = 0; $i <= 5; $i++)
                        <text x="60" y="{{ 480 - ($i * 80) }}" text-anchor="end" dy="0.3em" class="text-xs fill-slate-500">{{ $i }}</text>
                        <line x1="65" y1="{{ 480 - ($i * 80) }}" x2="950" y2="{{ 480 - ($i * 80) }}" stroke="#e2e8f0" stroke-width="1" />
                    @endfor
                    
                    <!-- Y-axis -->
                    <line x1="65" y1="50" x2="65" y2="480" stroke="#1e293b" stroke-width="2" />
                    <!-- X-axis -->
                    <line x1="65" y1="480" x2="950" y2="480" stroke="#1e293b" stroke-width="2" />
                    
                    <!-- Grouped bars per klaster -->
                    @php
                        $klasters = $klasterResult['tabel_klaster'];
                        $clusterColors = ['#ef4444', '#3b82f6', '#10b981', '#f59e0b']; // red, blue, green, amber
                        $konstrukColors = ['TC' => 'rgba(239,68,68,1)', 'TE' => 'rgba(59,130,246,1)', 'ITT' => 'rgba(16,185,129,1)', 'YB' => 'rgba(245,158,11,1)'];
                        $barWidth = 14;
                        $groupSpacing = 100;
                        $startX = 100;
                    @endphp
                    
                    @foreach ($klasters as $klIdx => $klaster)
                        @php
                            $groupX = $startX + ($klIdx * $groupSpacing);
                        @endphp
                        
                        @foreach (['TC', 'TE', 'ITT', 'YB'] as $kIdx => $kode)
                            @php
                                $centroidValue = floatval($klaster['centroid'][$kode] ?? 2.0);
                                $barX = $groupX + ($kIdx - 1.5) * $barWidth;
                                $barHeight = ($centroidValue / 5) * 380;
                                $topY = 480 - $barHeight;
                            @endphp
                            
                            <rect x="{{ $barX }}" y="{{ $topY }}" width="{{ $barWidth - 2 }}" height="{{ $barHeight }}" 
                                fill="{{ $konstrukColors[$kode] }}" />
                        @endforeach
                        
                        <!-- Cluster label -->
                        <text x="{{ $groupX }}" y="500" text-anchor="middle" class="text-sm font-bold fill-slate-900">
                            C{{ $klIdx + 1 }}
                        </text>
                    @endforeach
                    
                    <!-- Legend -->
                    @php
                        $legX = 100;
                        $legY = 520;
                    @endphp
                    
                    @foreach (['TC', 'TE', 'ITT', 'YB'] as $idx => $kode)
                        @php
                            $legXOffset = $legX + ($idx * 180);
                            $legColor = $konstrukColors[$kode];
                        @endphp
                        
                        <rect x="{{ $legXOffset }}" y="{{ $legY }}" width="12" height="12" fill="{{ $legColor }}" />
                        <text x="{{ $legXOffset + 18 }}" y="{{ $legY + 10 }}" class="text-xs fill-slate-700">{{ $kode }}</text>
                    @endforeach
                </svg>
            </div>

            <!-- Profile Cards dengan Rekomendasi -->
            <div class="mb-6">
                <h4 class="text-md font-medium text-slate-800 mb-3">Profil Klaster dan Rekomendasi Tindak Lanjut</h4>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach ($klasters as $klIdx => $klaster)
                        <div class="border border-slate-300 rounded-lg p-4 bg-slate-50">
                            <h5 class="text-base font-bold text-slate-900 mb-2">
                                Klaster {{ $klIdx + 1 }}: {{ $klaster['kode_profil'] }}
                            </h5>
                            <p class="text-sm text-slate-700 mb-2"><strong>Profil:</strong> {{ $klaster['profil'] }}</p>
                            <p class="text-sm text-slate-700 mb-2"><strong>Jumlah Peserta:</strong> {{ $klaster['jumlah_peserta'] }}</p>
                            <p class="text-sm text-slate-700 mb-2"><strong>Karakteristik:</strong> {{ $klaster['karakteristik'] }}</p>
                            <div class="bg-blue-50 border border-blue-200 rounded p-2 mb-3">
                                <p class="text-xs font-semibold text-blue-900 mb-1">Rekomendasi Tindak Lanjut:</p>
                                <p class="text-xs text-blue-800">{{ $klaster['rekomendasi'] }}</p>
                            </div>
                            <div class="text-xs text-slate-600">
                                <strong>Centroid:</strong> TC={{ $klaster['centroid']['TC'] }}, TE={{ $klaster['centroid']['TE'] }}, 
                                ITT={{ $klaster['centroid']['ITT'] }}, YB={{ $klaster['centroid']['YB'] }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="p-4 bg-slate-100 rounded text-slate-700 text-sm">
                Data klaster tidak tersedia (kurang dari 8 responses).
            </div>
        @endif
    </div>
</div>


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
