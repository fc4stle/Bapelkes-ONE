<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeaderNavigasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_menampilkan_brand_institusi()
    {
        $user = User::factory()->create(['role' => 'peserta']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee(config('app.name'), false);
        $response->assertSee('BAPELKES DINKES DIY', false);
        $response->assertSee('border-bottom: 4px solid var(--color-accent)', false);
    }

    public function test_dashboard_nav_link_aktif_menampilkan_gold_border()
    {
        $user = User::factory()->create(['role' => 'peserta']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('border-[var(--color-gold-soft)]', false);
    }

    public function test_login_page_menampilkan_brand_institusi()
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('BAPELKES DINKES DIY', false);
    }
}
