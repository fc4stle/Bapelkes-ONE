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
            <!-- Brand Band (Photo + Nav + Header) -->
            <div data-brand="band" style="background: linear-gradient(180deg, rgba(11, 40, 31, 0.80), rgba(11, 58, 44, 0.94)), url('/images/gedung-saraswati.jpeg') center 35% / cover; color: white;">
                @include('layouts.navigation')

                <!-- Page Header (Slot $header) -->
                @isset($header)
                    <header class="py-5 px-4 sm:px-6 lg:px-8" style="padding-top: 20px; padding-bottom: 36px;">
                        <div class="max-w-7xl mx-auto">
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
        </div>

        @stack('scripts')
    </body>
</html>
