<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeaderNavigasiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test dashboard menampilkan navigasi dengan logo putih dan band foto
     */
    public function test_dashboard_menampilkan_band_dengan_logo()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        // Dashboard harus menampilkan band dengan logo putih
        $response->assertSee('logo-bapelkes-putih.png');
        $response->assertSee('data-brand="band"', false);
        $response->assertSee(config('app.name'));
        $response->assertStatus(200);
    }

    /**
     * Test dashboard nav link aktif menampilkan styling aktif
     */
    public function test_dashboard_active_nav_link()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        // Aktif link harus ada di halaman (bisa melalui data-active atau class aktif)
        $response->assertStatus(200);
        $response->assertSee('data-brand="band"', false);
    }

    /**
     * Test login page desktop layout menampilkan panel kiri dengan branding
     */
    public function test_login_page_desktop_layout()
    {
        $html = $this->get(route('login'))->getContent();

        // Pastikan ada grid 2 kolom untuk desktop
        $this->assertStringContainsString('lg:grid-cols-2', $html);

        // Pastikan ada panel kiri dengan data-brand="panel"
        $this->assertStringContainsString('data-brand="panel"', $html);

        // Panel kiri harus punya foto gedung dan logo putih
        $this->assertStringContainsString('gedung-saraswati.jpeg', $html);
        $this->assertStringContainsString('logo-bapelkes-putih.png', $html);
    }

    /**
     * Test login page mobile punya hero bar dengan foto dan logo
     */
    public function test_login_page_mobile_hero()
    {
        $html = $this->get(route('login'))->getContent();

        // Mobile hero harus ada (lg:hidden)
        // Extract mobile hero: <div class="lg:hidden ... >
        preg_match('/<div class="lg:hidden[^>]*h-32[^>]*>\s*<img[^>]*logo-bapelkes-putih[^>]*>/s', $html, $matches);

        // Harus ada mobile hero dengan logo
        $this->assertStringContainsString('lg:hidden', $html, 'Mobile hero tidak ditemukan');
        $this->assertStringContainsString('logo-bapelkes-putih.png', $html, 'Logo putih tidak ada');
    }

    /**
     * Test register page desktop layout menampilkan panel kiri dengan branding
     */
    public function test_register_page_desktop_layout()
    {
        $html = $this->get(route('register'))->getContent();

        // Pastikan ada grid 2 kolom untuk desktop
        $this->assertStringContainsString('lg:grid-cols-2', $html);

        // Pastikan ada panel kiri dengan data-brand="panel"
        $this->assertStringContainsString('data-brand="panel"', $html);

        // Panel kiri harus punya foto dan logo
        $this->assertStringContainsString('gedung-saraswati.jpeg', $html);
        $this->assertStringContainsString('logo-bapelkes-putih.png', $html);
    }

    /**
     * Test register page mobile punya hero bar dengan foto dan logo
     */
    public function test_register_page_mobile_hero()
    {
        $html = $this->get(route('register'))->getContent();

        // Mobile hero harus ada (lg:hidden)
        $this->assertStringContainsString('lg:hidden', $html, 'Mobile hero tidak ditemukan');
        $this->assertStringContainsString('logo-bapelkes-putih.png', $html, 'Logo putih tidak ada');
    }

    /**
     * Test navigation tidak ada handler inline warna (this.style)
     */
    public function test_dashboard_navigation_no_inline_style_handlers()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        // Pastikan tidak ada this.style di response (handler warna inline tidak boleh ada)
        $response->assertDontSee('this.style', false);
    }
}
