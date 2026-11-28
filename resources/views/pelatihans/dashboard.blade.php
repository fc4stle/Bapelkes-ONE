<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Dashboard Rekap Panitia" subtitle="Ringkasan hal yang perlu ditindak hari ini." />
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Bagian 1: Panel Metrik Perlu Tindakan -->
            <div data-reveal class="rounded-xl border bg-white mb-8" style="border-color: var(--color-line);">
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-px overflow-hidden rounded-xl bg-[var(--color-line)]">
                    <!-- Menunggu Verifikasi -->
                    <div class="p-5 bg-white">
                        <p class="text-xs font-medium text-[var(--color-muted)]">Menunggu Verifikasi</p>
                        <p class="text-3xl font-semibold text-[var(--color-text)] mt-2" data-count-to="{{ $total_pending }}">{{ $total_pending }}</p>
                        <div class="mt-3">
                            @if ($total_pending > 0 && $pelatihan_pending_terbanyak)
                                <a href="{{ route('pelatihan.pendaftar', $pelatihan_pending_terbanyak) }}" class="text-sm font-medium text-[var(--color-primary)] hover:underline">
                                    Tinjau →
                                </a>
                            @else
                                <span class="text-sm text-[var(--color-muted)]">Tidak ada</span>
                            @endif
                        </div>
                    </div>

                    <!-- Perhatian Kuota -->
                    <div class="p-5 bg-white">
                        <div class="flex items-center gap-2">
                            <p class="text-xs font-medium text-[var(--color-muted)]">Perhatian Kuota</p>
                            @if ($pelatihan_perhatian > 0)
                                <span class="inline-block w-2 h-2 rounded-full" style="background-color: var(--color-warning);"></span>
                            @endif
                        </div>
                        <p class="text-3xl font-semibold text-[var(--color-text)] mt-2" data-count-to="{{ $pelatihan_perhatian }}">{{ $pelatihan_perhatian }}</p>
                        <div class="mt-3">
                            @if ($pelatihan_perhatian > 0)
                                <a href="{{ route('pelatihans.index') }}" class="text-sm font-medium text-[var(--color-primary)] hover:underline">
                                    Lihat →
                                </a>
                            @else
                                <span class="text-sm text-[var(--color-muted)]">Tidak ada</span>
                            @endif
                        </div>
                    </div>

                    <!-- Tanpa TIYAS -->
                    <div class="p-5 bg-white">
                        <p class="text-xs font-medium text-[var(--color-muted)]">Tanpa TIYAS</p>
                        <p class="text-3xl font-semibold text-[var(--color-text)] mt-2" data-count-to="{{ $pelatihan_tanpa_tiyas }}">{{ $pelatihan_tanpa_tiyas }}</p>
                        <div class="mt-3">
                            @if ($pelatihan_tanpa_tiyas > 0)
                                <a href="{{ route('pelatihans.index') }}" class="text-sm font-medium text-[var(--color-primary)] hover:underline">
                                    Tinjau →
                                </a>
                            @else
                                <span class="text-sm text-[var(--color-muted)]">Tidak ada</span>
                            @endif
                        </div>
                    </div>

                    <!-- Asrama Pending -->
                    <div class="p-5 bg-white">
                        <p class="text-xs font-medium text-[var(--color-muted)]">Asrama Pending</p>
                        <p class="text-3xl font-semibold text-[var(--color-text)] mt-2" data-count-to="{{ $asrama_pending }}">{{ $asrama_pending }}</p>
                        <div class="mt-3">
                            @if ($asrama_pending > 0 && $pelatihan_asrama_terbanyak)
                                <a href="{{ route('pelatihan.pendaftar', $pelatihan_asrama_terbanyak) }}" class="text-sm font-medium text-[var(--color-primary)] hover:underline">
                                    Tinjau →
                                </a>
                            @else
                                <span class="text-sm text-[var(--color-muted)]">Tidak ada</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bagian 2: Antrian Verifikasi -->
            <div data-reveal data-reveal-delay="1" class="rounded-xl border bg-white mb-8" style="border-color: var(--color-line);">
                <div class="p-5 border-b" style="border-color: var(--color-line);">
                    <h3 class="text-base font-semibold text-[var(--color-text)]">Antrian Verifikasi</h3>
                </div>

                @if ($antrian_verifikasi->isEmpty())
                    <div class="p-5">
                        <p class="text-sm text-[var(--color-muted)]">Semua pendaftaran sudah diverifikasi.</p>
                    </div>
                @else
                    <div class="p-5">
                        <div class="space-y-3">
                            @foreach ($antrian_verifikasi as $item)
                                <div class="flex items-center justify-between p-4 rounded-lg border" style="border-color: var(--color-line); background-color: var(--color-bg);">
                                    <div class="flex-1">
                                        <p class="font-medium text-[var(--color-text)]">{{ $item->peserta->name }}</p>
                                        <p class="text-sm text-[var(--color-muted)]">{{ $item->pelatihan->nama }}</p>
                                        <p class="text-xs text-[var(--color-muted)] mt-1">
                                            Terdaftar: {{ $item->created_at->format('d M Y H:i') }}
                                        </p>
                                    </div>
                                    <a href="{{ route('pelatihan.pendaftar', $item->pelatihan) }}" class="ml-4 px-3 py-2 text-sm font-medium rounded-lg transition" style="background-color: var(--color-primary); color: white;">
                                        Tinjau
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Bagian 3: Hasil TIYAS Terbaru -->
            <div data-reveal data-reveal-delay="2" class="rounded-xl border bg-white mb-8" style="border-color: var(--color-line);">
                <div class="p-5 border-b" style="border-color: var(--color-line);">
                    <h3 class="text-base font-semibold text-[var(--color-text)]">Hasil TIYAS Terbaru</h3>
                </div>

                @if ($tiyas_terbaru->isEmpty())
                    <div class="p-5">
                        <p class="text-sm text-[var(--color-muted)]">Belum ada evaluasi TIYAS yang masuk.</p>
                    </div>
                @else
                    <div class="p-5">
                        <div class="space-y-3">
                            @foreach ($tiyas_terbaru as $item)
                                <div class="flex items-center justify-between p-4 rounded-lg border" style="border-color: var(--color-line); background-color: var(--color-bg);">
                                    <div class="flex-1">
                                        <p class="font-medium text-[var(--color-text)]">{{ $item->nama }}</p>
                                        <p class="text-sm text-[var(--color-muted)]">
                                            <span class="font-semibold" style="color: var(--color-accent);">{{ $item->tiyas_responses_count }}</span>
                                            respons TIYAS
                                        </p>
                                        <p class="text-xs text-[var(--color-muted)] mt-1">
                                            Selesai: {{ $item->tanggal_selesai->format('d M Y') }}
                                        </p>
                                    </div>
                                    <a href="{{ route('tiyas.hasil', $item) }}" class="ml-4 px-3 py-2 text-sm font-medium rounded-lg transition" style="background-color: var(--color-accent); color: var(--color-text);">
                                        Lihat
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Bagian 4: Tabel Rekap Pelatihan -->
            <div data-reveal data-reveal-delay="3" class="rounded-xl border bg-white" style="border-color: var(--color-line);">
                <div class="p-5 border-b" style="border-color: var(--color-line);">
                    <h3 class="text-base font-semibold text-[var(--color-text)]">Dashboard Rekap Pelatihan</h3>
                </div>

                @if ($pelatihans->isEmpty())
                    <div class="p-5">
                        <p class="text-sm text-[var(--color-muted)]">Belum ada pelatihan</p>
                    </div>
                @else
                    <div class="p-5">
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead class="border-b" style="border-color: var(--color-line); background-color: var(--color-bg);">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-[var(--color-muted)]">Pelatihan</th>
                                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-[var(--color-muted)]">Total</th>
                                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-[var(--color-muted)]">Terverifikasi</th>
                                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-[var(--color-muted)]">Menunggu</th>
                                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-[var(--color-muted)]">Ditolak</th>
                                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-[var(--color-muted)]">Hadir</th>
                                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-[var(--color-muted)]">Asrama</th>
                                        <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-[var(--color-muted)]">Sertifikat</th>
                                    </tr>
                                </thead>
                                <tbody class="tabel-institusi divide-y" style="border-color: var(--color-line);">
                                    @foreach ($pelatihans as $pelatihan)
                                        <tr>
                                            <td class="px-4 py-4">
                                                <a href="{{ route('pelatihan.pendaftar', $pelatihan) }}" class="font-medium" style="color: var(--color-primary);">{{ $pelatihan->nama }}</a>
                                                <div class="text-xs text-[var(--color-muted)]">{{ $pelatihan->tanggal_mulai->format('d M Y') }} - {{ $pelatihan->tanggal_selesai->format('d M Y') }}</div>
                                            </td>
                                            <td class="px-4 py-4 text-center text-sm text-[var(--color-text)] font-bold">{{ $pelatihan->total_pendaftar }}</td>
                                            <td class="px-4 py-4 text-center text-sm font-medium text-[var(--color-text)]">{{ $pelatihan->terverifikasi }}</td>
                                            <td class="px-4 py-4 text-center text-sm font-medium text-[var(--color-text)]">{{ $pelatihan->menunggu_verifikasi }}</td>
                                            <td class="px-4 py-4 text-center text-sm font-medium text-[var(--color-text)]">{{ $pelatihan->ditolak }}</td>
                                            <td class="px-4 py-4 text-center text-sm font-medium" style="color: var(--color-primary);">{{ $pelatihan->hadir }}</td>
                                            <td class="px-4 py-4 text-center text-sm text-[var(--color-muted)]">{{ $pelatihan->butuh_asrama }}</td>
                                            <td class="px-4 py-4 text-center text-sm text-[var(--color-muted)]">{{ $pelatihan->sertifikat_terunduh }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $pelatihans->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
