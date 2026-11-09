<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl leading-tight" style="color: var(--color-text);">
            {{ __('Pilih Presensi') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if($pelatihans->count() > 0)
                <!-- Daftar Pelatihan yang Berlangsung -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($pelatihans as $pelatihan)
                        <div class="rounded-lg border p-6 shadow-sm hover:shadow-md transition" style="border-color: var(--color-accent); background-color: var(--color-bg);">
                            <!-- Judul Pelatihan -->
                            <h3 class="font-semibold text-lg mb-2" style="color: var(--color-text);">
                                {{ $pelatihan->nama }}
                            </h3>

                            <!-- Tanggal -->
                            <p class="text-sm mb-2" style="color: var(--color-text); opacity: 0.7;">
                                <span class="font-medium">Tanggal:</span>
                                {{ $pelatihan->tanggal_mulai->format('d M Y') }} - {{ $pelatihan->tanggal_selesai->format('d M Y') }}
                            </p>

                            <!-- Lokasi -->
                            <p class="text-sm mb-4" style="color: var(--color-text); opacity: 0.7;">
                                <span class="font-medium">Lokasi:</span>
                                {{ $pelatihan->lokasi ?? 'Belum ditentukan' }}
                            </p>

                            <!-- Statistik Kehadiran -->
                            <div class="mb-6 p-3 rounded" style="background-color: var(--color-surface); border-left: 4px solid var(--color-accent);">
                                <p class="text-sm font-medium" style="color: var(--color-text);">
                                    {{ $pelatihan->hadir_count }} dari {{ $pelatihan->diverifikasi_count }} peserta hadir
                                </p>
                            </div>

                            <!-- Tombol Buka Presensi -->
                            <a href="{{ route('pelatihan.presensi', $pelatihan) }}" 
                               class="inline-block w-full px-4 py-2 rounded text-center text-sm font-medium transition"
                               style="background-color: var(--color-primary); color: white; text-decoration: none;">
                                Buka Presensi
                            </a>
                        </div>
                    @endforeach
                </div>
            @else
                <!-- Empty State -->
                <div class="rounded-lg border p-12 text-center" style="border-color: var(--color-accent); background-color: var(--color-bg);">
                    <svg class="mx-auto h-12 w-12 mb-4" style="color: var(--color-text); opacity: 0.5;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>

                    <h3 class="text-lg font-medium mb-2" style="color: var(--color-text);">
                        Tidak ada pelatihan yang sedang berlangsung hari ini
                    </h3>

                    <p class="text-sm mb-6" style="color: var(--color-text); opacity: 0.7;">
                        Pelatihan akan muncul di halaman ini saat status bukan draft dan tanggal hari ini berada dalam rentang pelatihan.
                    </p>

                    <a href="{{ route('pelatihans.index') }}" 
                       class="inline-block px-4 py-2 rounded text-sm font-medium transition"
                       style="background-color: var(--color-primary); color: white; text-decoration: none;">
                        Kembali ke Kelola Pelatihan
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
