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

            <!-- Flash Messages -->
            <x-flash type="success" />
            <x-flash type="error" />

            <!-- Page Heading -->
            @isset($header)
                <header style="background-color: var(--color-bg); border-bottom: 1px solid var(--color-line);">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        @stack('scripts')
    </body>
</html>
