<x-app-layout>
    <div style="background-color: var(--color-bg);">
        <!-- Hero Section -->
        <section class="py-10 sm:py-16 px-4">
            <div class="max-w-5xl mx-auto text-center">
                <h1 class="text-3xl sm:text-5xl font-bold mb-4" style="color: var(--color-text);">Sistem Informasi Portal Pelatihan Terpadu</h1>
                <p class="text-base sm:text-xl mb-8" style="color: #555;">Portal terpadu pelatihan dan layanan peserta Bapelkes DIY</p>

                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    @auth
                        @if (auth()->user()->isStaf())
                            <a href="{{ route('panitia.dashboard') }}" class="w-full sm:w-auto px-8 py-3 rounded-[var(--radius-btn)] text-white font-semibold text-center" style="background-color: var(--color-primary);">
                                Rekap Panitia
                            </a>
                            <a href="{{ route('pelatihans.index') }}" class="w-full sm:w-auto px-8 py-3 rounded-[var(--radius-btn)] font-semibold border-2 bg-transparent text-center" style="border-color: var(--color-primary); color: var(--color-primary);">
                                Kelola Pelatihan
                            </a>
                        @else
                            <a href="{{ route('pelatihan.katalog') }}" class="w-full sm:w-auto px-8 py-3 rounded-[var(--radius-btn)] text-white font-semibold text-center" style="background-color: var(--color-primary);">
                                Lihat Katalog
                            </a>
                            <a href="{{ route('pendaftarans.index') }}" class="w-full sm:w-auto px-8 py-3 rounded-[var(--radius-btn)] font-semibold border-2 bg-transparent text-center" style="border-color: var(--color-primary); color: var(--color-primary);">
                                Pendaftaran Saya
                            </a>
                        @endif
                    @else
                        <a href="{{ route('pelatihan.katalog') }}" class="w-full sm:w-auto px-8 py-3 rounded-[var(--radius-btn)] text-white font-semibold text-center" style="background-color: var(--color-primary);">
                            Lihat Katalog
                        </a>
                        <a href="{{ route('register') }}" class="w-full sm:w-auto px-8 py-3 rounded-[var(--radius-btn)] font-semibold border-2 bg-transparent text-center" style="border-color: var(--color-primary); color: var(--color-primary);">
                            Daftar Akun
                        </a>
                    @endauth
                </div>
            </div>
        </section>

        <!-- Search Section -->
        @if (!auth()->check() || auth()->user()->isPeserta())
            <section class="py-12 px-4" style="border-top: 1px solid #e5e7eb;">
                <div class="max-w-5xl mx-auto">
                    <form method="GET" action="{{ route('pelatihan.katalog') }}" class="flex flex-col sm:flex-row gap-3">
                        <input type="text" name="q" placeholder="Cari pelatihan..." class="flex-1 px-4 py-3 rounded-[var(--radius-btn)] border-2 w-full" style="border-color: #e5e7eb; color: var(--color-text);">
                        <button type="submit" class="w-full sm:w-auto px-6 py-3 rounded-[var(--radius-btn)] text-white font-semibold" style="background-color: var(--color-primary);">
                            Cari
                        </button>
                    </form>
                </div>
            </section>
        @endif

        <!-- How to Register Section -->
        <section class="py-10 sm:py-16 px-4" style="border-top: 1px solid #e5e7eb;">
            <div class="max-w-5xl mx-auto">
                <h2 class="text-2xl sm:text-3xl font-bold text-center mb-12" style="color: var(--color-text);">Cara Mendaftar</h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-8">
                    <!-- Step 1 -->
                    <div class="text-center">
                        <div class="w-16 h-16 rounded-full mx-auto mb-4 flex items-center justify-center text-white text-2xl font-bold" style="background-color: var(--color-primary);">
                            1
                        </div>
                        <h3 class="text-xl font-bold mb-2" style="color: var(--color-text);">Buat Akun</h3>
                        <p style="color: #666;">Daftar dengan email dan kata sandi Anda. Akun baru akan langsung berperan sebagai peserta.</p>
                    </div>

                    <!-- Step 2 -->
                    <div class="text-center">
                        <div class="w-16 h-16 rounded-full mx-auto mb-4 flex items-center justify-center text-2xl font-bold" style="background-color: var(--color-accent); color: var(--color-text);">
                            2
                        </div>
                        <h3 class="text-xl font-bold mb-2" style="color: var(--color-text);">Pilih Pelatihan</h3>
                        <p style="color: #666;">Lihat katalog pelatihan yang tersedia dan pilih yang sesuai dengan kebutuhan Anda, lalu isi formulir pendaftaran.</p>
                    </div>

                    <!-- Step 3 -->
                    <div class="text-center">
                        <div class="w-16 h-16 rounded-full mx-auto mb-4 flex items-center justify-center text-white text-2xl font-bold" style="background-color: #5C3D2E;">
                            3
                        </div>
                        <h3 class="text-xl font-bold mb-2" style="color: var(--color-text);">Tunggu Verifikasi</h3>
                        <p style="color: #666;">Panitia akan memverifikasi pendaftaran Anda. Setelah disetujui, Anda dapat mengunduh kartu peserta.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Open Trainings Section -->
        <section class="py-10 sm:py-16 px-4" style="border-top: 1px solid #e5e7eb;">
            <div class="max-w-5xl mx-auto">
                <h2 class="text-2xl sm:text-3xl font-bold text-center mb-12" style="color: var(--color-text);">Pelatihan yang Sedang Dibuka</h2>

                @php
                    $trainings = \App\Models\Pelatihan::where('status', 'dibuka')
                        ->orderBy('tanggal_mulai', 'asc')
                        ->limit(3)
                        ->get();
                @endphp

                @if ($trainings->isEmpty())
                    <div class="text-center py-12">
                        <p style="color: #999; font-size: 18px;">Belum ada pelatihan yang dibuka.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        @foreach ($trainings as $training)
                            <x-card style="border: 1px solid #e5e7eb;">
                                <h3 class="text-lg font-bold mb-2" style="color: var(--color-text);">{{ $training->nama }}</h3>
                                <p class="text-sm mb-4" style="color: #666;">
                                    <strong>Tanggal:</strong> {{ $training->tanggal_mulai->format('d M Y') }} - {{ $training->tanggal_selesai->format('d M Y') }}
                                </p>
                                <p class="text-sm mb-4" style="color: #666;">
                                    <strong>Lokasi:</strong> {{ $training->lokasi }}
                                </p>
                                <p class="text-sm mb-4" style="color: #666;">
                                    <strong>Sisa Kuota:</strong>
                                    <span class="inline-block rounded-full text-xs font-semibold px-3 py-1" style="@if($training->isFull()) background-color: var(--color-danger); @else background-color: var(--color-success); @endif color: white;">
                                        @if($training->isFull())
                                            Kuota penuh
                                        @else
                                            Sisa {{ $training->sisaKuota() }} dari {{ $training->kuota }}
                                        @endif
                                    </span>
                                </p>
                                <a href="{{ route('pelatihan.detail', $training) }}" class="inline-block px-4 py-2 rounded-[var(--radius-btn)] text-white font-semibold" style="background-color: var(--color-primary);">
                                    Lihat Detail
                                </a>
                            </x-card>
                        @endforeach
                    </div>
                @endif

                <div class="text-center mt-12">
                    <a href="{{ route('pelatihan.katalog') }}" class="text-lg font-semibold" style="color: var(--color-primary);">
                        Lihat Semua Pelatihan →
                    </a>
                </div>
            </div>
        </section>
    </div>
</x-app-layout>
