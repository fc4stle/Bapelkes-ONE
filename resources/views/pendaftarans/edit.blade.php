<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-[var(--color-text)] leading-tight">
            Ubah Pendaftaran: {{ $pelatihan->nama }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-[var(--color-surface)] shadow rounded-[var(--radius-card)]">
                <div class="px-4 py-5 sm:p-6">
                    @if (session('error'))
                        <div class="mb-[var(--sp-4)] rounded-[var(--radius-btn)] bg-red-50 p-[var(--sp-4)] text-sm text-[var(--color-danger)]">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-[var(--sp-4)] rounded-[var(--radius-btn)] bg-red-50 p-[var(--sp-4)] text-sm text-[var(--color-danger)]">
                            Terdapat kesalahan pada isian form. Periksa kembali field yang ditandai di bawah.
                        </div>
                    @endif

                    @php
                        $dataDiri = $pendaftaran->data_diri ?? [];
                    @endphp

                    <form action="{{ route('pendaftarans.updateSelf', $pendaftaran) }}" method="POST" enctype="multipart/form-data" id="editForm" novalidate>
                        @csrf
                        @method('PUT')

                        <!-- Bagian 1: Data Diri -->
                        <fieldset class="mb-[var(--sp-8)] border border-gray-200 rounded-[var(--radius-card)] p-[var(--sp-4)]">
                            <legend class="text-[var(--fs-h2)] font-semibold text-[var(--color-text)] px-2">1. Data Diri</legend>

                            <div class="grid grid-cols-1 gap-[var(--sp-4)] sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <x-input-label for="nama" value="Nama Lengkap" />
                                    <x-text-input type="text" name="nama" id="nama" value="{{ old('nama', $dataDiri['nama'] ?? '') }}" required class="mt-1 block w-full @error('nama') border-[var(--color-danger)] @enderror" />
                                    <x-input-error :messages="$errors->get('nama')" class="mt-1" />
                                </div>

                                <div>
                                    <x-input-label for="nik" value="NIK" />
                                    <x-text-input type="text" name="nik" id="nik" value="{{ old('nik', $dataDiri['nik'] ?? '') }}" required maxlength="16" class="mt-1 block w-full @error('nik') border-[var(--color-danger)] @enderror" />
                                    <x-input-error :messages="$errors->get('nik')" class="mt-1" />
                                </div>

                                <div>
                                    <x-input-label for="kontak" value="Kontak (No. HP / Email)" />
                                    <x-text-input type="text" name="kontak" id="kontak" value="{{ old('kontak', $dataDiri['kontak'] ?? '') }}" required class="mt-1 block w-full @error('kontak') border-[var(--color-danger)] @enderror" />
                                    <x-input-error :messages="$errors->get('kontak')" class="mt-1" />
                                </div>

                                <div>
                                    <x-input-label for="profesi" value="Profesi" />
                                    <x-text-input type="text" name="profesi" id="profesi" value="{{ old('profesi', $dataDiri['profesi'] ?? '') }}" required class="mt-1 block w-full @error('profesi') border-[var(--color-danger)] @enderror" />
                                    <x-input-error :messages="$errors->get('profesi')" class="mt-1" />
                                </div>

                                <div>
                                    <x-input-label for="instansi" value="Instansi Asal" />
                                    <x-text-input type="text" name="instansi" id="instansi" value="{{ old('instansi', $dataDiri['instansi'] ?? '') }}" required class="mt-1 block w-full @error('instansi') border-[var(--color-danger)] @enderror" />
                                    <x-input-error :messages="$errors->get('instansi')" class="mt-1" />
                                </div>
                            </div>
                        </fieldset>

                        <!-- Bagian 2: Dokumen -->
                        <fieldset class="mb-[var(--sp-8)] border border-gray-200 rounded-[var(--radius-card)] p-[var(--sp-4)]">
                            <legend class="text-[var(--fs-h2)] font-semibold text-[var(--color-text)] px-2">2. Dokumen</legend>

                            <div class="space-y-[var(--sp-4)]">
                                <div>
                                    <x-input-label for="surat_tugas" value="Surat Tugas (opsional, biarkan kosong untuk tetap pakai berkas lama)" />
                                    <input type="file" name="surat_tugas" id="surat_tugas" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-[var(--radius-btn)] file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-[var(--color-primary)] hover:file:bg-indigo-100">
                                    <p class="mt-1 text-[var(--fs-caption)] text-gray-500">PDF, JPG, PNG (maks. 2MB)</p>
                                    <x-input-error :messages="$errors->get('surat_tugas')" class="mt-1" />
                                </div>

                                <div>
                                    <x-input-label for="dokumen_lain" value="Dokumen Persyaratan Lain (opsional, biarkan kosong untuk tetap pakai berkas lama)" />
                                    <input type="file" name="dokumen_lain" id="dokumen_lain" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-[var(--radius-btn)] file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-[var(--color-primary)] hover:file:bg-indigo-100">
                                    <p class="mt-1 text-[var(--fs-caption)] text-gray-500">PDF, JPG, PNG (maks. 2MB)</p>
                                    <x-input-error :messages="$errors->get('dokumen_lain')" class="mt-1" />
                                </div>
                            </div>
                        </fieldset>

                        <!-- Bagian 3: Asrama -->
                        <fieldset class="mb-[var(--sp-8)] border border-gray-200 rounded-[var(--radius-card)] p-[var(--sp-4)] bg-[var(--color-bg)]">
                            <legend class="text-[var(--fs-h2)] font-semibold text-[var(--color-text)] px-2">3. Asrama <span class="text-sm font-normal text-gray-500">(opsional)</span></legend>

                            <div class="mb-[var(--sp-4)]">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="butuh_asrama" id="butuh_asrama" value="1" {{ old('butuh_asrama', $pendaftaran->butuh_asrama) ? 'checked' : '' }} class="rounded border-gray-300 text-[var(--color-primary)] shadow-sm focus:ring-[var(--color-primary)]">
                                    <span class="ml-2 text-sm font-medium text-[var(--color-text)]">Saya butuh menginap</span>
                                </label>
                                @if ($pendaftaran->kamar_id)
                                    <p class="mt-1 text-[var(--fs-caption)] text-gray-500">
                                        Saat ini dialokasikan kamar {{ $pendaftaran->kamar->nomor_kamar }} di {{ $pendaftaran->kamar->asrama->nama }}.
                                        Mengubah tanggal dapat mengubah alokasi kamar.
                                    </p>
                                @endif
                            </div>

                            <div id="asrama-fields" class="hidden grid grid-cols-1 gap-[var(--sp-4)] sm:grid-cols-2">
                                <div>
                                    <x-input-label for="check_in" value="Tanggal Check-in" />
                                    <x-text-input type="date" name="check_in" id="check_in" value="{{ old('check_in', optional($pendaftaran->check_in)->format('Y-m-d')) }}" class="mt-1 block w-full" />
                                    <x-input-error :messages="$errors->get('check_in')" class="mt-1" />
                                </div>

                                <div>
                                    <x-input-label for="check_out" value="Tanggal Check-out" />
                                    <x-text-input type="date" name="check_out" id="check_out" value="{{ old('check_out', optional($pendaftaran->check_out)->format('Y-m-d')) }}" class="mt-1 block w-full" />
                                    <x-input-error :messages="$errors->get('check_out')" class="mt-1" />
                                </div>
                            </div>
                        </fieldset>

                        <!-- Bagian 4: Konfirmasi -->
                        <fieldset class="mb-[var(--sp-8)] border border-gray-200 rounded-[var(--radius-card)] p-[var(--sp-4)]">
                            <legend class="text-[var(--fs-h2)] font-semibold text-[var(--color-text)] px-2">4. Konfirmasi</legend>

                            <div class="rounded-[var(--radius-btn)] bg-indigo-50 p-[var(--sp-4)] mb-[var(--sp-4)]">
                                <h4 class="text-sm font-medium text-[var(--color-primary)] mb-[var(--sp-2)]">Ringkasan Pendaftaran</h4>
                                <dl class="text-sm text-[var(--color-primary)] space-y-1">
                                    <div><dt class="font-medium">Pelatihan</dt><dd>{{ $pelatihan->nama }}</dd></div>
                                    <div><dt class="font-medium">Tanggal Pelatihan</dt><dd>{{ $pelatihan->tanggal_mulai->format('d M Y') }} - {{ $pelatihan->tanggal_selesai->format('d M Y') }}</dd></div>
                                </dl>
                            </div>

                            <p class="text-sm text-gray-600 mb-[var(--sp-4)]">Dengan menekan tombol di bawah, Anda menyatakan bahwa data yang diisi adalah benar.</p>

                            <div class="flex justify-end gap-[var(--sp-3)]">
                                <a href="{{ route('pendaftarans.index') }}">
                                    <x-secondary-button type="button">Batal</x-secondary-button>
                                </a>
                                <x-primary-button type="submit">Simpan Perubahan</x-primary-button>
                            </div>
                        </fieldset>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkbox = document.getElementById('butuh_asrama');
            const fields = document.getElementById('asrama-fields');

            function toggleAsrama() {
                if (checkbox.checked) {
                    fields.classList.remove('hidden');
                } else {
                    fields.classList.add('hidden');
                }
            }

            checkbox.addEventListener('change', toggleAsrama);
            toggleAsrama();

            const form = document.getElementById('editForm');
            form.addEventListener('submit', function(e) {
                let valid = true;
                form.querySelectorAll('[required]').forEach(function(field) {
                    if (!field.value.trim()) {
                        valid = false;
                        field.classList.add('border-[var(--color-danger)]');
                    } else {
                        field.classList.remove('border-[var(--color-danger)]');
                    }
                });
                if (!valid) {
                    e.preventDefault();
                }
            });
        });
    </script>
    @endpush
</x-app-layout>
