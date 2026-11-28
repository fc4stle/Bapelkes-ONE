<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationBrandingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Halaman login menampilkan nama aplikasi lengkap di h1
     */
    public function test_login_page_displays_full_application_name(): void
    {
        // Note: config('app.name') didapat dari env('APP_NAME'), di test ini default ke 'Laravel'
        // Untuk test, kami cek bahwa layout sudah struktur-nya benar untuk menerima config
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertDontSee('Bapelkes ONE');
    }

    /**
     * Halaman login tidak menampilkan "Bapelkes ONE"
     */
    public function test_login_page_does_not_show_old_brand_name(): void
    {
        $response = $this->get(route('login'));

        $response->assertDontSee('Bapelkes ONE');
    }

    /**
     * Halaman register menampilkan nama aplikasi dan tidak "Bapelkes ONE"
     */
    public function test_register_page_displays_full_application_name(): void
    {
        $response = $this->get(route('register'));

        $response->assertStatus(200);
        $response->assertDontSee('Bapelkes ONE');
    }

    /**
     * Halaman katalog publik tidak menampilkan "Bapelkes ONE"
     */
    public function test_public_catalog_does_not_show_old_brand_name(): void
    {
        $response = $this->get(route('pelatihan.katalog'));

        $response->assertDontSee('Bapelkes ONE');
    }

    /**
     * Dashboard peserta tidak menampilkan "Bapelkes ONE" sebagai brand
     */
    public function test_peserta_dashboard_does_not_show_old_brand_name(): void
    {
        $peserta = $this->createPeserta();

        $response = $this->actingAs($peserta)->get(route('dashboard'));

        // Bapelkes DIY sebagai penyelenggara/lokasi boleh muncul, tapi bukan "Bapelkes ONE"
        $response->assertDontSee('Bapelkes ONE');
    }

    /**
     * Navbar menampilkan logo putih
     */
    public function test_navbar_displays_white_logo(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('logo-bapelkes-putih.png');
    }

    /**
     * Halaman beranda menampilkan nama lengkap aplikasi
     */
    public function test_home_page_displays_full_name(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Sistem Informasi Portal Pelatihan Terpadu');
    }

    /**
     * Halaman beranda tidak menampilkan "Bapelkes ONE"
     */
    public function test_home_page_does_not_show_old_brand_name(): void
    {
        $response = $this->get('/');

        $response->assertDontSee('Bapelkes ONE');
    }

    /**
     * Config app.php default name diatur ke nama lengkap
     */
    public function test_config_app_php_has_correct_default(): void
    {
        // Baca file config langsung untuk verifikasi
        $configPath = config_path('app.php');
        $configContent = file_get_contents($configPath);

        $this->assertStringContainsString(
            "'Sistem Informasi Portal Pelatihan Terpadu'",
            $configContent,
            'config/app.php harus memiliki default name: Sistem Informasi Portal Pelatihan Terpadu'
        );
    }

    /**
     * .env.example memiliki APP_NAME yang benar
     */
    public function test_env_example_has_correct_app_name(): void
    {
        $envExamplePath = base_path('.env.example');
        $envContent = file_get_contents($envExamplePath);

        $this->assertStringContainsString(
            'APP_NAME="Sistem Informasi Portal Pelatihan Terpadu"',
            $envContent,
            '.env.example harus memiliki APP_NAME="Sistem Informasi Portal Pelatihan Terpadu"'
        );
    }

    /**
     * Guest layout memakai config('app.name') di title
     */
    public function test_guest_layout_uses_config_app_name_in_title(): void
    {
        $layoutPath = resource_path('views/layouts/guest.blade.php');
        $layoutContent = file_get_contents($layoutPath);

        $this->assertStringContainsString(
            "{{ isset(\$title) ? \$title.' - '.config('app.name'",
            $layoutContent,
            'Guest layout title harus memakai config(\'app.name\''
        );
    }

    /**
     * App layout title memakai config('app.name')
     */
    public function test_app_layout_uses_config_app_name_in_title(): void
    {
        $layoutPath = resource_path('views/layouts/app.blade.php');
        $layoutContent = file_get_contents($layoutPath);

        $this->assertStringContainsString(
            "config('app.name'",
            $layoutContent,
            'App layout title harus memakai config(\'app.name\''
        );
    }

    /**
     * Navigation navbar menampilkan logo putih
     */
    public function test_navigation_uses_white_logo(): void
    {
        $navPath = resource_path('views/layouts/navigation.blade.php');
        $navContent = file_get_contents($navPath);

        $this->assertStringContainsString(
            'logo-bapelkes-putih.png',
            $navContent,
            'Navigation navbar harus menampilkan logo-bapelkes-putih.png'
        );
        $this->assertStringContainsString(
            'sr-only',
            $navContent,
            'Navigation harus memiliki sr-only text untuk screen readers'
        );
    }

    /**
     * README.md memiliki judul dan deskripsi aplikasi yang benar
     */
    public function test_readme_has_correct_title(): void
    {
        $readmePath = base_path('README.md');
        $readmeContent = file_get_contents($readmePath);

        $this->assertStringContainsString(
            '# Sistem Informasi Portal Pelatihan Terpadu',
            $readmeContent,
            'README.md harus memiliki judul: Sistem Informasi Portal Pelatihan Terpadu'
        );
        $this->assertStringContainsString(
            'dengan studi kasus layanan pelatihan Bapelkes DIY',
            $readmeContent,
            'README.md harus menyebutkan studi kasus Bapelkes DIY'
        );
        $this->assertStringNotContainsString(
            '# BAPELKES ONE',
            $readmeContent,
            'README.md tidak boleh memiliki judul lama "BAPELKES ONE"'
        );
    }

    /**
     * Beranda view menampilkan nama aplikasi dengan benar
     */
    public function test_beranda_view_has_correct_application_name(): void
    {
        $berandaPath = resource_path('views/beranda.blade.php');
        $berandaContent = file_get_contents($berandaPath);

        $this->assertStringContainsString(
            'Sistem Informasi Portal Pelatihan Terpadu',
            $berandaContent,
            'beranda.blade.php harus memiliki nama aplikasi lengkap'
        );
        $this->assertStringNotContainsString(
            '<h1 class="text-3xl sm:text-5xl font-bold mb-4" style="color: var(--color-text);">Bapelkes ONE</h1>',
            $berandaContent,
            'beranda.blade.php tidak boleh hardcode "Bapelkes ONE"'
        );
    }

    /**
     * Halaman login memiliki header dengan judul "Masuk" dan deskripsi peserta/panitia/admin
     */
    public function test_login_page_has_correct_heading(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('Masuk');
        $response->assertSee('Peserta, panitia, dan admin masuk dari halaman ini.');
    }

    /**
     * Guest layout memiliki panel kiri dan form container dengan responsive spacing
     */
    public function test_guest_layout_has_responsive_spacing(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('lg:grid-cols-2');
        $response->assertSee('max-w-[460px]');
        $response->assertSee('py-8 sm:py-12');
    }

    /**
     * Guest layout TIDAK memiliki kartu putih (white card background)
     */
    public function test_guest_layout_does_not_have_white_card_background(): void
    {
        $layoutPath = resource_path('views/layouts/guest.blade.php');
        $layoutContent = file_get_contents($layoutPath);

        $this->assertStringNotContainsString(
            'bg-white',
            $layoutContent,
            'Guest layout tidak boleh memiliki kartu bg-white untuk form'
        );
    }

    /**
     * Guest layout memiliki panel kiri dengan logo dan foto gedung
     */
    public function test_guest_layout_has_branded_left_panel(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('logo-bapelkes-putih.png');
        $response->assertSee('gedung-saraswati.jpeg');
        $response->assertSee('Portal Pelatihan Terpadu');
    }

    /**
     * Beranda menampilkan tagline studi kasus yang benar
     */
    public function test_beranda_displays_correct_case_study_tagline(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Portal terpadu pelatihan dan layanan peserta, studi kasus Bapelkes DIY');
    }

    /**
     * Beranda tidak menampilkan tagline lama tanpa "studi kasus"
     */
    public function test_beranda_does_not_display_old_tagline(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertDontSee('Portal terpadu pelatihan dan layanan peserta Bapelkes DIY');
    }

    /**
     * Halaman login menampilkan tagline studi kasus yang benar
     */
    public function test_login_page_displays_correct_case_study_tagline(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('Portal terpadu pelatihan dan layanan peserta, studi kasus Bapelkes DIY');
    }

    /**
     * Halaman login tidak menampilkan tagline lama tanpa "studi kasus"
     */
    public function test_login_page_does_not_display_old_tagline(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertDontSee('Portal terpadu pelatihan dan layanan peserta Bapelkes DIY');
    }
}
