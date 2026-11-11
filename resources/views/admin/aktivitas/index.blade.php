<x-app-layout>
    <x-slot name="header">
        Riwayat Aktivitas
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden shadow-sm rounded-lg" style="background-color: var(--color-surface);">
                <div class="p-6" style="color: var(--color-text);">
                    <!-- Filter dan Search -->
                    <form method="GET" action="{{ route('admin.aktivitas.index') }}" class="mb-6">
                        <div class="flex flex-col md:flex-row gap-4">
                            <!-- Filter Aksi -->
                            <div class="flex-1">
                                <label for="aksi" class="block text-sm font-medium mb-1" style="color: var(--color-text);">
                                    Filter Aksi
                                </label>
                                <select name="aksi" id="aksi" class="w-full px-3 py-2 rounded-md border"
                                    style="border-color: color-mix(in srgb, var(--color-text) 20%, transparent); color: var(--color-text);">
                                    <option value="">-- Semua Aksi --</option>
                                    @foreach ($aksiOptions as $aksi)
                                        <option value="{{ $aksi }}" @if (request('aksi') === $aksi) selected @endif>
                                            {{ $aksi }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Pencarian -->
                            <div class="flex-1">
                                <label for="q" class="block text-sm font-medium mb-1" style="color: var(--color-text);">
                                    Cari Deskripsi
                                </label>
                                <input type="text" name="q" id="q" value="{{ request('q') }}"
                                    class="w-full px-3 py-2 rounded-md border"
                                    style="border-color: color-mix(in srgb, var(--color-text) 20%, transparent); color: var(--color-text);"
                                    placeholder="Cari teks di deskripsi...">
                            </div>

                            <!-- Button -->
                            <div class="flex items-end gap-2">
                                <button type="submit" class="px-4 py-2 rounded-md text-white" style="background-color: var(--color-primary);">
                                    Filter
                                </button>
                                <a href="{{ route('admin.aktivitas.index') }}" class="px-4 py-2 rounded-md border"
                                    style="border-color: color-mix(in srgb, var(--color-text) 20%, transparent); color: var(--color-text);">
                                    Reset
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- Tabel untuk Desktop, Kartu untuk Mobile -->
                    @if ($aktivitas->count() > 0)
                        <!-- Desktop Tabel -->
                        <div class="hidden md:block">
                            <table class="w-full border-collapse text-sm" style="border: 1px solid color-mix(in srgb, var(--color-text) 20%, transparent);">
                                <thead>
                                    <tr style="background-color: color-mix(in srgb, var(--color-bg) 50%, var(--color-surface));">
                                        <th class="px-4 py-2 text-left" style="border-right: 1px solid color-mix(in srgb, var(--color-text) 20%, transparent);">Waktu</th>
                                        <th class="px-4 py-2 text-left" style="border-right: 1px solid color-mix(in srgb, var(--color-text) 20%, transparent);">Pelaku</th>
                                        <th class="px-4 py-2 text-left" style="border-right: 1px solid color-mix(in srgb, var(--color-text) 20%, transparent);">Aksi</th>
                                        <th class="px-4 py-2 text-left">Deskripsi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($aktivitas as $log)
                                        <tr style="border-bottom: 1px solid color-mix(in srgb, var(--color-text) 20%, transparent);">
                                            <td class="px-4 py-2" style="border-right: 1px solid color-mix(in srgb, var(--color-text) 20%, transparent);">
                                                {{ $log->created_at->translatedFormat('d M Y H:i') }}
                                            </td>
                                            <td class="px-4 py-2" style="border-right: 1px solid color-mix(in srgb, var(--color-text) 20%, transparent);">
                                                {{ $log->pelaku?->name ?? '—' }}
                                            </td>
                                            <td class="px-4 py-2" style="border-right: 1px solid color-mix(in srgb, var(--color-text) 20%, transparent);">
                                                <span class="px-2 py-1 rounded text-xs" style="background-color: color-mix(in srgb, var(--color-primary) 10%, transparent); color: var(--color-primary);">
                                                    {{ $log->aksi }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2">
                                                {{ Str::limit($log->deskripsi, 60) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Kartu -->
                        <div class="md:hidden space-y-4">
                            @foreach ($aktivitas as $log)
                                <div class="block border rounded-lg p-4" style="border-color: color-mix(in srgb, var(--color-text) 20%, transparent);">
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="font-semibold" style="color: var(--color-text);">
                                            {{ $log->created_at->translatedFormat('d M Y H:i') }}
                                        </div>
                                        <span class="px-2 py-1 rounded text-xs" style="background-color: color-mix(in srgb, var(--color-primary) 10%, transparent); color: var(--color-primary);">
                                            {{ $log->aksi }}
                                        </span>
                                    </div>
                                    <div class="text-sm mb-1">
                                        <span style="color: var(--color-text); opacity: 0.7;">Pelaku:</span>
                                        {{ $log->pelaku?->name ?? '—' }}
                                    </div>
                                    <div class="text-sm">
                                        <span style="color: var(--color-text); opacity: 0.7;">Deskripsi:</span>
                                        {{ Str::limit($log->deskripsi, 100) }}
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6">
                            {{ $aktivitas->render() }}
                        </div>
                    @else
                        <!-- Empty State -->
                        <div class="py-12 text-center">
                            <div style="color: var(--color-text); opacity: 0.4;">
                                @if (request('aksi') || request('q'))
                                    Tidak ada aktivitas yang cocok.
                                @else
                                    Belum ada aktivitas tercatat.
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
