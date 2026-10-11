<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-[var(--color-text)] leading-tight">
            Beranda
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-[var(--color-surface)] shadow rounded-[var(--radius-card)] p-[var(--sp-8)] text-center">
                <h1 class="text-[var(--fs-h1)] font-bold text-[var(--color-text)]">Selamat Datang di Bapelkes ONE</h1>
                <p class="mt-[var(--sp-2)] text-[var(--fs-body)] text-gray-500">
                    Portal terpadu pelatihan dan layanan peserta Bapelkes DIY.
                </p>

                @auth
                    @if (auth()->user()->isPanitia())
                        <div class="mt-[var(--sp-6)] flex flex-col sm:flex-row gap-[var(--sp-3)] justify-center max-w-xl mx-auto">
                            <a href="{{ route('panitia.dashboard') }}" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Rekap Panitia
                            </a>
                            <a href="{{ route('pelatihans.index') }}" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Kelola Pelatihan
                            </a>
                        </div>
                    @else
                        <form method="GET" action="{{ route('pelatihan.katalog') }}" class="mt-[var(--sp-6)] flex flex-col sm:flex-row gap-[var(--sp-3)] max-w-xl mx-auto">
                            <x-text-input type="text" name="q" placeholder="Cari pelatihan..." class="flex-1" />
                            <x-primary-button type="submit">Cari Pelatihan</x-primary-button>
                        </form>
                    @endif
                @else
                    <form method="GET" action="{{ route('pelatihan.katalog') }}" class="mt-[var(--sp-6)] flex flex-col sm:flex-row gap-[var(--sp-3)] max-w-xl mx-auto">
                        <x-text-input type="text" name="q" placeholder="Cari pelatihan..." class="flex-1" />
                        <x-primary-button type="submit">Cari Pelatihan</x-primary-button>
                    </form>
                @endauth
            </div>
        </div>
    </div>
</x-app-layout>