<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-[var(--color-text)] leading-tight">
            Detail Pelatihan
        </h2>
    </x-slot>

    <div class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="bg-white shadow rounded-lg">
    <div class="px-4 py-5 sm:p-6">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-[var(--color-text)]">{{ $pelatihan->nama }}</h1>
                <p class="mt-1 text-sm text-[var(--color-muted)]">Detail pelatihan</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('pelatihans.edit', $pelatihan) }}" class="inline-flex items-center px-4 py-2 border border-[var(--color-line)] shadow-sm text-sm font-medium rounded-md text-[var(--color-text)] bg-white hover:bg-[var(--color-bg)] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--color-primary)]">
                    Edit
                </a>
                <a href="{{ route('pelatihans.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md hover:opacity-90" style="color: white; background-color: var(--color-primary); focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--color-primary)]">
                    Kembali
                </a>
            </div>
        </div>

        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-[var(--color-muted)]">Deskripsi</dt>
                <dd class="mt-1 text-sm text-[var(--color-text)]">{{ $pelatihan->deskripsi ?? '-' }}</dd>
            </div>

            <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-[var(--color-muted)]">Persyaratan</dt>
                <dd class="mt-1 text-sm text-[var(--color-text)]">{{ $pelatihan->persyaratan ?? '-' }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-[var(--color-muted)]">Tanggal Mulai</dt>
                <dd class="mt-1 text-sm text-[var(--color-text)]">{{ $pelatihan->tanggal_mulai->format('d F Y') }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-[var(--color-muted)]">Tanggal Selesai</dt>
                <dd class="mt-1 text-sm text-[var(--color-text)]">{{ $pelatihan->tanggal_selesai->format('d F Y') }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-[var(--color-muted)]">Lokasi</dt>
                <dd class="mt-1 text-sm text-[var(--color-text)]">{{ $pelatihan->lokasi }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-[var(--color-muted)]">Kuota</dt>
                <dd class="mt-1 text-sm text-[var(--color-text)]">{{ $pelatihan->kuota }} peserta</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-[var(--color-muted)]">Metode</dt>
                <dd class="mt-1 text-sm text-[var(--color-text)]">{{ $pelatihan->metode->label() }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-[var(--color-muted)]">Status</dt>
                <dd class="mt-1 text-sm text-[var(--color-text)]">
                    @php
                        $statusColors = [
                            \App\Enums\StatusPelatihan::Draft->value => 'bg-[var(--color-line)] text-[var(--color-text)]',
                            \App\Enums\StatusPelatihan::Dibuka->value => 'bg-[color-mix(in_srgb,var(--color-success)_15%,white)] text-[var(--color-success)]',
                            \App\Enums\StatusPelatihan::Ditutup->value => 'bg-[color-mix(in_srgb,var(--color-warning)_15%,white)] text-[var(--color-warning)]',
                            \App\Enums\StatusPelatihan::Selesai->value => 'bg-[var(--color-primary-soft)] text-[var(--color-primary)]',
                        ];
                    @endphp
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$pelatihan->status->value] ?? 'bg-[var(--color-line)] text-[var(--color-text)]' }}">
                        {{ $pelatihan->status->label() }}
                    </span>
                </dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-[var(--color-muted)]">Dibuat</dt>
                <dd class="mt-1 text-sm text-[var(--color-text)]">{{ $pelatihan->created_at->format('d F Y H:i') }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-[var(--color-muted)]">Terakhir Diperbarui</dt>
                <dd class="mt-1 text-sm text-[var(--color-text)]">{{ $pelatihan->updated_at->format('d F Y H:i') }}</dd>
            </div>
        </dl>
    </div>
    </div>
    </div>
    </div>
</x-app-layout>
