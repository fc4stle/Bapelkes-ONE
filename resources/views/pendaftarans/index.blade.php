<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-[var(--color-text)] leading-tight">
            Pendaftaran Saya
        </h2>
    </x-slot>

    <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-[var(--color-surface)] shadow rounded-[var(--radius-card)]">
    <div class="px-4 py-5 sm:p-6">
        <div class="flex justify-between items-center mb-[var(--sp-6)]">
            <h1 class="text-[var(--fs-h1)] font-bold text-[var(--color-text)]">Pendaftaran Saya</h1>
            <a href="{{ route('pelatihan.katalog') }}">
                <x-primary-button type="button">Daftar Pelatihan</x-primary-button>
            </a>
        </div>

        @if (session('success'))
            <div class="mb-[var(--sp-4)] rounded-[var(--radius-btn)] bg-green-50 p-[var(--sp-4)] text-sm text-[var(--color-success)]">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-[var(--sp-4)] rounded-[var(--radius-btn)] bg-red-50 p-[var(--sp-4)] text-sm text-[var(--color-danger)]">
                {{ session('error') }}
            </div>
        @endif

        @if ($pendaftarans->isEmpty())
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-[var(--color-text)]">Belum ada pendaftaran</h3>
                <p class="mt-1 text-sm text-gray-500">Anda belum mendaftar pada pelatihan manapun.</p>
                <div class="mt-[var(--sp-6)]">
                    <a href="{{ route('pelatihan.katalog') }}">
                        <x-primary-button type="button">Daftar Pelatihan</x-primary-button>
                    </a>
                </div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pelatihan</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($pendaftarans as $pendaftaran)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-[var(--color-text)]">{{ $pendaftaran->pelatihan->nama }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $pendaftaran->pelatihan->tanggal_mulai->format('d M Y') }} - {{ $pendaftaran->pelatihan->tanggal_selesai->format('d M Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $statusMap = [
                                            'pending' => 'pending',
                                            'diverifikasi' => 'approved',
                                            'ditolak' => 'rejected',
                                        ];
                                        $badgeStatus = $statusMap[$pendaftaran->status_verifikasi] ?? 'pending';
                                    @endphp
                                    <x-status-badge :status="$badgeStatus" />
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    @if ($pendaftaran->status_verifikasi === 'pending')
                                        <form action="{{ route('pendaftarans.destroy', $pendaftaran) }}" method="POST" class="inline" onsubmit="return confirm('Yakin ingin membatalkan pendaftaran ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-[var(--color-danger)] hover:opacity-75">Batalkan</button>
                                        </form>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    </div>
    </div>
    </div>
</x-app-layout>