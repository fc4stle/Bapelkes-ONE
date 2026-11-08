<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl leading-tight text-[var(--color-text)]">
            {{ __('Dashboard Peserta') }}
        </h2>
    </x-slot>

    <div class="py-[var(--sp-8)]">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-[var(--sp-8)]">
            @if ($totalPendaftaran === 0)
                {{-- Belum ada pendaftaran --}}
                <div class="rounded-[var(--radius-card)] border border-gray-200 bg-[var(--color-surface)] p-[var(--sp-6)] shadow-[var(--shadow-card)] text-center">
                    <h3 class="text-lg font-semibold text-[var(--color-text)] mb-[var(--sp-2)]">
                        Anda belum mendaftar pelatihan apa pun
                    </h3>
                    <p class="text-sm text-gray-600 mb-[var(--sp-4)]">
                        Jelajahi katalog pelatihan yang tersedia dan daftarkan diri Anda sekarang.
                    </p>
                    <a href="{{ route('pelatihan.katalog') }}" class="inline-flex items-center px-4 py-2 bg-[var(--color-primary)] border border-transparent rounded-[var(--radius-btn)] font-semibold text-xs text-white uppercase tracking-widest hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:ring-offset-2 transition ease-in-out duration-150">
                        Lihat katalog
                    </a>
                </div>
            @else
                {{-- A. Sapaan --}}
                <div>
                    <h1 class="text-2xl font-bold text-[var(--color-text)]">
                        Halo, {{ $user->name }}
                    </h1>
                    <p class="mt-[var(--sp-2)] text-sm text-gray-600">
                        Pantau status pendaftaran pelatihan Anda dan lihat pelatihan yang dibuka.
                    </p>
                </div>

                {{-- A1. Banner Pengingat (0-1 hari) --}}
                @if ($bannerPengingat)
                    <div class="rounded-[var(--radius-card)] border border-[var(--color-warning)] bg-[var(--color-surface)] p-[var(--sp-4)] shadow-[var(--shadow-card)]" x-data="{}">
                        <div class="flex items-start gap-[var(--sp-4)]">
                            <div class="flex-1">
                                <h3 class="font-semibold text-[var(--color-warning)]">
                                    ⚠️ {{ $bannerPengingat['label'] }}
                                </h3>
                                <p class="mt-[var(--sp-1)] text-sm text-gray-700">
                                    <strong>{{ $bannerPengingat['pendaftaran']->pelatihan->nama }}</strong>
                                </p>
                                <p class="mt-[var(--sp-1)] text-xs text-gray-600">
                                    Mulai: <strong>{{ $bannerPengingat['pendaftaran']->pelatihan->tanggal_mulai->translatedFormat('d M Y') }}</strong>
                                </p>
                            </div>
                            <a href="{{ route('pendaftarans.kartu', $bannerPengingat['pendaftaran']) }}" class="inline-flex items-center px-3 py-2 bg-[var(--color-warning)] border border-transparent rounded-[var(--radius-btn)] font-semibold text-xs text-white uppercase tracking-widest hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-[var(--color-warning)] focus:ring-offset-2 transition ease-in-out duration-150 whitespace-nowrap">
                                Lihat Kartu
                            </a>
                        </div>
                    </div>
                @endif

                {{-- B. Tiga angka ringkas --}}
                <div class="grid grid-cols-3 gap-[var(--sp-4)] sm:gap-[var(--sp-6)]">
                    <div class="rounded-[var(--radius-card)] border border-gray-200 bg-[var(--color-surface)] p-[var(--sp-4)] shadow-[var(--shadow-card)] text-center">
                        <div class="text-2xl sm:text-3xl font-bold text-[var(--color-primary)]">
                            {{ $totalPendaftaran }}
                        </div>
                        <p class="mt-[var(--sp-1)] text-xs sm:text-sm text-gray-600">
                            Total Pendaftaran
                        </p>
                    </div>
                    <div class="rounded-[var(--radius-card)] border border-gray-200 bg-[var(--color-surface)] p-[var(--sp-4)] shadow-[var(--shadow-card)] text-center">
                        <div class="text-2xl sm:text-3xl font-bold text-[var(--color-warning)]">
                            {{ $menungguVerifikasi }}
                        </div>
                        <p class="mt-[var(--sp-1)] text-xs sm:text-sm text-gray-600">
                            Menunggu Verifikasi
                        </p>
                    </div>
                    <div class="rounded-[var(--radius-card)] border border-gray-200 bg-[var(--color-surface)] p-[var(--sp-4)] shadow-[var(--shadow-card)] text-center">
                        <div class="text-2xl sm:text-3xl font-bold text-[var(--color-success)]">
                            {{ $terverifikasi }}
                        </div>
                        <p class="mt-[var(--sp-1)] text-xs sm:text-sm text-gray-600">
                            Terverifikasi
                        </p>
                    </div>
                </div>

                {{-- C. Langkah Berikutnya --}}
                <div>
                    <h2 class="text-lg font-semibold text-[var(--color-text)] mb-[var(--sp-4)]">
                        Langkah Berikutnya
                    </h2>
                    <div class="space-y-[var(--sp-4)]">
                        @forelse ($pendaftarans->take(5) as $pendaftaran)
                            @php
                                $langkah = $pendaftaran->langkahBerikutnya();
                                $borderLeftColor = match ($langkah['tone']) {
                                    'info' => 'border-l-gray-400',
                                    'sukses' => 'border-l-[var(--color-success)]',
                                    'perhatian' => 'border-l-[var(--color-accent)]',
                                    'bahaya' => 'border-l-[var(--color-danger)]',
                                    default => 'border-l-gray-400',
                                };
                            @endphp
                            <div class="rounded-[var(--radius-card)] border border-gray-200 border-l-4 {{ $borderLeftColor }} bg-[var(--color-surface)] p-[var(--sp-4)] shadow-[var(--shadow-card)]">
                                <div class="flex items-start justify-between gap-[var(--sp-4)]">
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-[var(--color-text)]">
                                            {{ $pendaftaran->pelatihan->nama }}
                                        </h3>
                                        <div class="mt-[var(--sp-2)]">
                                            <x-status-badge :status="$pendaftaran->status_verifikasi === 'diverifikasi' ? 'approved' : ($pendaftaran->status_verifikasi === 'pending' ? 'pending' : 'rejected')" />
                                        </div>
                                        <h4 class="mt-[var(--sp-3)] text-sm font-semibold text-[var(--color-text)]">
                                            {{ $langkah['judul'] }}
                                        </h4>
                                        <p class="mt-[var(--sp-1)] text-xs sm:text-sm text-gray-600">
                                            {{ $langkah['deskripsi'] }}
                                        </p>
                                    </div>
                                </div>

                                @if (!empty($langkah['aksi']))
                                    <div class="mt-[var(--sp-4)] flex flex-wrap gap-[var(--sp-2)]">
                                        @foreach ($langkah['aksi'] as $index => $tombol)
                                            @if ($index === 0)
                                                <a href="{{ $tombol['url'] }}" class="inline-flex items-center px-4 py-2 bg-[var(--color-primary)] border border-transparent rounded-[var(--radius-btn)] font-semibold text-xs text-white uppercase tracking-widest hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:ring-offset-2 transition ease-in-out duration-150">
                                                    {{ $tombol['label'] }}
                                                </a>
                                            @else
                                                <a href="{{ $tombol['url'] }}" class="inline-flex items-center px-4 py-2 bg-[var(--color-surface)] border border-[var(--color-primary)] rounded-[var(--radius-btn)] font-semibold text-xs text-[var(--color-primary)] uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:ring-offset-2 transition ease-in-out duration-150">
                                                    {{ $tombol['label'] }}
                                                </a>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-gray-600">Tidak ada pendaftaran untuk ditampilkan.</p>
                        @endforelse
                    </div>
                </div>

                {{-- C1. Pengingat Pelatihan --}}
                @if ($pengingats->isNotEmpty())
                    <div>
                        <h2 class="text-lg font-semibold text-[var(--color-text)] mb-[var(--sp-4)]">
                            Pengingat
                        </h2>
                        <div class="space-y-[var(--sp-4)]">
                            @foreach ($pengingats as $item)
                                @php
                                    $pendaftaran = $item['pendaftaran'];
                                    $urgensiColor = match ($item['urgensi']) {
                                        'tinggi' => 'border-l-[var(--color-danger)]',
                                        'sedang' => 'border-l-[var(--color-warning)]',
                                        default => 'border-l-[var(--color-accent)]',
                                    };
                                @endphp
                                <div class="rounded-[var(--radius-card)] border border-gray-200 border-l-4 {{ $urgensiColor }} bg-[var(--color-surface)] p-[var(--sp-4)] shadow-[var(--shadow-card)]">
                                    <div class="flex items-start justify-between gap-[var(--sp-4)]">
                                        <div class="flex-1">
                                            <h3 class="font-semibold text-[var(--color-text)]">
                                                {{ $pendaftaran->pelatihan->nama }}
                                            </h3>
                                            <p class="mt-[var(--sp-1)] text-sm font-semibold text-[var(--color-accent)]">
                                                {{ $item['label'] }}
                                            </p>
                                            <p class="mt-[var(--sp-2)] text-xs text-gray-600">
                                                <span class="font-semibold">Tanggal Mulai:</span> {{ $pendaftaran->pelatihan->tanggal_mulai->translatedFormat('d M Y') }}
                                            </p>
                                            <p class="mt-[var(--sp-1)] text-xs text-gray-600">
                                                <span class="font-semibold">Lokasi:</span> {{ $pendaftaran->pelatihan->lokasi }}
                                            </p>
                                            @if ($pendaftaran->butuh_asrama)
                                                <p class="mt-[var(--sp-1)] text-xs text-gray-600">
                                                    <span class="font-semibold">Kamar:</span> 
                                                    @if ($pendaftaran->kamar)
                                                        {{ $pendaftaran->kamar->nama }}
                                                    @else
                                                        Belum ditugaskan
                                                    @endif
                                                </p>
                                                <p class="mt-[var(--sp-1)] text-xs text-gray-600">
                                                    <span class="font-semibold">Check-in:</span> 
                                                    @if ($pendaftaran->check_in)
                                                        {{ $pendaftaran->check_in->translatedFormat('d M Y') }}
                                                    @else
                                                        Belum ditentukan
                                                    @endif
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="mt-[var(--sp-4)] flex gap-[var(--sp-2)]">
                                        <a href="{{ route('pendaftarans.kartu', $pendaftaran) }}" class="inline-flex items-center px-4 py-2 bg-[var(--color-primary)] border border-transparent rounded-[var(--radius-btn)] font-semibold text-xs text-white uppercase tracking-widest hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:ring-offset-2 transition ease-in-out duration-150">
                                            Kartu Peserta
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- D. Pelatihan yang dibuka --}}
                <div>
                    <h2 class="text-lg font-semibold text-[var(--color-text)] mb-[var(--sp-4)]">
                        Pelatihan yang Dibuka
                    </h2>
                    @if ($pelatihansNew->isEmpty())
                        <div class="rounded-[var(--radius-card)] border border-gray-200 bg-[var(--color-surface)] p-[var(--sp-4)] shadow-[var(--shadow-card)] text-center">
                            <p class="text-sm text-gray-600">
                                Belum ada pelatihan lain yang dibuka.
                            </p>
                        </div>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-[var(--sp-4)]">
                            @foreach ($pelatihansNew as $pelatihan)
                                <div class="rounded-[var(--radius-card)] border border-gray-200 bg-[var(--color-surface)] p-[var(--sp-4)] shadow-[var(--shadow-card)]">
                                    <h3 class="font-semibold text-[var(--color-text)] line-clamp-2">
                                        {{ $pelatihan->nama }}
                                    </h3>
                                    <p class="mt-[var(--sp-2)] text-xs text-gray-600">
                                        <span class="font-semibold">Mulai:</span> {{ $pelatihan->tanggal_mulai->translatedFormat('d M Y') }}
                                    </p>
                                    <p class="mt-[var(--sp-1)] text-xs text-gray-600">
                                        <span class="font-semibold">Sisa Kuota:</span>
                                        @if ($pelatihan->sisaKuota() <= 0)
                                            <span class="text-[var(--color-danger)]">Penuh</span>
                                        @else
                                            {{ $pelatihan->sisaKuota() }} peserta
                                        @endif
                                    </p>
                                    <a href="{{ route('pelatihan.detail', $pelatihan) }}" class="mt-[var(--sp-4)] inline-flex items-center px-4 py-2 bg-[var(--color-surface)] border border-[var(--color-primary)] rounded-[var(--radius-btn)] font-semibold text-xs text-[var(--color-primary)] uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)] focus:ring-offset-2 transition ease-in-out duration-150">
                                        Lihat detail
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
