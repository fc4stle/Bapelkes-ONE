<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#0F4C3A">

        <title>{{ $title ?? 'Beranda' }} - {{ config('app.name', 'Sistem Informasi Portal Pelatihan Terpadu') }}</title>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen" style="background-color: var(--color-bg);">
            @include('layouts.navigation')

            <!-- Brand Band (Photo + Header) -->
            <div data-brand="band" style="background: linear-gradient(180deg, rgba(11, 40, 31, 0.80), rgba(11, 58, 44, 0.94)), url('/images/gedung-saraswati.jpeg') center 35% / cover; color: white; padding-top: 72px; margin-top: -72px;">
                <!-- Page Header (Slot $header) -->
                @isset($header)
                    <header class="py-5" style="padding-top: 20px; padding-bottom: 36px;">
                        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset
            </div>

            <!-- Flash Messages -->
            <x-flash type="success" />
            <x-flash type="error" />

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>

            <!-- Tombol Naik ke Atas (Back to Top) -->
            <div x-data="{ scrollTop: false }" x-init="window.addEventListener('scroll', () => scrollTop = window.scrollY > 400)" class="fixed bottom-6 right-6 z-40 print:hidden">
                <button 
                    x-show="scrollTop"
                    x-transition
                    @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
                    aria-label="Kembali ke atas"
                    class="w-11 h-11 rounded-full flex items-center justify-center transition-colors duration-200 text-white hover:opacity-90 active:scale-95"
                    style="background-color: var(--color-primary);"
                >
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="18 15 12 9 6 15"></polyline>
                    </svg>
                </button>
            </div>
        </div>

        @stack('scripts')
    </body>
</html>
