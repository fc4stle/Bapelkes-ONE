<form action="{{ $action }}" method="POST" class="space-y-6">
    @csrf
    @if ($method === 'PUT')
        @method('PUT')
    @endif

    <div>
        <label for="nama" class="block text-sm font-medium text-gray-700">Nama Pelatihan <span class="text-red-500">*</span></label>
        <input type="text" name="nama" id="nama" value="{{ old('nama', $pelatihan?->nama) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border px-3 py-2 @error('nama') border-red-500 @enderror" required>
        @error('nama')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="deskripsi" class="block text-sm font-medium text-gray-700">Deskripsi</label>
        <textarea name="deskripsi" id="deskripsi" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border px-3 py-2 @error('deskripsi') border-red-500 @enderror">{{ old('deskripsi', $pelatihan?->deskripsi) }}</textarea>
        @error('deskripsi')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <label for="tanggal_mulai" class="block text-sm font-medium text-gray-700">Tanggal Mulai <span class="text-red-500">*</span></label>
            <input type="date" name="tanggal_mulai" id="tanggal_mulai" value="{{ old('tanggal_mulai', $pelatihan?->tanggal_mulai?->format('Y-m-d')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border px-3 py-2 @error('tanggal_mulai') border-red-500 @enderror" required>
            @error('tanggal_mulai')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="tanggal_selesai" class="block text-sm font-medium text-gray-700">Tanggal Selesai <span class="text-red-500">*</span></label>
            <input type="date" name="tanggal_selesai" id="tanggal_selesai" value="{{ old('tanggal_selesai', $pelatihan?->tanggal_selesai?->format('Y-m-d')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border px-3 py-2 @error('tanggal_selesai') border-red-500 @enderror" required>
            @error('tanggal_selesai')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <label for="lokasi" class="block text-sm font-medium text-gray-700">Lokasi <span class="text-red-500">*</span></label>
            <input type="text" name="lokasi" id="lokasi" value="{{ old('lokasi', $pelatihan?->lokasi) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border px-3 py-2 @error('lokasi') border-red-500 @enderror" required>
            @error('lokasi')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="kuota" class="block text-sm font-medium text-gray-700">Kuota <span class="text-red-500">*</span></label>
            <input type="number" name="kuota" id="kuota" min="1" value="{{ old('kuota', $pelatihan?->kuota) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border px-3 py-2 @error('kuota') border-red-500 @enderror" required>
            @error('kuota')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label for="status" class="block text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
        <select name="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border px-3 py-2 @error('status') border-red-500 @enderror" required>
            <option value="draft" {{ old('status', $pelatihan?->status) === 'draft' ? 'selected' : '' }}>Draft</option>
            <option value="dibuka" {{ old('status', $pelatihan?->status) === 'dibuka' ? 'selected' : '' }}>Dibuka</option>
            <option value="ditutup" {{ old('status', $pelatihan?->status) === 'ditutup' ? 'selected' : '' }}>Ditutup</option>
            <option value="selesai" {{ old('status', $pelatihan?->status) === 'selesai' ? 'selected' : '' }}>Selesai</option>
        </select>
        @error('status')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex justify-end space-x-3">
        <a href="{{ route('pelatihans.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            Batal
        </a>
        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            {{ $submitLabel }}
        </button>
    </div>
</form>
