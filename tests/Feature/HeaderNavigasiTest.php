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
        
        // Panel kiri boleh pakai gold-soft (legitimate untuk latar dark)
        $this->assertStringContainsString('Evaluasi transfer pelatihan (TIYAS)', $html);
    }

    /**
     * Test login page mobile brand tidak memakai gold-soft
     */
    public function test_login_page_mobile_brand_no_gold_soft()
    {
        $html = $this->get(route('login'))->getContent();

        // Extract mobile brand block: <a ... data-brand="mobile" ... </a>
        preg_match('/<a[^>]*data-brand="mobile"[^>]*>.*?<\/a>/s', $html, $matches);
        $this->assertNotEmpty($matches, 'Mobile brand block not found');
        
        $mobileBrand = $matches[0];
        
        // Mobile brand tidak boleh punya gold-soft (pakai primary/accent untuk latar terang)
        $this->assertStringNotContainsString('gold-soft', $mobileBrand);
        
        // Tapi harus ada di panel kiri
        $this->assertStringContainsString('data-brand="panel"', $html);
        $this->assertStringContainsString('gold-soft', $html);
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
        
        // Panel kiri boleh pakai gold-soft
        $this->assertStringContainsString('Evaluasi transfer pelatihan (TIYAS)', $html);
    }

    /**
     * Test register page mobile brand tidak memakai gold-soft
     */
    public function test_register_page_mobile_brand_no_gold_soft()
    {
        $html = $this->get(route('register'))->getContent();

        // Extract mobile brand block
        preg_match('/<a[^>]*data-brand="mobile"[^>]*>.*?<\/a>/s', $html, $matches);
        $this->assertNotEmpty($matches, 'Mobile brand block not found');
        
        $mobileBrand = $matches[0];
        
        // Mobile brand tidak boleh punya gold-soft
        $this->assertStringNotContainsString('gold-soft', $mobileBrand);
        
        // Tapi harus ada di panel kiri
        $this->assertStringContainsString('data-brand="panel"', $html);
        $this->assertStringContainsString('gold-soft', $html);
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
