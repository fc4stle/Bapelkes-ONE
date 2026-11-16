@if ($cakupan === 'semua' && isset($klasterPelatihan))
    <div class="mt-8 bg-white rounded-lg shadow p-6">
        <h3 class="text-xl font-bold mb-4">Pengelompokan Pelatihan (FCM antar Pelatihan)</h3>

        @if ($klasterPelatihan['status'] === 'kurang_data')
            <div class="bg-blue-50 border border-blue-200 rounded p-4">
                <p class="text-blue-900">
                    Belum cukup pelatihan untuk pengelompokan (butuh minimal 6, saat ini {{ $klasterPelatihan['jumlah_sekarang'] }}).
                </p>
            </div>
        @else
            <!-- Scatter chart placeholder with data points -->
            <div class="mb-6">
                <div class="bg-gray-100 rounded p-4 h-80" data-chart="fcm-pelatihan-scatter"
                     data-points="{{ json_encode(array_map(fn($idx, $p) => ['id' => $idx + 1, 'x' => $p['mean']['TC'] ?? 0, 'y' => $p['mean']['TE'] ?? 0, 'cluster' => $p['klaster_dominan']], array_keys($klasterPelatihan['tabel_pelatihan']), array_values($klasterPelatihan['tabel_pelatihan']))) }}">
                    <p class="text-gray-500 text-center pt-36">Scatter FCM Pelatihan (Chart akan dirender oleh JavaScript)</p>
                </div>
            </div>

            <!-- Membership stacked bar placeholder -->
            <div class="mb-6">
                <div class="bg-gray-100 rounded p-4 h-40" data-chart="fcm-pelatihan-membership">
                    <p class="text-gray-500 text-center pt-16">Membership Bar FCM Pelatihan (Chart akan dirender oleh JavaScript)</p>
                </div>
            </div>

            <!-- Membership table -->
            <div class="mb-6 overflow-auto">
                <h4 class="font-bold mb-2">Keanggotaan Klaster per Pelatihan (%)</h4>
                <table class="min-w-full border-collapse border border-gray-300">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border border-gray-300 px-3 py-2 text-left">Pelatihan</th>
                            @foreach (range(1, $klasterPelatihan['c_optimal']) as $c)
                                <th class="border border-gray-300 px-3 py-2 text-center">Klaster {{ $c }}</th>
                            @endforeach
                            <th class="border border-gray-300 px-3 py-2 text-left">Dominan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($klasterPelatihan['tabel_pelatihan'] as $idx => $pelatihan)
                            <tr>
                                <td class="border border-gray-300 px-3 py-2">P{{ $idx + 1 }} - {{ $pelatihan['nama'] }}</td>
                                @foreach (range(1, $klasterPelatihan['c_optimal']) as $c)
                                    <td class="border border-gray-300 px-3 py-2 text-center">
                                        {{ round($pelatihan['keanggotaan'][$c] ?? 0, 1) }}%
                                    </td>
                                @endforeach
                                <td class="border border-gray-300 px-3 py-2">
                                    Klaster {{ $pelatihan['klaster_dominan'] }} ({{ round($pelatihan['keanggotaan'][$pelatihan['klaster_dominan']] ?? 0, 1) }}%)
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Cluster cards -->
            <div class="grid grid-cols-1 md:grid-cols-{{ $klasterPelatihan['c_optimal'] }} gap-4">
                @foreach ($klasterPelatihan['tabel_klaster'] as $klaster)
                    @php
                        $warnaKlaster = config('tiyas.warna_klaster', [])[$klaster['klaster'] - 1] ?? '#cccccc';
                    @endphp
                    <div class="bg-white border-2 rounded p-4" data-klaster="{{ $klaster['klaster'] }}" data-color="{{ $warnaKlaster }}">
                        <h4 class="font-bold mb-2">Klaster {{ $klaster['klaster'] }} - {{ $klaster['profil'] }}</h4>
                        <p class="text-sm mb-2"><strong>Pelatihan:</strong></p>
                        <ul class="text-sm list-disc pl-5">
                            @foreach ($klasterPelatihan['tabel_pelatihan'] as $idx => $pelatihan)
                                @if ($pelatihan['klaster_dominan'] == $klaster['klaster'])
                                    <li>P{{ $idx + 1 }} - {{ $pelatihan['nama'] }}</li>
                                @endif
                            @endforeach
                        </ul>
                        <p class="text-sm mt-3"><strong>Centroid:</strong></p>
                        <ul class="text-sm">
                            <li>TC: {{ round($klaster['centroid']['TC'], 2) }}</li>
                            <li>TE: {{ round($klaster['centroid']['TE'], 2) }}</li>
                            <li>ITT: {{ round($klaster['centroid']['ITT'], 2) }}</li>
                            <li>YB: {{ round($klaster['centroid']['YB'], 2) }}</li>
                        </ul>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endif
