<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeaderNavigasiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test dashboard menampilkan brand institusi dengan warna kontras di header
     */
    public function test_dashboard_menampilkan_brand_institusi()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertSee(config('app.name'));
        $response->assertSee('BAPELKES DINKES DIY');
        $response->assertSee('border-bottom: 4px solid var(--color-accent)', false);
        // Brand di navigation bar harus gold-soft, bukan primary
        $response->assertSee('border-color: var(--color-gold-soft)', false);
        $response->assertSee('color: var(--color-gold-soft)', false);
        $response->assertDontSee('border-color: var(--color-primary)', false);
    }

    /**
     * Test dashboard nav link aktif menampilkan border gold soft
     */
    public function test_dashboard_active_nav_link()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        // Aktif link harus punya border var(--color-gold-soft)
        $response->assertSee('border-[var(--color-gold-soft)]', false);
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
