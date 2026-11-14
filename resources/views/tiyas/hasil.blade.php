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
                                <div class="inline-block px-2 py-1 rounded text-xs font-semibold w-fit" style="background-color: {{ $warna }}; color: white;">
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

                <!-- SECTION 1: Statistik Deskriptif + Grafik -->
                @if (empty($deskriptif))
                    <x-card class="mb-6">
                        <h2 class="text-xl font-semibold mb-4" style="color: var(--color-text);">Statistik Deskriptif</h2>
                        <div style="background-color: color-mix(in srgb, var(--color-accent) 10%, transparent); border-left: 4px solid var(--color-accent); padding: 1rem; border-radius: var(--radius-card);">
                            <p style="color: var(--color-text);" class="text-sm">
                                Belum ada data TIYAS yang terisi untuk pelatihan ini.
                            </p>
                        </div>
                    </x-card>
                @else
                    <x-card class="mb-6">
                        <h2 class="text-xl font-semibold mb-4" style="color: var(--color-text);">1. Statistik Deskriptif</h2>
                        
                        <!-- Tabel Deskriptif Statistik -->
                        <div class="mb-6">
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm" style="border-collapse: collapse;">
                                    <thead>
                                        <tr style="border-bottom: 1px solid color-mix(in srgb, var(--color-text) 15%, transparent);">
                                            <th class="text-left px-4 py-2" style="color: var(--color-text);">Konstruk</th>
                                            <th class="text-center px-4 py-2" style="color: var(--color-text);">Rerata</th>
                                            <th class="text-center px-4 py-2" style="color: var(--color-text);">SD</th>
                                            <th class="text-center px-4 py-2" style="color: var(--color-text);">Min</th>
                                            <th class="text-center px-4 py-2" style="color: var(--color-text);">Maks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach (['TC' => 'Teamwork & Collaboration', 'TE' => 'Technical Excellence', 'ITT' => 'Innovation & IT', 'YB' => 'Yayasan Belief'] as $kode => $label)
                                            @if (isset($deskriptif[$kode]))
                                                <tr style="border-bottom: 1px solid color-mix(in srgb, var(--color-text) 10%, transparent);">
                                                    <td class="px-4 py-2 font-medium" style="color: var(--color-text);">{{ $label }} ({{ $kode }})</td>
                                                    <td class="text-center px-4 py-2" style="color: var(--color-primary);">{{ $deskriptif[$kode]['mean'] }}</td>
                                                    <td class="text-center px-4 py-2" style="color: var(--color-primary);">{{ $deskriptif[$kode]['stdev'] }}</td>
                                                    <td class="text-center px-4 py-2" style="color: var(--color-primary);">{{ $deskriptif[$kode]['min'] }}</td>
                                                    <td class="text-center px-4 py-2" style="color: var(--color-primary);">{{ $deskriptif[$kode]['max'] }}</td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Chart 1: Rerata & Deviasi Standar per Konstruk (Bars dengan band SD) -->
                        <div class="mb-6">
                            <h3 class="font-semibold mb-3" style="color: var(--color-text);">Rata-rata Skor dan Kisaran Variabilitas per Konstruk</h3>
                            <svg viewBox="0 0 800 400" class="w-full rounded" style="border: 1px solid color-mix(in srgb, var(--color-text) 15%, transparent); background-color: white;" data-chart="deskriptif-mean-sd">
                                <!-- Title -->
                                <text x="400" y="25" text-anchor="middle" style="font-size: 16px; font-weight: 600; fill: var(--color-text);">Rata-rata ± SD per Konstruk</text>
                                
                                <!-- Y-axis labels (0-5 scale) -->
                                @for ($i = 0; $i <= 5; $i++)
                                    <text x="50" y="{{380 - ($i * 60)}}" text-anchor="end" dy="0.3em" style="font-size: 12px; fill: var(--color-text); opacity: 0.6;">{{ $i }}</text>
                                    <line x1="55" y1="{{380 - ($i * 60)}}" x2="750" y2="{{380 - ($i * 60)}}" stroke="color-mix(in srgb, var(--color-text) 15%, transparent)" stroke-width="1"/>
                                @endfor
                                
                                <!-- Y-axis -->
                                <line x1="55" y1="50" x2="55" y2="380" stroke="var(--color-text)" stroke-width="2"/>
                                <!-- X-axis -->
                                <line x1="55" y1="380" x2="750" y2="380" stroke="var(--color-text)" stroke-width="2"/>
                                
                                <!-- Bars untuk setiap konstruk -->
                                @php
                                    $konstruks = ['TC', 'TE', 'ITT', 'YB'];
                                    $colors = [
                                        'TC' => 'var(--color-danger)',
                                        'TE' => 'var(--color-primary)',
                                        'ITT' => 'var(--color-success)',
                                        'YB' => 'var(--color-accent)'
                                    ];
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
                                        <rect x="{{$barX - $barWidth/2 + 5}}" y="{{$bandTopY}}" width="{{$barWidth - 10}}" height="{{$bandHeight * 2}}" 
                                            fill="{{ $colors[$kode] }}" opacity="0.2"/>
                                        
                                        <!-- Main bar -->
                                        <rect x="{{$barX - $barWidth/2}}" y="{{$topY}}" width="{{$barWidth}}" height="{{$barHeight}}" 
                                            fill="{{ $colors[$kode] }}"/>
                                        
                                        <!-- Value label on bar -->
                                        <text x="{{$barX}}" y="{{$topY - 5}}" text-anchor="middle" style="font-size: 14px; font-weight: 600; fill: var(--color-text);">
                                            {{ $deskriptif[$kode]['mean'] }}
                                        </text>
                                        
                                        <!-- X-axis label -->
                                        <text x="{{$barX}}" y="400" text-anchor="middle" style="font-size: 14px; font-weight: 500; fill: var(--color-text);">
                                            {{ $kode }}
                                        </text>
                                    @endif
                                @endforeach
                            </svg>
                            <p class="text-xs mt-2" style="color: var(--color-text); opacity: 0.6;">Warna terang menunjukkan kisaran ±1 SD dari rata-rata</p>
                        </div>

                        <!-- Chart 2: Distribusi Kategori (Stacked Bar %) -->
                        <div class="mb-6">
                            <h3 class="font-semibold mb-3" style="color: var(--color-text);">Distribusi Kategori per Konstruk</h3>
                            <svg viewBox="0 0 800 400" class="w-full rounded" style="border: 1px solid color-mix(in srgb, var(--color-text) 15%, transparent); background-color: white;" data-chart="deskriptif-kategori-stacked">
                                <!-- Title -->
                                <text x="400" y="25" text-anchor="middle" style="font-size: 16px; font-weight: 600; fill: var(--color-text);">Proporsi Kategori (%)</text>
                                
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
                                    
                                    <text x="45" y="{{$barY + 15}}" text-anchor="end" style="font-size: 14px; font-weight: 500; fill: var(--color-text);">{{ $kode }}</text>
                                    
                                    @if (isset($deskriptif[$kode]['kategori']))
                                        @php
                                            $categories = $deskriptif[$kode]['kategori'];
                                            $categoryColors = [
                                                'Rendah' => 'var(--color-danger)',
                                                'Sedang' => 'var(--color-accent)',
                                                'Tinggi' => 'var(--color-success)'
                                            ];
                                            $xPos = 60;
                                        @endphp
                                        
                                        @foreach ($categories as $catLabel => $catData)
                                            @php
                                                $percent = $catData['percent'];
                                                $catWidth = ($percent / 100) * 650;
                                                $catColor = $categoryColors[$catLabel] ?? 'var(--color-text)';
                                            @endphp
                                            
                                            <!-- Segment bar -->
                                            <rect x="{{$xPos}}" y="{{$barY}}" width="{{$catWidth}}" height="{{$barHeight}}" 
                                                fill="{{ $catColor }}" stroke="white" stroke-width="1"/>
                                            
                                            <!-- Percent label (if > 10%) -->
                                            @if ($percent >= 10)
                                                <text x="{{$xPos + $catWidth/2}}" y="{{$barY + $barHeight/2 + 5}}" 
                                                    text-anchor="middle" style="font-size: 12px; font-weight: 700; fill: white;">
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
                                        $categoryColors = [
                                            'Rendah' => 'var(--color-danger)',
                                            'Sedang' => 'var(--color-accent)',
                                            'Tinggi' => 'var(--color-success)'
                                        ];
                                        $catColor = $categoryColors[$catLabel];
                                        $legXOffset = $legX + ($idx * 150);
                                    @endphp
                                    
                                    <rect x="{{$legXOffset}}" y="{{$legY}}" width="12" height="12" fill="{{ $catColor }}"/>
                                    <text x="{{$legXOffset + 18}}" y="{{$legY + 10}}" style="font-size: 12px; fill: var(--color-text);">{{ $catLabel }}</text>
                                @endforeach
                            </svg>
                        </div>

                        <!-- Tabel Kategori dengan Count -->
                        <div class="overflow-x-auto mb-6">
                            <table class="w-full text-sm" style="border-collapse: collapse;">
                                <thead>
                                    <tr style="border-bottom: 1px solid color-mix(in srgb, var(--color-text) 15%, transparent);">
                                        <th class="px-4 py-2 text-left" style="color: var(--color-text);">Konstruk</th>
                                        <th class="px-4 py-2 text-center" style="color: var(--color-text);">Rendah</th>
                                        <th class="px-4 py-2 text-center" style="color: var(--color-text);">Sedang</th>
                                        <th class="px-4 py-2 text-center" style="color: var(--color-text);">Tinggi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach (['TC', 'TE', 'ITT', 'YB'] as $kode)
                                        @if (isset($deskriptif[$kode]['kategori']))
                                            <tr style="border-bottom: 1px solid color-mix(in srgb, var(--color-text) 10%, transparent);">
                                                <td class="px-4 py-2 font-medium" style="color: var(--color-text);">{{ $kode }}</td>
                                                <td class="px-4 py-2 text-center" style="color: var(--color-text);">
                                                    {{ $deskriptif[$kode]['kategori']['Rendah']['count'] ?? 0 }} ({{ $deskriptif[$kode]['kategori']['Rendah']['percent'] ?? 0 }}%)
                                                </td>
                                                <td class="px-4 py-2 text-center" style="color: var(--color-text);">
                                                    {{ $deskriptif[$kode]['kategori']['Sedang']['count'] ?? 0 }} ({{ $deskriptif[$kode]['kategori']['Sedang']['percent'] ?? 0 }}%)
                                                </td>
                                                <td class="px-4 py-2 text-center" style="color: var(--color-text);">
                                                    {{ $deskriptif[$kode]['kategori']['Tinggi']['count'] ?? 0 }} ({{ $deskriptif[$kode]['kategori']['Tinggi']['percent'] ?? 0 }}%)
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </x-card>

                    <!-- SECTION 2: Indeks Validitas Klaster -->
                    @if ($klasterResult['status'] === 'kurang_data')
                        <x-card class="mb-6">
                            <h2 class="text-xl font-semibold mb-4" style="color: var(--color-text);">2. Indeks Validitas Klaster</h2>
                            <div style="background-color: color-mix(in srgb, var(--color-accent) 10%, transparent); border-left: 4px solid var(--color-accent); padding: 1rem; border-radius: var(--radius-card);">
                                <p style="color: var(--color-text);" class="text-sm">
                                    <strong>Profil klaster</strong> butuh minimal 8 peserta yang sudah mengisi. Saat ini: <strong>{{ $klasterResult['jumlah_sekarang'] }}</strong> peserta.
                                </p>
                            </div>
                        </x-card>
                    @else
                        <x-card class="mb-6">
                            <h2 class="text-xl font-semibold mb-4" style="color: var(--color-text);">2. Indeks Validitas Klaster</h2>
                            
                            <!-- Summary: Jumlah klaster terpilih -->
                            <div style="background-color: color-mix(in srgb, var(--color-success) 10%, transparent); border-left: 4px solid var(--color-success); padding: 1rem; border-radius: var(--radius-card); margin-bottom: 1rem;">
                                <p style="color: var(--color-text);" class="text-sm">
                                    <strong>Jumlah klaster terpilih:</strong> <strong style="color: var(--color-success);">{{ $klasterResult['c_optimal'] }}</strong> | 
                                    <strong>PC:</strong> {{ number_format($klasterResult['fuzzy_partition_coefficient'], 4) }} | 
                                    <strong>PE:</strong> {{ number_format($klasterResult['normalized_classification_entropy'], 4) }}
                                </p>
                            </div>

                            <!-- Tabel Validitas Index -->
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
                        </x-card>

                        <!-- SECTION 3: Gambar Klaster (Centroid & Derajat Keanggotaan) -->
                        <x-card class="mb-6">
                            <h2 class="text-xl font-semibold mb-4" style="color: var(--color-text);">3. Gambar Klaster</h2>
                            
                            <!-- 3a. Centroid Klaster (Grouped Bars) -->
                            <div class="mb-6">
                                <h3 class="font-semibold mb-3" style="color: var(--color-text);">a) Centroid per Klaster dan Konstruk</h3>
                                <svg viewBox="0 0 1000 500" class="w-full rounded" style="border: 1px solid color-mix(in srgb, var(--color-text) 15%, transparent); background-color: white;" data-chart="centroid-grouped-bars">
                                    <!-- Title -->
                                    <text x="500" y="25" text-anchor="middle" style="font-size: 16px; font-weight: 600; fill: var(--color-text);">Centroid Setiap Klaster</text>
                                    
                                    <!-- Y-axis labels -->
                                    @for ($i = 0; $i <= 5; $i++)
                                        <text x="60" y="{{480 - ($i * 80)}}" text-anchor="end" dy="0.3em" style="font-size: 12px; fill: var(--color-text); opacity: 0.6;">{{ $i }}</text>
                                        <line x1="65" y1="{{480 - ($i * 80)}}" x2="950" y2="{{480 - ($i * 80)}}" stroke="color-mix(in srgb, var(--color-text) 15%, transparent)" stroke-width="1"/>
                                    @endfor
                                    
                                    <!-- Y-axis -->
                                    <line x1="65" y1="50" x2="65" y2="480" stroke="var(--color-text)" stroke-width="2"/>
                                    <!-- X-axis -->
                                    <line x1="65" y1="480" x2="950" y2="480" stroke="var(--color-text)" stroke-width="2"/>
                                    
                                    <!-- Grouped bars per klaster -->
                                    @php
                                        $klasters = $klasterResult['tabel_klaster'];
                                        $konstrukColors = [
                                            'TC' => 'var(--color-danger)',
                                            'TE' => 'var(--color-primary)',
                                            'ITT' => 'var(--color-success)',
                                            'YB' => 'var(--color-accent)'
                                        ];
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
                                            
                                            <rect x="{{$barX}}" y="{{$topY}}" width="{{$barWidth - 2}}" height="{{$barHeight}}" 
                                                fill="{{ $konstrukColors[$kode] }}"/>
                                        @endforeach
                                        
                                        <!-- Cluster label -->
                                        <text x="{{$groupX}}" y="500" text-anchor="middle" style="font-size: 14px; font-weight: 700; fill: var(--color-text);">
                                            K{{ $klIdx + 1 }}
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
                                        
                                        <rect x="{{$legXOffset}}" y="{{$legY}}" width="12" height="12" fill="{{ $legColor }}"/>
                                        <text x="{{$legXOffset + 18}}" y="{{$legY + 10}}" style="font-size: 12px; fill: var(--color-text);">{{ $kode }}</text>
                                    @endforeach
                                </svg>
                            </div>

                            <!-- 3b. Derajat Keanggotaan per Peserta (Horizontal Stacked Bar) -->
                            <div class="mb-6">
                                <h3 class="font-semibold mb-3" style="color: var(--color-text);">b) Derajat Keanggotaan Peserta per Klaster (Horizontal Stacked Bar)</h3>
                                <div class="overflow-y-auto" style="max-height: 500px;">
                                    <svg viewBox="0 0 1000 {{ count($klasterResult['tabel_peserta']) * 40 + 60 }}" class="w-full rounded" style="border: 1px solid color-mix(in srgb, var(--color-text) 15%, transparent); background-color: white;" data-chart="membership-horizontal-stacked">
                                        <!-- Title -->
                                        <text x="500" y="25" text-anchor="middle" style="font-size: 16px; font-weight: 600; fill: var(--color-text);">Derajat Keanggotaan (%)</text>
                                        
                                        <!-- Peserta rows -->
                                        @php
                                            $klasterColors = [
                                                1 => 'var(--color-danger)',
                                                2 => 'var(--color-primary)',
                                                3 => 'var(--color-success)',
                                                4 => 'var(--color-accent)'
                                            ];
                                        @endphp
                                        
                                        @foreach ($klasterResult['tabel_peserta'] as $pIdx => $peserta)
                                            @php
                                                $rowY = 60 + ($pIdx * 40);
                                            @endphp
                                            
                                            <!-- Nama peserta (kiri) -->
                                            <text x="10" y="{{$rowY + 15}}" style="font-size: 11px; fill: var(--color-text);">{{ substr($peserta['nama'], 0, 20) }}{{ strlen($peserta['nama']) > 20 ? '...' : '' }}</text>
                                            
                                            <!-- Stacked membership bar -->
                                            @php
                                                $xPos = 200;
                                                $barHeight = 30;
                                                $barWidth = 700;
                                            @endphp
                                            
                                            @foreach ($peserta['memberships'] as $kIdx => $membership)
                                                @php
                                                    $segWidth = ($membership / 100) * $barWidth;
                                                    $klasterNum = $kIdx + 1;
                                                    $klasterColor = $klasterColors[$klasterNum] ?? 'var(--color-text)';
                                                @endphp
                                                
                                                <rect x="{{$xPos}}" y="{{$rowY - $barHeight/2}}" width="{{$segWidth}}" height="{{$barHeight}}" 
                                                    fill="{{ $klasterColor }}" stroke="white" stroke-width="1"/>
                                                
                                                @if ($membership >= 15)
                                                    <text x="{{$xPos + $segWidth/2}}" y="{{$rowY + 5}}" text-anchor="middle" 
                                                        style="font-size: 10px; font-weight: 600; fill: white;">
                                                        {{ $membership }}%
                                                    </text>
                                                @endif
                                                
                                                @php
                                                    $xPos += $segWidth;
                                                @endphp
                                            @endforeach
                                        @endforeach
                                        
                                        <!-- Legend -->
                                        @php
                                            $legY = 60 + (count($klasterResult['tabel_peserta']) * 40) + 20;
                                        @endphp
                                        
                                        @foreach (range(1, $klasterResult['c_optimal']) as $k)
                                            @php
                                                $legColor = $klasterColors[$k] ?? 'var(--color-text)';
                                                $legX = 200 + (($k - 1) * 150);
                                            @endphp
                                            
                                            <rect x="{{$legX}}" y="{{$legY}}" width="12" height="12" fill="{{ $legColor }}"/>
                                            <text x="{{$legX + 18}}" y="{{$legY + 10}}" style="font-size: 12px; fill: var(--color-text);">Klaster {{ $k }}</text>
                                        @endforeach
                                    </svg>
                                </div>
                            </div>
                        </x-card>

                        <!-- SECTION 4: Profil Klaster dengan Karakteristik & Rekomendasi -->
                        <x-card class="mb-6">
                            <h2 class="text-xl font-semibold mb-4" style="color: var(--color-text);">4. Profil Klaster</h2>
                            
                            <!-- Profile Cards dengan Rekomendasi -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach ($klasters as $klIdx => $klaster)
                                    <div style="border: 1px solid color-mix(in srgb, var(--color-text) 15%, transparent); border-radius: var(--radius-card); padding: 1rem;">
                                        <h3 class="font-bold mb-2" style="color: var(--color-text);">
                                            Klaster {{ $klIdx + 1 }}: {{ $klaster['kode_profil'] ?? '—' }}
                                        </h3>
                                        <p class="text-sm mb-2" style="color: var(--color-text);"><strong>Profil:</strong> {{ $klaster['profil'] }}</p>
                                        <p class="text-sm mb-2" style="color: var(--color-text);"><strong>Jumlah Peserta:</strong> {{ $klaster['jumlah_peserta'] }}</p>
                                        <p class="text-sm mb-2" style="color: var(--color-text);"><strong>Karakteristik:</strong> {{ $klaster['karakteristik'] }}</p>
                                        
                                        <!-- Rekomendasi box -->
                                        <div style="background-color: color-mix(in srgb, var(--color-accent) 10%, transparent); border-left: 4px solid var(--color-accent); padding: 0.75rem; border-radius: var(--radius-btn); margin-bottom: 0.75rem;">
                                            <p class="text-xs font-semibold mb-1" style="color: var(--color-text);">Kebutuhan klaster ini (Tabel 18):</p>
                                            <p class="text-xs" style="color: var(--color-text);">{{ $klaster['rekomendasi'] }}</p>
                                        </div>
                                        
                                        <p class="text-xs" style="color: var(--color-text); opacity: 0.7;">
                                            <strong>Centroid:</strong> TC={{ $klaster['centroid']['TC'] }}, TE={{ $klaster['centroid']['TE'] }}, 
                                            ITT={{ $klaster['centroid']['ITT'] }}, YB={{ $klaster['centroid']['YB'] }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        </x-card>

                        <!-- SECTION 5: Derajat Keanggotaan Tabel + Cara Membaca -->
                        <x-card class="mb-6">
                            <h2 class="text-xl font-semibold mb-4" style="color: var(--color-text);">5. Derajat Keanggotaan Peserta</h2>

                            <!-- Tabel scrollable (md:hidden untuk kartu di HP) -->
                            <div class="md:hidden space-y-3 mb-6">
                                @foreach ($klasterResult['tabel_peserta'] as $peserta)
                                    <div style="border: 1px solid color-mix(in srgb, var(--color-text) 15%, transparent); border-radius: var(--radius-card); padding: 1rem;">
                                        <p class="font-medium text-sm mb-2" style="color: var(--color-text);">{{ $peserta['nama'] }}</p>
                                        <p class="text-xs mb-2 font-semibold" style="color: var(--color-success);">Profil dominan: {{ $peserta['klaster_dominan'] }}</p>
                                        <div class="space-y-1">
                                            @foreach ($peserta['memberships'] as $k => $m)
                                                <div class="flex justify-between text-xs">
                                                    <span style="color: var(--color-text);">Klaster {{ $k + 1 }}</span>
                                                    <span style="color: var(--color-text); font-medium;">{{ $m }}%</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Tabel di desktop (hidden md:block) -->
                            <div class="hidden md:block overflow-x-auto mb-6">
                                <table class="min-w-full text-sm divide-y" style="border-color: color-mix(in srgb, var(--color-text) 15%, transparent);">
                                    <thead style="background-color: var(--color-bg);">
                                        <tr>
                                            <th class="px-4 py-3 text-left font-medium" style="color: var(--color-text);">Peserta</th>
                                            @foreach (range(1, $klasterResult['c_optimal']) as $k)
                                                <th class="px-4 py-3 text-center font-medium" style="color: var(--color-text);">Klaster {{ $k }}</th>
                                            @endforeach
                                            <th class="px-4 py-3 text-center font-medium" style="color: var(--color-text);">Profil Dominan</th>
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

                            <!-- Cara Membaca Hasil -->
                            <div style="background-color: color-mix(in srgb, var(--color-accent) 8%, transparent); border-left: 4px solid var(--color-accent); padding: 1rem; border-radius: var(--radius-card);">
                                <h3 class="font-semibold mb-3 text-sm" style="color: var(--color-text);">Cara Membaca Hasil Ini</h3>
                                <div class="space-y-2 text-xs" style="color: var(--color-text);">
                                    <p><strong>1. Fuzzy C-Means (FCM):</strong> Algoritma mengelompokkan peserta berdasarkan kemiripan skor TC, TE, ITT, YB. Setiap peserta boleh sebagian masuk beberapa klaster (fuzzy), bukan kategori tegas.</p>
                                    <p><strong>2. Derajat Keanggotaan:</strong> Persentase menunjukkan seberapa kuat peserta "masuk" ke tiap klaster. Contoh: 58% di K1, 42% di K2 = peserta berada di perbatasan dua profil.</p>
                                    <p><strong>3. Indeks Validitas (PC, PE, XB, Silhouette):</strong> Mengukur kualitas pengelompokan. Silhouette tinggi = klaster terpisah jelas. XB rendah = klaster kompak dan terpisah.</p>
                                    <p><strong>4. Profil A–D:</strong> Ditentukan dari pola centroid terhadap profil konseptual. "Belum Cocok" = pola data tidak sesuai 4 profil utama.</p>
                                    <p><strong>5. Skor dan Centroid:</strong> Menggunakan rerata skala 1–4 (bukan estimasi PCM). Centroid adalah nilai rata-rata skor semua peserta dalam klaster.</p>
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
            @endif
        </div>
    </div>
</x-app-layout>
