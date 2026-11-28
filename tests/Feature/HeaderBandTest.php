<?php

namespace Tests\Feature;

use App\Models\Pelatihan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeaderBandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test halaman beranda menampilkan band dengan data-brand="band"
     */
    public function test_beranda_displays_header_band(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('data-brand="band"', false);
        $response->assertSee('/images/gedung-saraswati.jpeg');
        $response->assertSee('/images/logo-bapelkes-putih.png');
    }

    /**
     * Test halaman beranda tidak mengandung warna hardcode yang tidak diperbolehkan
     */
    public function test_beranda_does_not_contain_hardcoded_colors(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertDontSee('#555');
        $response->assertDontSee('#666');
        $response->assertDontSee('#999');
        $response->assertDontSee('#5C3D2E');
    }

    /**
     * Test halaman dashboard peserta menampilkan band tanpa error 500
     */
    public function test_peserta_dashboard_displays_header_band(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);

        $response = $this->actingAs($peserta)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('data-brand="band"', false);
    }

    /**
     * Test halaman dashboard panitia menampilkan band tanpa error 500
     */
    public function test_panitia_dashboard_displays_header_band(): void
    {
        $panitia = User::factory()->create(['role' => 'panitia']);

        $response = $this->actingAs($panitia)->get(route('panitia.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('data-brand="band"', false);
    }

    /**
     * Test navbar tidak lagi menampilkan styling border emas lama
     */
    public function test_navbar_does_not_have_old_styling(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertDontSee('border-bottom: 4px');
        // Navbar hanya harus menampilkan logo, tidak text "BAPELKES DINKES DIY"
        $response->assertDontSee('BAPELKES DINKES DIY');
    }

    /**
     * Test beranda menampilkan "Belum ada pelatihan yang dibuka." saat tidak ada pelatihan
     */
    public function test_beranda_shows_empty_state_when_no_training(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Belum ada pelatihan yang dibuka.');
    }

    /**
     * Test beranda menampilkan nama pelatihan ketika ada pelatihan dibuka
     */
    public function test_beranda_shows_training_name_when_training_exists(): void
    {
        $pelatihan = Pelatihan::factory()->create([
            'status' => 'dibuka',
            'nama' => 'Pelatihan Test XYZ 2026',
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Pelatihan Test XYZ 2026');
    }
}
