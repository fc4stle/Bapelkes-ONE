<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Kartu Peserta Digital
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            @if (session('error'))
                <div class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            @if ($pendaftaran->isPending())
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6 text-center">
                        <svg class="mx-auto h-16 w-16 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">Menunggu Verifikasi</h3>
                        <p class="mt-2 text-sm text-gray-500">
                            Pendaftaran Anda untuk pelatihan <strong>{{ $pendaftaran->pelatihan->nama }}</strong> sedang menunggu verifikasi dari panitia.
                            Kartu peserta akan tersedia setelah pendaftaran diverifikasi.
                        </p>
                        <div class="mt-6">
                            <a href="{{ route('pendaftarans.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Kembali ke Daftar Pendaftaran
                            </a>
                        </div>
                    </div>
                </div>
            @elseif ($pendaftaran->isDitolak())
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6 text-center">
                        <svg class="mx-auto h-16 w-16 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">Pendaftaran Ditolak</h3>
                        <p class="mt-2 text-sm text-gray-500">
                            Pendaftaran Anda untuk pelatihan <strong>{{ $pendaftaran->pelatihan->nama }}</strong> telah ditolak.
                        </p>
                        @if ($pendaftaran->alasan_penolakan)
                            <div class="mt-4 rounded-md bg-red-50 p-4 text-sm text-red-700">
                                <strong>Alasan:</strong> {{ $pendaftaran->alasan_penolakan }}
                            </div>
                        @endif
                        <div class="mt-6">
                            <a href="{{ route('pendaftarans.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Kembali ke Daftar Pendaftaran
                            </a>
                        </div>
                    </div>
                </div>
            @elseif ($pendaftaran->isDiverifikasi())
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="bg-indigo-600 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-white">Kartu Peserta Digital</h3>
                                <p class="text-indigo-100 text-sm">Bapelkes Dinkes DIY</p>
                            </div>
                            <svg class="h-8 w-8 text-indigo-200" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M3 5a2 2 0 012-2h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5zm10.7 12.7L9 13l-2.7 2.7-1.4-1.4L9 10.2l6.1 6.1-1.4 1.4z"/>
                            </svg>
                        </div>
                    </div>

                    <div class="px-6 py-6">
                        <div class="flex items-start space-x-6">
                            <div class="flex-shrink-0">
                                <div class="h-20 w-20 rounded-full bg-indigo-100 flex items-center justify-center">
                                    <span class="text-2xl font-bold text-indigo-600">
                                        {{ strtoupper(substr($pendaftaran->data_diri['nama'] ?? 'P', 0, 1)) }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-xl font-bold text-gray-900">{{ $pendaftaran->data_diri['nama'] ?? '-' }}</h4>
                                <p class="text-sm text-gray-500">NIK: {{ $pendaftaran->data_diri['nik'] ?? '-' }}</p>
                                <p class="text-sm text-gray-500">{{ $pendaftaran->data_diri['profesi'] ?? '-' }} &middot; {{ $pendaftaran->data_diri['instansi'] ?? '-' }}</p>
                            </div>
                        </div>

                        <div class="mt-6 border-t border-gray-200 pt-6">
                            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Pelatihan</dt>
                                    <dd class="mt-1 text-sm font-medium text-gray-900">{{ $pendaftaran->pelatihan->nama }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Pelatihan</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        {{ $pendaftaran->pelatihan->tanggal_mulai->format('d M Y') }} - {{ $pendaftaran->pelatihan->tanggal_selesai->format('d M Y') }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Lokasi</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $pendaftaran->pelatihan->lokasi }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Metode</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $pendaftaran->pelatihan->metode->label() }}</dd>
                                </div>
                                @if ($pendaftaran->butuh_asrama && $pendaftaran->kamar)
                                    <div class="sm:col-span-2">
                                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Asrama</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ $pendaftaran->kamar->asrama->nama }} - Kamar {{ $pendaftaran->kamar->nomor_kamar }}
                                            <br>
                                            <span class="text-gray-500">Check-in: {{ $pendaftaran->check_in->format('d M Y') }} | Check-out: {{ $pendaftaran->check_out->format('d M Y') }}</span>
                                        </dd>
                                    </div>
                                @elseif ($pendaftaran->butuh_asrama && !$pendaftaran->kamar)
                                    <div class="sm:col-span-2">
                                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Asrama</dt>
                                        <dd class="mt-1 text-sm text-yellow-600">Asrama penuh, tidak ada kamar tersedia</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>

                        <div class="mt-6 border-t border-gray-200 pt-6">
                            <div class="flex flex-col items-center">
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-3">QR Code Presensi</p>
                                <div class="bg-white p-4 rounded-lg border-2 border-gray-200 inline-block">
                                    {!! $qrCode !!}
                                </div>
                                <p class="mt-3 text-xs text-gray-500 text-center">
                                    Kode: <span class="font-mono font-medium">{{ $pendaftaran->kode_presensi }}</span>
                                </p>
                                <p class="mt-1 text-xs text-gray-400 text-center">
                                    Tunjukkan QR code ini saat presensi di lokasi pelatihan
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <p class="text-xs text-gray-500">
                                Diverifikasi oleh: {{ $pendaftaran->verifier->name ?? 'Panitia' }} pada {{ $pendaftaran->verified_at->format('d M Y H:i') }}
                            </p>
                            <button onclick="window.print()" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Cetak
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
