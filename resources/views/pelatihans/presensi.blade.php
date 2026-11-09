<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Presensi: {{ $pelatihan->nama }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="mb-6">
                        <h1 class="text-2xl font-bold text-gray-900">Presensi Peserta</h1>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ $pelatihan->nama }} &middot;
                            {{ $pelatihan->tanggal_mulai->format('d M Y') }} - {{ $pelatihan->tanggal_selesai->format('d M Y') }}
                            &middot; {{ $pelatihan->lokasi }}
                        </p>
                        <p class="mt-1 text-sm text-gray-500">
                            Masukkan kode presensi dari kartu peserta (hasil scan QR code).
                        </p>
                        <div class="mt-4">
                            <a href="{{ route('pelatihan.pendaftar', $pelatihan) }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
                                <svg class="mr-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                                </svg>
                                Kembali ke Pendaftar
                            </a>
                        </div>
                    </div>

                    @if (session('success'))
                        <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-700 flex items-center">
                            <svg class="h-5 w-5 mr-2 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-700 flex items-center">
                            <svg class="h-5 w-5 mr-2 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            {{ session('error') }}
                        </div>
                    @endif

                    @if (session('warning'))
                        <div class="mb-4 rounded-md bg-yellow-50 p-4 text-sm text-yellow-700 flex items-center">
                            <svg class="h-5 w-5 mr-2 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            {{ session('warning') }}
                        </div>
                    @endif

                    <div x-data="scanQrData()" x-init="initScanData()">
                        <!-- Kartu Kehadiran -->
                        <div class="mb-8 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <h3 class="font-semibold text-blue-900 mb-3">Kehadiran</h3>
                            <p class="text-lg font-bold text-blue-900 mb-3">
                                <span x-text="currentHadirCount"></span> dari <span x-text="totalCount"></span> peserta hadir
                            </p>
                            <div class="space-y-2">
                                <template x-for="item in hadirList" :key="item.nama">
                                    <div class="flex items-center justify-between text-sm bg-white p-2 rounded border border-blue-100">
                                        <span class="font-medium text-gray-900" x-text="item.nama"></span>
                                        <span class="text-gray-500" x-text="item.hadir_at"></span>
                                    </div>
                                </template>
                                <div x-show="hadirList.length === 0" class="text-sm text-gray-500 italic p-2">
                                    Belum ada yang hadir.
                                </div>
                            </div>
                        </div>

                        <!-- Bagian Scan QR -->
                        <div class="mb-8">
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-lg font-semibold text-gray-900">Scan QR Code</h2>
                            </div>

                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                <div id="qr_scanner_container" style="display: none; height: 300px;"></div>

                                <div x-show="!scanActive && !cameraError && !scanResult" class="p-4 bg-gray-50 text-center">
                                    <button type="button"
                                        @click="startScan()"
                                        class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2"
                                        style="background-color: var(--color-primary);">
                                        <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Mulai Scan
                                    </button>
                                </div>

                                <div x-show="scanActive && !scanResult" class="p-4 bg-gray-50">
                                    <button type="button"
                                        @click="stopScan()"
                                        class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2"
                                        style="background-color: var(--color-danger);">
                                        <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        Berhenti
                                    </button>
                                </div>

                                <div x-show="cameraError" class="p-4 bg-red-50 border-t border-red-100">
                                    <p class="text-sm text-red-700 font-medium mb-2">Kamera tidak dapat diakses</p>
                                    <p class="text-xs text-red-600 mb-3" x-text="cameraError"></p>
                                    <p class="text-xs text-red-600">Gunakan form input manual di bawah untuk melanjutkan presensi.</p>
                                </div>

                                <!-- Hasil Scan (Tetap Tampil) -->
                                <div x-show="scanResult" class="p-4 border-t" :class="scanResult?.type === 'success' ? 'bg-green-50 border-green-100' : (scanResult?.type === 'warning' ? 'bg-yellow-50 border-yellow-100' : 'bg-red-50 border-red-100')">
                                    <div class="flex flex-col gap-3">
                                        <div>
                                            <p class="text-lg font-bold" :class="scanResult?.type === 'success' ? 'text-green-800' : (scanResult?.type === 'warning' ? 'text-yellow-800' : 'text-red-800')" x-text="scanResult?.pesan"></p>
                                            <p x-show="scanResult?.nama" class="text-sm mt-1" :class="scanResult?.type === 'success' ? 'text-green-700' : (scanResult?.type === 'warning' ? 'text-yellow-700' : 'text-red-700')" x-text="'Peserta: ' + scanResult?.nama"></p>
                                        </div>
                                        <button type="button"
                                            @click="continueScan()"
                                            class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2"
                                            style="background-color: var(--color-primary);">
                                            Scan peserta berikutnya
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Manual -->
                        <form method="POST" action="{{ route('pelatihan.presensi.proses', $pelatihan) }}" class="space-y-4">
                            @csrf
                            <div>
                                <label for="kode_presensi" class="block text-sm font-medium text-gray-700">Kode Presensi (Manual)</label>
                                <input type="text" name="kode_presensi" id="kode_presensi"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[var(--color-primary)] focus:ring-[var(--color-primary)] sm:text-sm border px-3 py-2 font-mono uppercase"
                                    placeholder="Masukkan kode dari QR/Scanner"
                                    autofocus>
                                @error('kode_presensi')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2" style="background-color: var(--color-primary);">
                                <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Tandai Hadir
                            </button>
                        </form>

                        <div class="mt-6 border-t border-gray-200 pt-4">
                            <p class="text-xs text-gray-400">
                                Tips: Gunakan "Mulai Scan" untuk scan langsung dari kamera, atau masukkan kode manual di form di atas.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function scanQrData() {
            return {
                scanActive: false,
                scanner: null,
                scanResult: null,
                cameraError: null,
                lastScannedCode: null,
                lastScannedTime: null,
                currentHadirCount: {{ $hadir_count }},
                totalCount: {{ $total_count }},
                hadirList: {{ json_encode($hadir_list) }},

                initScanData() {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    if (!csrfToken) {
                        console.error('CSRF token not found');
                    }
                },

                async startScan() {
                    try {
                        if (typeof window.Html5Qrcode === 'undefined') {
                            this.cameraError = 'Library pemindai QR sedang dimuat. Coba lagi dalam beberapa detik.';
                            this.scanActive = false;
                            console.error('Html5Qrcode library not loaded');
                            return;
                        }

                        this.cameraError = null;
                        this.scanResult = null;
                        this.scanActive = true;

                        document.getElementById('qr_scanner_container').style.display = 'block';

                        this.scanner = new window.Html5Qrcode('qr_scanner_container');

                        await this.scanner.start(
                            { facingMode: 'environment' },
                            { fps: 10, qrbox: { width: 250, height: 250 } },
                            (decodedText) => this.onQrSuccess(decodedText),
                            (error) => this.onQrError(error)
                        );
                    } catch (error) {
                        this.cameraError = 'Tidak dapat mengakses kamera. Pastikan browser mendukung akses kamera dan izin sudah diberikan.';
                        this.scanActive = false;
                        console.error('Scan init error:', error);
                    }
                },

                async stopScan() {
                    if (this.scanner) {
                        try {
                            await this.scanner.stop();
                            this.scanner.clear();
                        } catch (error) {
                            console.error('Error stopping scanner:', error);
                        }
                        document.getElementById('qr_scanner_container').style.display = 'none';
                    }
                    this.scanActive = false;
                    this.scanResult = null;
                },

                continueScan() {
                    this.scanResult = null;
                    if (this.scanner) {
                        this.scanner.resume();
                    }
                },

                async onQrSuccess(decodedText) {
                    const now = Date.now();

                    if (this.lastScannedCode === decodedText && this.lastScannedTime && (now - this.lastScannedTime) < 3000) {
                        return;
                    }

                    this.lastScannedCode = decodedText;
                    this.lastScannedTime = now;

                    const kode = decodedText.trim().toUpperCase();
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                    try {
                        const response = await fetch('{{ route("pelatihan.presensi.proses", $pelatihan) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ kode_presensi: kode }),
                        });

                        const data = await response.json();

                        if (response.ok && data.status === 'sukses') {
                            this.scanResult = {
                                type: 'success',
                                pesan: data.pesan || 'Presensi berhasil!',
                                nama: data.nama,
                            };
                            // Update hitungan dan daftar
                            this.currentHadirCount = data.hadir_count;
                            this.hadirList.unshift({
                                nama: data.nama,
                                hadir_at: data.hadir_at,
                            });
                            // Jaga hanya 50 terbaru
                            if (this.hadirList.length > 50) {
                                this.hadirList.pop();
                            }
                        } else if (response.ok && data.status === 'sudah_hadir') {
                            this.scanResult = {
                                type: 'warning',
                                pesan: data.pesan || 'Peserta sudah hadir sebelumnya',
                                nama: data.nama,
                            };
                        } else {
                            this.scanResult = {
                                type: 'error',
                                pesan: data.pesan || 'Gagal mencatat presensi',
                                nama: null,
                            };
                        }

                        // Pause scanner
                        if (this.scanner) {
                            this.scanner.pause();
                        }
                    } catch (error) {
                        this.scanResult = {
                            type: 'error',
                            pesan: 'Error jaringan atau server',
                            nama: null,
                        };
                        console.error('Scan submit error:', error);
                    }
                },

                onQrError(error) {
                    if (!error.toString().includes('NotFoundException')) {
                        console.debug('QR scan error:', error.toString());
                    }
                },
            };
        }
    </script>

    @push('scripts')
        @vite('resources/js/presensi-scan.js')
    @endpush
</x-app-layout>
