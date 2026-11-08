<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $asrama->nama }}
            </h2>
            <a href="{{ route('panitia.asrama.index') }}" class="text-sm font-medium hover:underline" style="color: var(--color-primary);">
                ← Kembali
            </a>
        </div>
    </x-slot>

    <div class="py-12" style="background-color: var(--color-bg);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Session Error Message -->
            @if (session('error'))
                <div class="mb-6 p-4 rounded-lg" style="border-left: 4px solid var(--color-primary); background-color: var(--color-surface); color: var(--color-text);">
                    <p class="font-medium">{{ session('error') }}</p>
                </div>
            @endif

            <!-- Session Success Message -->
            @if (session('success'))
                <div class="mb-6 p-4 rounded-lg" style="border-left: 4px solid var(--color-success); background-color: var(--color-surface); color: var(--color-text);">
                    <p class="font-medium">{{ session('success') }}</p>
                </div>
            @endif

            <!-- Validation Errors -->
            @if ($errors->any())
                <div class="mb-6 p-4 rounded-lg" style="border-left: 4px solid var(--color-danger); background-color: var(--color-surface); color: var(--color-text);">
                    <ul class="text-sm space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>• {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Tambah Kamar Section -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-semibold mb-4" style="color: var(--color-text);">Tambah Kamar</h3>

                    <form
                        method="POST"
                        action="{{ route('panitia.kamar.store', $asrama) }}"
                        x-data="{}"
                        @submit.prevent="
                            $el.submit();
                        "
                        class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end"
                    >
                        @csrf

                        <div>
                            <label for="nomor_kamar" class="block text-sm font-medium mb-1" style="color: var(--color-text);">
                                Nomor Kamar <span style="color: var(--color-danger);">*</span>
                            </label>
                            <input
                                type="text"
                                id="nomor_kamar"
                                name="nomor_kamar"
                                value="{{ old('nomor_kamar') }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-offset-2"
                                style="--tw-ring-color: var(--color-primary);"
                                placeholder="Cth: A-001"
                            />
                            @error('nomor_kamar')
                                <p class="text-sm mt-1" style="color: var(--color-danger);">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="kapasitas" class="block text-sm font-medium mb-1" style="color: var(--color-text);">
                                Kapasitas <span style="color: var(--color-danger);">*</span>
                            </label>
                            <input
                                type="number"
                                id="kapasitas"
                                name="kapasitas"
                                value="{{ old('kapasitas') }}"
                                min="1"
                                max="10"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-offset-2"
                                style="--tw-ring-color: var(--color-primary);"
                                placeholder="1-10"
                            />
                            @error('kapasitas')
                                <p class="text-sm mt-1" style="color: var(--color-danger);">{{ $message }}</p>
                            @enderror
                        </div>

                        <button
                            type="submit"
                            class="px-4 py-2 text-sm font-medium text-white rounded-md hover:opacity-90 transition"
                            style="background-color: var(--color-primary);"
                        >
                            Tambah Kamar
                        </button>
                    </form>
                </div>
            </div>

            <!-- Daftar Kamar -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-semibold mb-4" style="color: var(--color-text);">Daftar Kamar</h3>

                    @if (empty($kamars))
                        <div class="text-center py-8">
                            <p class="text-gray-500">Belum ada kamar.</p>
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach ($kamars as $kamar)
                                <div class="p-4 border border-gray-200 rounded-lg">
                                    <div class="flex justify-between items-start mb-4">
                                        <div>
                                            <h4 class="text-base font-semibold" style="color: var(--color-text);">
                                                {{ $kamar['nomor_kamar'] }}
                                            </h4>
                                            <p class="text-sm text-gray-500">
                                                Kapasitas: {{ $kamar['jumlah_penghuni'] }}/{{ $kamar['kapasitas'] }} orang
                                            </p>
                                        </div>
                                        <div class="flex flex-col gap-2">
                                            <span class="px-3 py-1 text-xs font-medium rounded-full text-white @if ($kamar['status'] === 'Penuh') text-white" style="background-color: var(--color-danger); @else text-gray-700" style="background-color: var(--color-accent); @endif">
                                                {{ $kamar['status'] }}
                                            </span>
                                            <button
                                                type="button"
                                                class="px-2 py-1 text-xs font-medium text-gray-700 border border-gray-300 rounded hover:bg-gray-50"
                                                @click="$dispatch('open-ubah-kapasitas-modal', { kamarId: {{ $kamar['id'] }}, nomorKamar: '{{ $kamar['nomor_kamar'] }}', kapasitas: {{ $kamar['kapasitas'] }} })"
                                            >
                                                Ubah Kapasitas
                                            </button>
                                        </div>
                                    </div>

                                    @if (empty($kamar['penghuni']))
                                        <p class="text-sm text-gray-500 italic">Belum ada penghuni.</p>
                                    @else
                                        <div class="mt-4 space-y-2">
                                            <p class="text-xs font-semibold text-gray-600 uppercase">Penghuni:</p>
                                            <div class="overflow-x-auto">
                                                <table class="min-w-full text-sm">
                                                    <thead class="bg-gray-50 border-b border-gray-200">
                                                        <tr>
                                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Nama Peserta</th>
                                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Pelatihan</th>
                                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Check-in</th>
                                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Check-out</th>
                                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Status</th>
                                                            <th class="px-3 py-2 text-center text-xs font-medium text-gray-600">Aksi</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-200">
                                                        @foreach ($kamar['penghuni'] as $penghuni)
                                                            <tr>
                                                                <td class="px-3 py-2">{{ $penghuni['nama_peserta'] }}</td>
                                                                <td class="px-3 py-2">{{ $penghuni['pelatihan'] }}</td>
                                                                <td class="px-3 py-2">{{ $penghuni['check_in'] }}</td>
                                                                <td class="px-3 py-2">{{ $penghuni['check_out'] }}</td>
                                                                <td class="px-3 py-2">
                                                                    @if ($penghuni['status_verifikasi'] === 'diverifikasi')
                                                                        <span class="inline-flex px-2 py-1 text-xs rounded-full text-white" style="background-color: var(--color-primary);">
                                                                            Diverifikasi
                                                                        </span>
                                                                    @elseif ($penghuni['status_verifikasi'] === 'pending')
                                                                        <span class="inline-flex px-2 py-1 text-xs rounded-full" style="background-color: var(--color-accent); color: var(--color-text);">
                                                                            Pending
                                                                        </span>
                                                                    @else
                                                                        <span class="inline-flex px-2 py-1 text-xs rounded-full text-white" style="background-color: var(--color-danger);">
                                                                            Ditolak
                                                                        </span>
                                                                    @endif
                                                                </td>
                                                                <td class="px-3 py-2 text-center">
                                                                    <button
                                                                        type="button"
                                                                        class="px-2 py-1 text-xs font-medium text-gray-700 hover:underline"
                                                                        @click="$dispatch('open-pindah-kamar-modal', { pendaftaranId: {{ $penghuni['id'] }}, namaPeserta: '{{ $penghuni['nama_peserta'] }}', kamarSkrg: '{{ $kamar['nomor_kamar'] }}' })"
                                                                    >
                                                                        Pindah
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ubah Kapasitas -->
    <div
        x-data="{ open: false, kamarId: null, nomorKamar: '', kapasitas: '' }"
        @open-ubah-kapasitas-modal.window="
            open = true;
            kamarId = $event.detail.kamarId;
            nomorKamar = $event.detail.nomorKamar;
            kapasitas = $event.detail.kapasitas;
        "
        x-show="open"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        style="display: none;"
    >
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full mx-4" @click.stop>
            <h3 class="text-lg font-semibold mb-4" style="color: var(--color-text);">Ubah Kapasitas Kamar</h3>
            <p class="text-sm text-gray-600 mb-4">Kamar: <strong x-text="nomorKamar"></strong></p>

            <form :action="`/panitia/kamar/${kamarId}`" method="POST" @submit.prevent="$el.submit()">
                @csrf
                @method('PATCH')

                <div class="mb-4">
                    <label for="kapasitas_ubah" class="block text-sm font-medium mb-1" style="color: var(--color-text);">
                        Kapasitas Baru <span style="color: var(--color-danger);">*</span>
                    </label>
                    <input
                        type="number"
                        id="kapasitas_ubah"
                        name="kapasitas"
                        x-model.number="kapasitas"
                        min="1"
                        max="10"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-offset-2"
                        style="--tw-ring-color: var(--color-primary);"
                    />
                </div>

                <div class="flex gap-2 justify-end">
                    <button
                        type="button"
                        @click="open = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-md hover:bg-gray-50"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="px-4 py-2 text-sm font-medium text-white rounded-md hover:opacity-90"
                        style="background-color: var(--color-primary);"
                    >
                        Ubah
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Pindah Kamar -->
    <div
        x-data="{ open: false, pendaftaranId: null, namaPeserta: '', kamarSkrg: '', kamarTujuanId: '' }"
        @open-pindah-kamar-modal.window="
            open = true;
            pendaftaranId = $event.detail.pendaftaranId;
            namaPeserta = $event.detail.namaPeserta;
            kamarSkrg = $event.detail.kamarSkrg;
        "
        x-show="open"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        style="display: none;"
    >
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full mx-4" @click.stop>
            <h3 class="text-lg font-semibold mb-4" style="color: var(--color-text);">Pindah Kamar</h3>
            <p class="text-sm text-gray-600 mb-1">Peserta: <strong x-text="namaPeserta"></strong></p>
            <p class="text-sm text-gray-600 mb-4">Kamar Saat Ini: <strong x-text="kamarSkrg"></strong></p>

            <form :action="`/panitia/pendaftaran/${pendaftaranId}/kamar`" method="POST" @submit.prevent="$el.submit()">
                @csrf
                @method('PATCH')

                <div class="mb-4">
                    <label for="kamar_tujuan" class="block text-sm font-medium mb-1" style="color: var(--color-text);">
                        Kamar Tujuan <span style="color: var(--color-danger);">*</span>
                    </label>
                    <select
                        id="kamar_tujuan"
                        name="kamar_id"
                        x-model="kamarTujuanId"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-offset-2"
                        style="--tw-ring-color: var(--color-primary);"
                    >
                        <option value="">-- Pilih Kamar --</option>
                        @foreach ($kamars as $kamar)
                            <option value="{{ $kamar['id'] }}">
                                {{ $kamar['nomor_kamar'] }} ({{ $kamar['jumlah_penghuni'] }}/{{ $kamar['kapasitas'] }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-2 justify-end">
                    <button
                        type="button"
                        @click="open = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-md hover:bg-gray-50"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="px-4 py-2 text-sm font-medium text-white rounded-md hover:opacity-90"
                        style="background-color: var(--color-primary);"
                    >
                        Pindah
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

