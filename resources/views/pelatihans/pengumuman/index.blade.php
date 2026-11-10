<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl leading-tight" style="color: var(--color-text);">
            Pengumuman: {{ $pelatihan->nama }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Form Pengumuman Baru -->
            <div class="shadow rounded-lg mb-8" style="background-color: var(--color-surface);">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium mb-6" style="color: var(--color-text);">Kirim Pengumuman Baru</h3>

                    @if ($errors->has('pengumuman'))
                        <div class="mb-6 p-4 rounded-md border" style="background-color: var(--color-danger); background-color: rgba(var(--rgb-danger), 0.1); border-color: rgba(var(--rgb-danger), 0.3); color: var(--color-danger);">
                            <p class="text-sm">{{ $errors->first('pengumuman') }}</p>
                        </div>
                    @endif

                    <form action="{{ route('pelatihan.pengumuman.store', $pelatihan) }}" method="POST">
                        @csrf
                        
                        <div class="mb-6">
                            <label for="judul" class="block text-sm font-medium mb-2" style="color: var(--color-text);">Judul <span style="color: var(--color-danger);">*</span></label>
                            <input 
                                type="text"
                                id="judul"
                                name="judul"
                                maxlength="120"
                                required
                                class="w-full px-4 py-2 border rounded-md shadow-sm text-sm focus:outline-none focus:ring-2 focus:ring-offset-2 @error('judul') border-red-300 @enderror"
                                style="border-color: #d1d5db; --tw-ring-color: var(--color-primary);"
                                value="{{ old('judul') }}"
                            >
                            @error('judul')
                                <p class="mt-2 text-sm" style="color: var(--color-danger);">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label for="isi" class="block text-sm font-medium mb-2" style="color: var(--color-text);">Isi <span style="color: var(--color-danger);">*</span></label>
                            <textarea 
                                id="isi"
                                name="isi"
                                maxlength="1000"
                                required
                                rows="6"
                                class="w-full px-4 py-2 border rounded-md shadow-sm text-sm focus:outline-none focus:ring-2 focus:ring-offset-2 @error('isi') border-red-300 @enderror"
                                style="border-color: #d1d5db; --tw-ring-color: var(--color-primary);"
                            >{{ old('isi') }}</textarea>
                            @error('isi')
                                <p class="mt-2 text-sm" style="color: var(--color-danger);">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex gap-3">
                            <button 
                                type="submit"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2"
                                style="background-color: var(--color-primary);"
                            >
                                <svg class="mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10.5 1.5H5.75A2.25 2.25 0 003.5 3.75v12.5A2.25 2.25 0 005.75 18.5h8.5a2.25 2.25 0 002.25-2.25V6.5m-11-3v3m8-3v3m-8 3.75h8m-8 3h8m-8 3h5" />
                                </svg>
                                Kirim Pengumuman
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Riwayat Pengumuman -->
            <div class="shadow rounded-lg" style="background-color: var(--color-surface);">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium mb-6" style="color: var(--color-text);">Riwayat Pengumuman</h3>

                    @if ($pengumumans->isEmpty())
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <p class="mt-4" style="color: var(--color-text); opacity: 0.6;">Belum ada pengumuman.</p>
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach ($pengumumans as $pengumuman)
                                <div class="p-4 rounded-lg" style="border: 1px solid #e5e7eb; background-color: var(--color-bg);">
                                    <div class="flex justify-between items-start mb-2">
                                        <h4 class="font-medium" style="color: var(--color-text);">{{ $pengumuman->judul }}</h4>
                                    </div>
                                    <p class="text-sm mb-3" style="color: var(--color-text);">{{ $pengumuman->isi }}</p>
                                    <p class="text-xs" style="color: var(--color-text); opacity: 0.6;">
                                        Dikirim oleh {{ $pengumuman->pengirim?->name ?? 'Sistem' }} 
                                        pada {{ $pengumuman->created_at->format('d M Y H:i') }},
                                        {{ $pengumuman->jumlah_penerima }} penerima
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
