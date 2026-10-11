<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if (auth()->user()->isPanitia())
                        {{ __('Selamat datang, Panitia. Anda dapat mengelola data pelatihan.') }}
                        <div class="mt-4 space-y-2">
                            <a href="{{ route('panitia.dashboard') }}" class="text-indigo-600 hover:text-indigo-900 underline">{{ __('Rekap Panitia') }}</a>
                            <a href="{{ route('pelatihans.index') }}" class="text-indigo-600 hover:text-indigo-900 underline block">{{ __('Kelola Pelatihan') }}</a>
                        </div>
                    @else
                        {{ __('Selamat datang, Peserta. Anda dapat melihat data pelatihan.') }}
                        <a href="{{ route('pelatihan.katalog') }}" class="text-indigo-600 hover:text-indigo-900 underline">{{ __('Lihat Katalog Pelatihan') }}</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
