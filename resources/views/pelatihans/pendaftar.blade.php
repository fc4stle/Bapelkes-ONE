<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Pendaftar: {{ $pelatihan->nama }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="mb-6">
                        <h1 class="text-2xl font-bold text-gray-900">{{ $pelatihan->nama }}</h1>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ $pelatihan->tanggal_mulai->format('d M Y') }} - {{ $pelatihan->tanggal_selesai->format('d M Y') }}
                            &middot; {{ $pelatihan->lokasi }}
                            &middot; {{ $pelatihan->metode->label() }}
                        </p>
                        <p class="mt-1 text-sm text-gray-500">
                            Kuota: {{ $pelatihan->jumlahPendaftar() }} / {{ $pelatihan->kuota }}
                        </p>
                    </div>

                    @if (session('success'))
                        <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-700">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($pendaftarans->isEmpty())
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada pendaftar</h3>
                            <p class="mt-1 text-sm text-gray-500">Belum ada peserta yang mendaftar pada pelatihan ini.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Peserta</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kontak</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Profesi / Instansi</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($pendaftarans as $pendaftaran)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $pendaftaran->data_diri['nama'] ?? '-' }}</div>
                                                <div class="text-sm text-gray-500">NIK: {{ $pendaftaran->data_diri['nik'] ?? '-' }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $pendaftaran->data_diri['kontak'] ?? '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <div>{{ $pendaftaran->data_diri['profesi'] ?? '-' }}</div>
                                                <div class="text-gray-400">{{ $pendaftaran->data_diri['instansi'] ?? '-' }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if ($pendaftaran->isDiverifikasi())
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Terverifikasi</span>
                                                @elseif ($pendaftaran->isDitolak())
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Ditolak</span>
                                                    @if ($pendaftaran->alasan_penolakan)
                                                        <p class="mt-1 text-xs text-gray-500 max-w-xs">{{ Str::limit($pendaftaran->alasan_penolakan, 60) }}</p>
                                                    @endif
                                                @else
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Belum Diverifikasi</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                @if ($pendaftaran->isPending())
                                                    <form action="{{ route('pendaftarans.verifikasi', $pendaftaran) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                                            Verifikasi
                                                        </button>
                                                    </form>

                                                    <button type="button" onclick="document.getElementById('tolak-modal-{{ $pendaftaran->id }}').showModal()" class="ml-2 inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                                        Tolak
                                                    </button>

                                                    <dialog id="tolak-modal-{{ $pendaftaran->id }}" class="rounded-lg shadow-xl p-0 backdrop:bg-gray-500/50">
                                                        <form method="POST" action="{{ route('pendaftarans.tolak', $pendaftaran) }}" class="p-6">
                                                            @csrf
                                                            @method('PATCH')
                                                            <h3 class="text-lg font-medium text-gray-900 mb-4">Tolak Pendaftaran</h3>
                                                            <p class="text-sm text-gray-500 mb-4">Anda akan menolak pendaftaran <strong>{{ $pendaftaran->data_diri['nama'] ?? 'peserta' }}</strong>.</p>
                                                            <div class="mb-4">
                                                                <label for="alasan_penolakan_{{ $pendaftaran->id }}" class="block text-sm font-medium text-gray-700">Alasan Penolakan <span class="text-red-500">*</span></label>
                                                                <textarea name="alasan_penolakan" id="alasan_penolakan_{{ $pendaftaran->id }}" rows="3" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm border px-3 py-2" placeholder="Tuliskan alasan penolakan..."></textarea>
                                                                @error('alasan_penolakan')
                                                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                                                @enderror
                                                            </div>
                                                            <div class="flex justify-end space-x-3">
                                                                <button type="button" onclick="this.closest('dialog').close()" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Batal</button>
                                                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700">Tolak Pendaftaran</button>
                                                            </div>
                                                        </form>
                                                    </dialog>
                                                @else
                                                    <span class="text-sm text-gray-400">
                                                        {{ $pendaftaran->verified_at ? $pendaftaran->verified_at->format('d M Y H:i') : '' }}
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $pendaftarans->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
