<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'Bapelkes ONE') }} - Bapelkes ONE</title>

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

        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0" style="background-color: var(--color-bg);">
            <div>
                <a href="/" class="text-center">
                    <h1 class="text-3xl font-bold" style="color: var(--color-primary);">Bapelkes ONE</h1>
                    <p class="text-sm" style="color: var(--color-text);">Portal Pelatihan Bapelkes DIY</p>
                </a>
            </div>

            <div class="w-full max-w-md px-4 mt-6">
                <div class="p-6 rounded-[var(--radius-card)] overflow-hidden" style="background-color: var(--color-surface); box-shadow: var(--shadow-card);">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
