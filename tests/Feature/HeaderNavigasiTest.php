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
     * Test login page menampilkan brand institusi dengan kontras untuk latar terang
     */
    public function test_login_page_menampilkan_brand_institusi()
    {
        $response = $this->get(route('login'));

        $response->assertSee('BAPELKES DINKES DIY');
        // Brand login harus gunakan var(--color-primary) bukan var(--color-gold-soft)
        $response->assertDontSee('color: var(--color-gold-soft);', false);
    }

    /**
     * Test login page brand menggunakan var(--color-accent) untuk tagline
     */
    public function test_login_page_tagline_uses_accent_color()
    {
        $response = $this->get(route('login'));

        // Tagline harus ada dan pakai var(--color-accent)
        $response->assertSee('BAPELKES DINKES DIY');
        $response->assertSee('color: var(--color-accent)', false);
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
