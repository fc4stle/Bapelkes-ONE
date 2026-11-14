<!-- TIYAS Hasil Clustering Page - Replacement section untuk deskriptif & FCM charts -->
<!-- TODO: Replace placeholder di hasil.blade.php dengan ini setelah testing -->

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
