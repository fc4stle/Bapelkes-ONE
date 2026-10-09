<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-[var(--color-text)] leading-tight">
            Detail Pelatihan
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-[var(--color-surface)] shadow rounded-[var(--radius-card)]">
                <div class="px-4 py-5 sm:p-6">

                    <div class="flex items-start justify-between">
                        <h1 class="text-[var(--fs-h1)] font-bold text-[var(--color-text)]">{{ $pelatihan->nama }}</h1>
                        @if ($pelatihan->isFull())
                            <x-status-badge status="rejected" />
                        @endif
                    </div>

                    @if ($pelatihan->deskripsi)
                        <p class="mt-[var(--sp-3)] text-[var(--fs-body)] text-gray-600">{{ $pelatihan->deskripsi }}</p>
                    @endif

                    @if ($pelatihan->persyaratan)
                        <div class="mt-[var(--sp-6)]">
                            <h3 class="text-[var(--fs-body)] font-semibold text-[var(--color-text)]">Persyaratan</h3>
                            <p class="mt-[var(--sp-2)] text-[var(--fs-body)] text-gray-600">{{ $pelatihan->persyaratan }}</p>
                        </div>
                    @endif

                    <dl class="mt-[var(--sp-6)] grid grid-cols-1 sm:grid-cols-2 gap-[var(--sp-4)] text-[var(--fs-body)]">
                        <div>
                            <dt class="text-[var(--fs-caption)] text-gray-500">Tanggal</dt>
                            <dd class="text-[var(--color-text)]">{{ $pelatihan->tanggal_mulai->format('d M Y') }} &ndash; {{ $pelatihan->tanggal_selesai->format('d M Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-[var(--fs-caption)] text-gray-500">Lokasi</dt>
                            <dd class="text-[var(--color-text)]">{{ $pelatihan->lokasi }}</dd>
                        </div>
                        <div>
                            <dt class="text-[var(--fs-caption)] text-gray-500">Metode</dt>
                            <dd class="text-[var(--color-text)]">{{ $pelatihan->metode->label() }}</dd>
                        </div>
                        <div>
                            <dt class="text-[var(--fs-caption)] text-gray-500">Kuota</dt>
                            <dd class="text-[var(--color-text)]">{{ $pelatihan->jumlahPendaftar() }} / {{ $pelatihan->kuota }}</dd>
                        </div>
                    </dl>

                    <div class="mt-[var(--sp-8)] flex items-center gap-[var(--sp-3)]">
                        <a href="{{ route('pelatihan.katalog') }}">
                            <x-secondary-button type="button">Kembali</x-secondary-button>
                        </a>

                        @if ($sudahDaftar)
                            <span class="text-[var(--fs-caption)] text-gray-500">Anda sudah terdaftar pada pelatihan ini.</span>
                        @elseif ($pelatihan->isFull())
                            <x-primary-button type="button" disabled>Kuota Penuh</x-primary-button>
                        @else
                            <a href="{{ route('pelatihan.daftar', $pelatihan) }}">
                                <x-primary-button type="button">Ajukan Pendaftaran</x-primary-button>
                            </a>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>