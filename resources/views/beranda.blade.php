<x-app-layout>
    <x-slot name="header">
        <!-- Hero Section dalam Band dengan Padding sejajar Navigation -->
        <div class="text-left">
            <!-- Badge Pil Kecil -->
            <div class="inline-block mb-4">
                <span class="inline-block px-3 py-1 text-xs font-medium rounded-full border border-white/50 text-white">
                    Balai Pelatihan Kesehatan DIY
                </span>
            </div>

            <!-- H1 + Paragraf wrapper -->
            <div class="max-w-2xl">
                <!-- H1 Hero -->
                <h1 class="text-3xl sm:text-4xl font-semibold leading-tight mb-4 text-white">
                    Pelatihan kesehatan Bapelkes DIY, dari daftar sampai sertifikat.
                </h1>

                <!-- Paragraf Deskripsi -->
                <p class="text-base sm:text-lg mb-8 leading-relaxed band-sub">
                    Lihat jadwal pelatihan, daftar, cetak kartu peserta, dan unduh sertifikat dari satu portal.
                </p>
            </div>

            <!-- Tombol CTA -->
            <div class="flex flex-col sm:flex-row gap-3 flex-wrap">
                @auth
                    @if (auth()->user()->isStaf())
                        <!-- Tombol Utama untuk Staf -->
                        <a href="{{ route('panitia.dashboard') }}" class="inline-block px-6 py-3 rounded-full font-semibold text-sm transition-colors hover:opacity-90 bg-[var(--color-gold-soft)] text-[var(--color-text)]">
                            Rekap Panitia
                        </a>
                        <!-- Tombol Sekunder untuk Staf -->
                        <a href="{{ route('pelatihans.index') }}" class="inline-block px-6 py-3 rounded-full font-semibold text-sm border border-white/50 text-white transition-colors hover:bg-white/10">
                            Kelola Pelatihan
                        </a>
                    @else
                        <!-- Tombol Utama untuk Peserta -->
                        <a href="{{ route('pelatihan.katalog') }}" class="inline-block px-6 py-3 rounded-full font-semibold text-sm transition-colors hover:opacity-90 bg-[var(--color-gold-soft)] text-[var(--color-text)]">
                            Lihat Katalog
                        </a>
                        <!-- Tombol Sekunder untuk Peserta -->
                        <a href="{{ route('pendaftarans.index') }}" class="inline-block px-6 py-3 rounded-full font-semibold text-sm border border-white/50 text-white transition-colors hover:bg-white/10">
                            Pendaftaran Saya
                        </a>
                    @endif
                @else
                    <!-- Tombol untuk Tamu -->
                    <a href="{{ route('pelatihan.katalog') }}" class="inline-block px-6 py-3 rounded-full font-semibold text-sm transition-colors hover:opacity-90 bg-[var(--color-gold-soft)] text-[var(--color-text)]">
                        Lihat Katalog
                    </a>
                    <a href="{{ route('register') }}" class="inline-block px-6 py-3 rounded-full font-semibold text-sm border border-white/50 text-white transition-colors hover:bg-white/10">
                        Daftar Akun
                    </a>
                @endauth
            </div>
        </div>
    </x-slot>

    <!-- Isi Halaman (di luar band) -->
    <div style="background-color: var(--color-bg);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

            <!-- Bar Cari (Hanya untuk Tamu/Peserta) -->
            <div data-reveal>
                @guest
                    <div class="mb-12">
                        <form action="{{ route('pelatihan.katalog') }}" method="GET" class="flex gap-2 max-w-md">
                            <input 
                                type="text" 
                                name="search" 
                                placeholder="Cari pelatihan..." 
                                class="flex-1 px-4 py-3 rounded-full border focus:outline-none focus:ring-2" 
                                style="border-color: var(--color-line);"
                            >
                            <button 
                                type="submit" 
                                class="px-6 py-3 rounded-full font-semibold text-sm text-white transition-colors hover:opacity-90"
                                style="background-color: var(--color-primary);"
                            >
                                Cari
                            </button>
                        </form>
                    </div>
                @else
                    @if (!auth()->user()->isStaf())
                        <div class="mb-12">
                            <form action="{{ route('pelatihan.katalog') }}" method="GET" class="flex gap-2 max-w-md">
                                <input 
                                    type="text" 
                                    name="search" 
                                    placeholder="Cari pelatihan..." 
                                    class="flex-1 px-4 py-3 rounded-full border focus:outline-none focus:ring-2" 
                                    style="border-color: var(--color-line);"
                                >
                                <button 
                                    type="submit" 
                                    class="px-6 py-3 rounded-full font-semibold text-sm text-white transition-colors hover:opacity-90"
                                    style="background-color: var(--color-primary);"
                                >
                                    Cari
                                </button>
                            </form>
                        </div>
                    @endif
                @endguest
            </div>

            <!-- Pelatihan yang Sedang Dibuka -->
            <div data-reveal data-reveal-delay="1">
                <h2 class="text-2xl font-bold mb-6" style="color: var(--color-text);">
                    Pelatihan yang sedang dibuka
                </h2>

                @php
                    $pelatihans = \App\Models\Pelatihan::belumSelesai()
                        ->where('status', 'dibuka')
                        ->latest()
                        ->limit(3)
                        ->get();
                @endphp

                @if ($pelatihans->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach ($pelatihans as $pelatihan)
                            <div class="p-6 rounded-lg border" style="background-color: white; border-color: var(--color-line);">
                                <!-- Nama Pelatihan -->
                                <h3 class="text-lg font-bold mb-2" style="color: var(--color-text);">
                                    {{ $pelatihan->nama }}
                                </h3>

                                <!-- Tanggal -->
                                <p class="text-sm mb-2" style="color: var(--color-muted);">
                                    <span style="font-weight: 500;">Tanggal:</span> 
                                    {{ $pelatihan->tanggal_mulai?->format('d M Y') ?? '-' }}
                                </p>

                                <!-- Lokasi -->
                                <p class="text-sm mb-4" style="color: var(--color-muted);">
                                    <span style="font-weight: 500;">Lokasi:</span> 
                                    {{ $pelatihan->lokasi ?? '-' }}
                                </p>

                                <!-- Kuota -->
                                <div class="mb-4 p-3 rounded-lg" style="background-color: var(--color-bg);">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-sm font-medium" style="color: var(--color-text);">
                                            Sisa Kuota
                                        </span>
                                        @php
                                            $sisa = $pelatihan->sisaKuota();
                                            $warna = $sisa > 10 ? 'var(--color-success)' : ($sisa > 0 ? 'var(--color-warning)' : 'var(--color-danger)');
                                        @endphp
                                        <span class="text-sm font-bold" style="color: {{ $warna }};">
                                            {{ $sisa }} dari {{ $pelatihan->kuota }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Tombol Detail -->
                                <a href="{{ route('pelatihan.detail', $pelatihan) }}" class="inline-block w-full text-center px-4 py-2 rounded-full font-semibold text-sm text-white transition-colors hover:opacity-90" style="background-color: var(--color-primary);">
                                    Lihat detail
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-6 rounded-lg text-center" style="background-color: white; border: 1px solid var(--color-line);">
                        <p style="color: var(--color-muted);">
                            Belum ada pelatihan yang dibuka.
                        </p>
                    </div>
                @endif
            </div>

            <!-- Cara Mendaftar -->
            <div data-reveal data-reveal-delay="2" class="mb-12">
                <h2 class="text-2xl font-bold mb-6" style="color: var(--color-text);">
                    Cara mendaftar
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Langkah 1 -->
                    <div>
                        <div class="flex items-center mb-4">
                            <span class="inline-block w-8 h-8 rounded-full font-bold text-sm flex items-center justify-center text-white mr-3" style="background-color: var(--color-accent); color: var(--color-text);">
                                1
                            </span>
                            <h3 class="text-lg font-bold" style="color: var(--color-text);">
                                Daftar akun
                            </h3>
                        </div>
                        <p style="color: var(--color-muted); border-top: 1px solid var(--color-line); padding-top: 1rem;">
                            Buat akun peserta Anda dengan email dan password yang aman.
                        </p>
                    </div>

                    <!-- Langkah 2 -->
                    <div>
                        <div class="flex items-center mb-4">
                            <span class="inline-block w-8 h-8 rounded-full font-bold text-sm flex items-center justify-center text-white mr-3" style="background-color: var(--color-accent); color: var(--color-text);">
                                2
                            </span>
                            <h3 class="text-lg font-bold" style="color: var(--color-text);">
                                Pilih pelatihan
                            </h3>
                        </div>
                        <p style="color: var(--color-muted); border-top: 1px solid var(--color-line); padding-top: 1rem;">
                            Lihat katalog pelatihan yang tersedia dan pilih sesuai minat Anda.
                        </p>
                    </div>

                    <!-- Langkah 3 -->
                    <div>
                        <div class="flex items-center mb-4">
                            <span class="inline-block w-8 h-8 rounded-full font-bold text-sm flex items-center justify-center text-white mr-3" style="background-color: var(--color-accent); color: var(--color-text);">
                                3
                            </span>
                            <h3 class="text-lg font-bold" style="color: var(--color-text);">
                                Cetak sertifikat
                            </h3>
                        </div>
                        <p style="color: var(--color-muted); border-top: 1px solid var(--color-line); padding-top: 1rem;">
                            Setelah selesai, cetak kartu peserta dan unduh sertifikat dari dashboard.
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
