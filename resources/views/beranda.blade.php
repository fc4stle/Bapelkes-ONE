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

                <form method="GET" action="{{ route('pelatihan.katalog') }}" class="mt-[var(--sp-6)] flex flex-col sm:flex-row gap-[var(--sp-3)] max-w-xl mx-auto">
                    <x-text-input type="text" name="q" placeholder="Cari pelatihan..." class="flex-1" />
                    <x-primary-button type="submit">Cari Pelatihan</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>