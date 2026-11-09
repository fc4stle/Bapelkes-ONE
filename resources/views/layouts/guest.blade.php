<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title.' - '.config('app.name', 'Sistem Informasi Portal Pelatihan Terpadu') : config('app.name', 'Sistem Informasi Portal Pelatihan Terpadu') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <!-- Flash Messages -->
        <x-flash type="success" />
        <x-flash type="error" />

        <div class="min-h-screen flex flex-col items-center justify-center px-4 py-8 sm:py-12" style="background-color: var(--color-bg);">
            <div class="w-full max-w-md">
                <a href="/" class="block text-center mb-6">
                    <h1 class="text-2xl sm:text-3xl font-bold leading-snug" style="color: var(--color-primary);">{{ config('app.name', 'Sistem Informasi Portal Pelatihan Terpadu') }}</h1>
                    <p class="text-sm mt-2" style="color: var(--color-text);">Portal terpadu pelatihan dan layanan peserta Bapelkes DIY</p>
                </a>
                <div class="p-6 sm:p-8 rounded-[var(--radius-card)] overflow-hidden" style="background-color: var(--color-surface); box-shadow: var(--shadow-card);">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
