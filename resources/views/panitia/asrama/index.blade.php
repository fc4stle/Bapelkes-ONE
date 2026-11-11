<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Kelola Asrama
        </h2>
    </x-slot>

    <div class="py-12" style="background-color: var(--color-bg);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Session Error Message -->
            @if (session('error'))
                <div class="mb-6 p-4 rounded-lg" style="border-left: 4px solid var(--color-primary); background-color: var(--color-surface); color: var(--color-text);">
                    <p class="font-medium">{{ session('error') }}</p>
                </div>
            @endif

            <div style="background-color: var(--color-surface); box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: var(--radius-card);">
                <div class="px-4 py-5 sm:p-6">
                    <div class="mb-6">
                        <h1 class="text-2xl font-bold mb-4" style="color: var(--color-text);">Daftar Asrama</h1>

                        <!-- Filter Tanggal -->
                        <form method="GET" class="mb-6 flex gap-2 items-end">
                            <div>
                                <label for="tanggal" class="block text-sm font-medium mb-1" style="color: var(--color-text);">
                                    Filter Tanggal
                                </label>
                                <input
                                    type="date"
                                    id="tanggal"
                                    name="tanggal"
                                    value="{{ $tanggal ?? '' }}"
                                    class="px-3 py-2 border border-gray-300 rounded-md text-sm"
                                />
                            </div>
                            <button
                                type="submit"
                                class="px-4 py-2 text-sm font-medium text-white rounded-md hover:opacity-90"
                                style="background-color: var(--color-primary);"
                            >
                                Filter
                            </button>
                            @if ($tanggal)
                                <a
                                    href="{{ route('panitia.asrama.index') }}"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-md hover:bg-gray-50"
                                >
                                    Reset
                                </a>
                            @endif
                        </form>
                    </div>

                    @if ($asramas->isEmpty())
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5.581m0 0H9m5.581 0a2 2 0 100-4h-.581m0 4a2 2 0 100 4h.581m0 0H9" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium" style="color: var(--color-text);">Belum ada asrama</h3>
                            <p class="mt-1 text-sm text-gray-500">Mulai dengan membuat asrama baru.</p>
                        </div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach ($asramas as $asrama)
                                <a
                                    href="{{ route('panitia.asrama.show', $asrama['id']) }}"
                                    class="p-4 border border-gray-200 rounded-lg hover:shadow-md transition"
                                    style="background-color: var(--color-surface);"
                                >
                                    <h3 class="text-lg font-semibold mb-3" style="color: var(--color-primary);">
                                        {{ $asrama['nama'] }}
                                    </h3>

                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span style="color: var(--color-text);">Jumlah Kamar:</span>
                                            <span class="font-medium">{{ $asrama['jumlah_kamar'] }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span style="color: var(--color-text);">Total Kapasitas:</span>
                                            <span class="font-medium">{{ $asrama['total_kapasitas'] }} orang</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span style="color: var(--color-text);">Penghuni Aktif:</span>
                                            <span class="font-medium">{{ $asrama['jumlah_penghuni_aktif'] }} orang</span>
                                        </div>
                                        <div class="flex justify-between border-t pt-2 mt-2">
                                            <span style="color: var(--color-text);">Sisa Kapasitas:</span>
                                            <span class="font-medium" style="color: @if ($asrama['sisa_kapasitas'] <= 0) var(--color-danger) @elseif ($asrama['sisa_kapasitas'] <= 3) var(--color-warning) @else var(--color-primary) @endif;">
                                                {{ $asrama['sisa_kapasitas'] }} orang
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
