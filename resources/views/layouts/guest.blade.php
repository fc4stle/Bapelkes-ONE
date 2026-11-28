<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#0F4C3A">

        <title>{{ isset($title) ? $title.' - '.config('app.name', 'Sistem Informasi Portal Pelatihan Terpadu') : config('app.name', 'Sistem Informasi Portal Pelatihan Terpadu') }}</title>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-[var(--color-text)] antialiased">
        <!-- Flash Messages -->
        <x-flash type="success" />
        <x-flash type="error" />

        <div class="min-h-screen lg:grid lg:grid-cols-2">
            <!-- Panel Kiri (Desktop Only) - Foto Gedung Saraswati -->
            <div class="hidden lg:flex flex-col justify-between p-12 xl:p-16 lg:overflow-y-auto relative" style="background: linear-gradient(to bottom, rgba(11, 40, 31, 0.45), rgba(11, 58, 44, 0.92)), url('/images/gedung-saraswati.jpeg') center/cover;" data-brand="panel">
                <!-- Top: Logo Putih -->
                <div>
                    <img src="/images/logo-bapelkes-putih.png" alt="Balai Pelatihan Kesehatan DIY" class="h-11 mb-12" />
                </div>

                <!-- Bottom: Content -->
                <div>
                    <!-- Brand Pill Label -->
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-white border-opacity-50 mb-8">
                        <span class="text-sm text-white font-medium">Balai Pelatihan Kesehatan DIY</span>
                    </div>

                    <!-- Main Heading -->
                    <h1 class="font-bold text-5xl xl:text-5xl leading-tight text-white mb-6" style="font-size: 46px;">
                        Satu akun untuk semua layanan pelatihan.
                    </h1>

                    <!-- Description -->
                    <p class="text-lg text-white max-w-md">
                        Daftar pelatihan, cetak kartu peserta, isi presensi, dan ambil sertifikat dari satu tempat.
                    </p>

                    <!-- Bottom: Copyright Gambar -->
                    <div class="mt-16 text-xs text-white opacity-70">
                        Gambar: Gedung Saraswati, Bapelkes DIY
                    </div>
                </div>
            </div>

            <!-- Panel Kanan (Form Area) -->
            <div class="flex items-center justify-center px-4 py-8 sm:py-12 lg:py-12 lg:px-12 relative" style="background-color: var(--color-bg);">
                <!-- Mobile Hero (Foto) -->
                <div class="lg:hidden absolute top-0 left-0 w-full h-32 sm:h-40" style="background: linear-gradient(to bottom, rgba(11, 40, 31, 0.45), rgba(11, 58, 44, 0.92)), url('/images/gedung-saraswati.jpeg') center/cover; overflow: hidden;">
                    <img src="/images/logo-bapelkes-putih.png" alt="Balai Pelatihan Kesehatan DIY" class="h-8 absolute top-4 left-4" />
                </div>

                <!-- Form Container -->
                <div class="w-full max-w-xs sm:max-w-sm lg:max-w-[460px] mt-32 sm:mt-40 lg:mt-0" style="display: flex; flex-direction: column; gap: 36px;">
                    <!-- Portal Label -->
                    <div style="border-left: 4px solid var(--color-accent); padding-left: 16px;">
                        <span class="font-semibold text-sm uppercase" style="color: var(--color-primary); letter-spacing: 0.05em;">Portal Pelatihan Terpadu</span>
                    </div>

                    <!-- Form Slot -->
                    <div>
                        {{ $slot }}
                    </div>

                    <!-- Contact Block -->
                    <div style="border-top: 1px solid var(--color-line); padding-top: 36px;">
                        <p class="font-bold mb-2" style="color: var(--color-text); font-size: 15px;">
                            Balai Pelatihan Kesehatan DIY
                        </p>
                        <p class="text-sm mb-2" style="color: var(--color-muted); line-height: 1.5;">
                            Jl. Raya Solo km 12,8, Tirtomartani, Kalasan, Sleman
                        </p>
                        <p class="text-sm" style="color: var(--color-muted);">
                            bapelkes@jogjaprov.go.id <span style="color: var(--color-line);">|</span> 0274 497253
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
