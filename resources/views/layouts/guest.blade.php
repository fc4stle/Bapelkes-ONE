<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#6E1423">

        <title>{{ isset($title) ? $title.' - '.config('app.name', 'Sistem Informasi Portal Pelatihan Terpadu') : config('app.name', 'Sistem Informasi Portal Pelatihan Terpadu') }}</title>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-[var(--color-text)] antialiased">
        <!-- Flash Messages -->
        <x-flash type="success" />
        <x-flash type="error" />

        <div class="min-h-screen lg:grid lg:grid-cols-2">
            <!-- Panel Kiri (Desktop Only) -->
            <div class="hidden lg:flex flex-col justify-between p-12 xl:p-16 lg:overflow-y-auto" style="background-color: var(--color-primary);" data-brand="panel">
                <!-- Dot Pattern Overlay -->
                <style>
                    [data-brand="panel"] {
                        background-image: radial-gradient(color-mix(in srgb, white 8%, transparent) 1px, transparent 1px);
                        background-size: 18px 18px;
                    }
                </style>

                <!-- Top: Brand Logo & Title -->
                <div>
                    <!-- Logo Circle -->
                    <div class="flex items-center gap-4 mb-8">
                        <div class="flex items-center justify-center w-[52px] h-[52px] rounded-full border-2" style="border-color: var(--color-gold-soft);">
                            <span class="font-serif font-bold text-3xl" style="color: var(--color-gold-soft);">B</span>
                        </div>
                        <div class="uppercase tracking-[0.12em] leading-tight" style="color: var(--color-gold-soft); font-size: 12px;">
                            BAPELKES DINKES DIY
                        </div>
                    </div>

                    <!-- Main Heading -->
                    <h1 class="font-serif font-bold text-4xl xl:text-5xl leading-tight text-white mb-6">
                        {{ config('app.name', 'Portal Pelatihan Terpadu') }}
                    </h1>

                    <!-- Description -->
                    <p class="text-lg text-white opacity-80 max-w-md mb-8">
                        Portal terpadu pelatihan dan layanan peserta, studi kasus Bapelkes DIY.
                    </p>

                    <!-- Features List -->
                    <ul class="space-y-4">
                        <li class="flex gap-4 text-white opacity-90" style="border-left: 2px solid var(--color-gold-soft);">
                            <span class="pl-4">Pendaftaran dan verifikasi pelatihan</span>
                        </li>
                        <li class="flex gap-4 text-white opacity-90" style="border-left: 2px solid var(--color-gold-soft);">
                            <span class="pl-4">Presensi, kartu peserta, dan sertifikat</span>
                        </li>
                        <li class="flex gap-4 text-white opacity-90" style="border-left: 2px solid var(--color-gold-soft);">
                            <span class="pl-4">Layanan asrama untuk peserta</span>
                        </li>
                        <li class="flex gap-4 text-white opacity-90" style="border-left: 2px solid var(--color-gold-soft);">
                            <span class="pl-4">Evaluasi transfer pelatihan (TIYAS)</span>
                        </li>
                    </ul>
                </div>

                <!-- Bottom: Copyright -->
                <div class="text-xs opacity-60 text-white">
                    © {{ date('Y') }} Bapelkes Dinkes DIY
                </div>
            </div>

            <!-- Panel Kanan (Form Area) -->
            <div class="flex items-center justify-center px-4 py-8 sm:py-12 lg:px-12" style="background-color: var(--color-bg);">
                <div class="w-full max-w-md lg:max-w-lg">
                    <!-- Brand Header (Mobile Only) -->
                    <a href="/" class="lg:hidden block text-center mb-8" data-brand="mobile">
                        <div class="flex items-center justify-center gap-3 mb-4">
                            <!-- Circle Brand -->
                            <div class="flex items-center justify-center w-[42px] h-[42px] rounded-full border-2" style="border-color: var(--color-primary);">
                                <span class="font-serif font-bold text-xl" style="color: var(--color-primary);">B</span>
                            </div>
                        </div>
                        <h1 class="font-serif font-bold text-2xl sm:text-3xl leading-tight" style="color: var(--color-primary); font-size: 28px;">{{ config('app.name', 'Portal Pelatihan Terpadu') }}</h1>
                        <p class="text-xs uppercase tracking-[0.12em] leading-tight mt-2" style="color: var(--color-accent); font-size: 11.5px;">BAPELKES DINKES DIY</p>
                        <p class="text-sm mt-4" style="color: var(--color-text);">Portal terpadu pelatihan dan layanan peserta, studi kasus Bapelkes DIY</p>
                    </a>

                    <!-- Form Card -->
                    <div class="p-6 sm:p-8 rounded-[var(--radius-card)] overflow-hidden" style="background-color: var(--color-surface); border: 1px solid var(--color-line); border-top: 3px solid var(--color-accent);">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
