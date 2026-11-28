<?php

namespace Tests\Feature;

use Tests\TestCase;

class TemaHijauTest extends TestCase
{
    /**
     * Test: app.css memiliki semua nilai token warna hijau institusi
     */
    public function test_app_css_contains_all_green_tokens(): void
    {
        $cssPath = base_path('resources/css/app.css');
        $this->assertFileExists($cssPath);

        $css = file_get_contents($cssPath);

        // Token warna hijau institusi harus ada di app.css
        $this->assertStringContainsString('--color-primary: #0F4C3A', $css, 'Token --color-primary tidak ditemukan atau nilai salah');
        $this->assertStringContainsString('--color-primary-hover: #0B3A2C', $css, 'Token --color-primary-hover tidak ditemukan atau nilai salah');
        $this->assertStringContainsString('--color-accent: #C8962E', $css, 'Token --color-accent tidak ditemukan atau nilai salah');
        $this->assertStringContainsString('--color-bg: #F4F1E8', $css, 'Token --color-bg tidak ditemukan atau nilai salah');
        $this->assertStringContainsString('--color-text: #1B2A24', $css, 'Token --color-text tidak ditemukan atau nilai salah');
        $this->assertStringContainsString('--color-surface: #FFFFFF', $css, 'Token --color-surface tidak ditemukan atau nilai salah');
        $this->assertStringContainsString('--color-success: #2F6F4E', $css, 'Token --color-success tidak ditemukan atau nilai salah');
        $this->assertStringContainsString('--color-warning: #B7791F', $css, 'Token --color-warning tidak ditemukan atau nilai salah');
        $this->assertStringContainsString('--color-danger: #B3263F', $css, 'Token --color-danger tidak ditemukan atau nilai salah');
        $this->assertStringContainsString('--color-muted: #5F6B64', $css, 'Token --color-muted tidak ditemukan atau nilai salah');
        $this->assertStringContainsString('--color-line: #DDD8C8', $css, 'Token --color-line tidak ditemukan atau nilai salah');
        $this->assertStringContainsString('--color-gold-soft: #E0B04A', $css, 'Token --color-gold-soft tidak ditemukan atau nilai salah');
        $this->assertStringContainsString('--color-primary-soft: #DCEBE4', $css, 'Token --color-primary-soft tidak ditemukan atau nilai salah');
    }

    /**
     * Test: app.css tidak lagi mengandung warna maroon lama
     */
    public function test_app_css_does_not_contain_old_maroon_colors(): void
    {
        $cssPath = base_path('resources/css/app.css');
        $css = file_get_contents($cssPath);

        // Warna maroon lama (harus dihapus)
        $this->assertStringNotContainsString('6E1423', $css, 'Warna maroon lama 6E1423 masih ada di app.css');
        $this->assertStringNotContainsString('B8892B', $css, 'Warna emas lama B8892B masih ada di app.css');
    }

    /**
     * Test: resources/js/app.js mengimpor Poppins dan tidak mengimpor font lama
     */
    public function test_app_js_imports_poppins_not_old_fonts(): void
    {
        $jsPath = base_path('resources/js/app.js');
        $this->assertFileExists($jsPath);

        $js = file_get_contents($jsPath);

        // Import Poppins harus ada
        $this->assertStringContainsString('@fontsource/poppins/400.css', $js, 'Import Poppins 400 tidak ditemukan');
        $this->assertStringContainsString('@fontsource/poppins/500.css', $js, 'Import Poppins 500 tidak ditemukan');
        $this->assertStringContainsString('@fontsource/poppins/600.css', $js, 'Import Poppins 600 tidak ditemukan');
        $this->assertStringContainsString('@fontsource/poppins/700.css', $js, 'Import Poppins 700 tidak ditemukan');

        // Font lama tidak boleh ada
        $this->assertStringNotContainsString('source-serif', $js, 'Import source-serif masih ada di app.js');
        $this->assertStringNotContainsString('public-sans', $js, 'Import public-sans masih ada di app.js');
    }

    /**
     * Test: Halaman login menampilkan logo, gambar, dan teks yang benar
     */
    public function test_login_page_displays_correct_content(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);

        // Gambar background gedung harus ada
        $response->assertSeeInOrder(['gedung-saraswati.jpeg'], false);

        // Logo putih harus ada
        $response->assertSeeInOrder(['logo-bapelkes-putih.png'], false);

        // Label portal terpadu harus ada
        $response->assertSee('Portal Pelatihan Terpadu', false);

        // Alamat institusi harus ada
        $response->assertSee('Jl. Raya Solo km 12,8', false);
    }

    /**
     * Test: Halaman login TIDAK memiliki kartu putih (bg-white) pada form wrapper
     */
    public function test_login_page_has_no_white_card_background(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);

        // Ambil HTML response
        $html = $response->getContent();

        // Periksa bahwa form wrapper tidak punya class bg-white
        // (Halaman mobile/desktop harus punya background color var(--color-bg), bukan white card)
        // Ini adalah pemeriksaan sederhana: form container harus pake color-bg atau tidak ada bg-white
        $this->assertStringNotContainsString('bg-white', $html, 'Class bg-white ditemukan di halaman login (form seharusnya tidak punya background putih)');
    }

    /**
     * Test: File gambar hero (background dan logo) ada di public/images
     */
    public function test_hero_images_exist(): void
    {
        $logoPath = public_path('images/logo-bapelkes-putih.png');
        $heroPath = public_path('images/gedung-saraswati.jpeg');

        $this->assertFileExists($logoPath, 'File logo-bapelkes-putih.png tidak ditemukan di public/images');
        $this->assertFileExists($heroPath, 'File gedung-saraswati.jpeg tidak ditemukan di public/images');
    }
}
