<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-[var(--color-text)] leading-tight">
            Katalog Pelatihan
        </h2>
    </x-slot>

    <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-[var(--color-surface)] shadow rounded-[var(--radius-card)]">
    <div class="px-4 py-5 sm:p-6">
        <h1 class="text-[var(--fs-h1)] font-bold text-[var(--color-text)] mb-[var(--sp-6)]">Katalog Pelatihan</h1>

        <form method="GET" action="{{ route('pelatihan.katalog') }}" class="mb-[var(--sp-6)] flex flex-col gap-[var(--sp-4)] sm:flex-row sm:items-end">
            <div class="flex-1">
                <x-input-label for="q" value="Cari Pelatihan" />
                <x-text-input type="text" name="q" id="q" value="{{ $search }}" placeholder="Cari nama pelatihan..." class="mt-1 block w-full" />
            </div>

            <div>
                <x-input-label for="metode" value="Metode" />
                <select name="metode" id="metode" class="mt-1 block w-full rounded-[var(--radius-btn)] border-gray-300 shadow-sm focus:border-[var(--color-primary)] focus:ring-[var(--color-primary)] sm:text-sm border px-3 py-2">
                    <option value="">Semua Metode</option>
                    @foreach (\App\Enums\MetodePelatihan::cases() as $metodeOption)
                        <option value="{{ $metodeOption->value }}" {{ $metode === $metodeOption->value ? 'selected' : '' }}>{{ $metodeOption->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-[var(--sp-3)]">
                <x-primary-button type="submit">Cari</x-primary-button>
                @if ($search || $metode)
                    <a href="{{ route('pelatihan.katalog') }}" class="text-sm text-gray-500 hover:text-gray-700">Reset</a>
                @endif
            </div>
        </form>

        @if ($pelatihans->isEmpty())
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-[var(--color-text)]">Tidak ada pelatihan ditemukan</h3>
                <p class="mt-1 text-sm text-gray-500">Coba ubah kata kunci pencarian atau filter metode.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-[var(--sp-4)]">
                @foreach ($pelatihans as $pelatihan)
                    <x-card variant="{{ $pelatihan->isFull() ? 'unavailable' : 'normal' }}">
                        <h3 class="font-semibold text-[var(--color-text)]">{{ $pelatihan->nama }}</h3>
                        @if ($pelatihan->deskripsi)
                            <p class="text-[var(--fs-caption)] text-gray-500 mt-1">{{ Str::limit($pelatihan->deskripsi, 60) }}</p>
                        @endif
                        <div class="text-[var(--fs-caption)] text-gray-500 mt-[var(--sp-2)] space-y-1">
                            <div>{{ $pelatihan->tanggal_mulai->format('d M Y') }} &ndash; {{ $pelatihan->tanggal_selesai->format('d M Y') }}</div>
                            <div>{{ $pelatihan->lokasi }} &middot; {{ $pelatihan->metode->label() }}</div>
                            <div>Kuota: {{ $pelatihan->jumlahPendaftar() }} / {{ $pelatihan->kuota }}</div>
                        </div>

                        <div class="mt-[var(--sp-3)] flex items-center justify-between">
                            @if ($pelatihan->isFull())
                                <span class="inline-flex items-center px-[var(--sp-3)] py-1 rounded-full text-xs font-semibold text-white bg-[var(--color-danger)]">Kuota Penuh</span>
                            @else
                                <span class="inline-flex items-center px-[var(--sp-3)] py-1 rounded-full text-xs font-semibold text-white bg-[var(--color-success)]">Tersedia</span>
                            @endif

                            @if (! $pelatihan->isFull())
                                <a href="{{ route('pelatihan.detail', $pelatihan) }}">
                                    <x-primary-button type="button">Lihat Detail</x-primary-button>
                                </a>
                            @endif
                        </div>
                    </x-card>
                @endforeach
            </div>

            <div class="mt-[var(--sp-6)]">
                {{ $pelatihans->links() }}
            </div>
        @endif
    </div>
    </div>
    </div>
    </div>
</x-app-layout>