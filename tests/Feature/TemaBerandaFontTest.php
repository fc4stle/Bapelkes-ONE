<?php

namespace Tests\Feature;

use Tests\TestCase;

class TemaBerandaFontTest extends TestCase
{
    /**
     * Test: beranda.blade.php tidak memuat "studi kasus Bapelkes DIY"
     */
    public function test_beranda_tidak_ada_studi_kasus_text()
    {
        $berandaPath = base_path('resources/views/beranda.blade.php');
        $content = file_get_contents($berandaPath);

        // Assert tidak ada "studi kasus Bapelkes DIY"
        $this->assertStringNotContainsString('studi kasus Bapelkes DIY', $content,
            'beranda.blade.php masih memuat "studi kasus Bapelkes DIY"');

        // Assert ada "dari satu portal"
        $this->assertStringContainsString('dari satu portal', $content,
            'beranda.blade.php tidak memuat "dari satu portal"');
    }

    /**
     * Test: beranda.blade.php peserta login memiliki "Pendaftaran Saya"
     */
    public function test_beranda_pendaftaran_saya_button()
    {
        $berandaPath = base_path('resources/views/beranda.blade.php');
        $content = file_get_contents($berandaPath);

        // Assert ada link ke pendaftarans.index
        $this->assertStringContainsString("route('pendaftarans.index')", $content,
            'beranda.blade.php tidak memuat route pendaftarans.index');

        // Assert ada label "Pendaftaran Saya"
        $this->assertStringContainsString('Pendaftaran Saya', $content,
            'beranda.blade.php tidak memuat "Pendaftaran Saya"');
    }

    /**
     * Test: beranda.blade.php h1 menggunakan text-3xl sm:text-4xl (bukan text-5xl)
     */
    public function test_beranda_h1_text_size()
    {
        $berandaPath = base_path('resources/views/beranda.blade.php');
        $content = file_get_contents($berandaPath);

        // Assert ada text-3xl sm:text-4xl di h1
        $this->assertStringContainsString('text-3xl sm:text-4xl', $content,
            'beranda.blade.php h1 tidak menggunakan text-3xl sm:text-4xl');

        // Assert tidak ada text-5xl di h1 (hanya di old version)
        $this->assertStringNotContainsString('text-4xl sm:text-5xl lg:text-5xl', $content,
            'beranda.blade.php masih menggunakan old text-4xl sm:text-5xl lg:text-5xl');
    }

    /**
     * Test: beranda.blade.php wrapper h1+p memiliki max-w-2xl
     */
    public function test_beranda_max_w_2xl_wrapper()
    {
        $berandaPath = base_path('resources/views/beranda.blade.php');
        $content = file_get_contents($berandaPath);

        // Assert ada max-w-2xl div wrapper
        $this->assertStringContainsString('max-w-2xl', $content,
            'beranda.blade.php tidak memiliki max-w-2xl wrapper');
    }

    /**
     * Test: beranda.blade.php paragraf deskripsi menggunakan .band-sub class
     */
    public function test_beranda_paragraph_band_sub()
    {
        $berandaPath = base_path('resources/views/beranda.blade.php');
        $content = file_get_contents($berandaPath);

        // Assert ada band-sub class di paragraf
        $this->assertStringContainsString('class="text-base sm:text-lg mb-8 leading-relaxed band-sub"', $content,
            'beranda.blade.php paragraf deskripsi tidak menggunakan band-sub class');
    }

    /**
     * Test: navigation.blade.php tombol "Daftar" guest memiliki border class
     */
    public function test_navigation_daftar_button_has_border()
    {
        $navPath = base_path('resources/views/layouts/navigation.blade.php');
        $content = file_get_contents($navPath);

        // Assert ada border border-white/35 di tombol Daftar guest
        $this->assertStringContainsString('border border-white/35', $content,
            'navigation.blade.php tombol Daftar tidak memiliki border border-white/35');
    }

    /**
     * Test: tailwind.config.js tidak memuat 'Public Sans' dan 'Source Serif'
     */
    public function test_tailwind_config_poppins_font()
    {
        $configPath = base_path('tailwind.config.js');
        $content = file_get_contents($configPath);

        // Assert tidak ada Public Sans
        $this->assertStringNotContainsString('Public Sans', $content,
            'tailwind.config.js masih memuat Public Sans');

        // Assert tidak ada Source Serif
        $this->assertStringNotContainsString('Source Serif', $content,
            'tailwind.config.js masih memuat Source Serif');

        // Assert ada Poppins di sans
        $this->assertStringContainsString("sans: ['Poppins'", $content,
            'tailwind.config.js sans tidak menggunakan Poppins');

        // Assert ada Poppins di serif
        $this->assertStringContainsString("serif: ['Poppins'", $content,
            'tailwind.config.js serif tidak menggunakan Poppins');
    }
}
