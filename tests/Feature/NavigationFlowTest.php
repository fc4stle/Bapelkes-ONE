<?php

namespace Tests\Feature;

use App\Models\Pelatihan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_panitia_login_redirects_to_panitia_dashboard(): void
    {
        $panitia = User::factory()->create(['role' => 'panitia']);

        $response = $this->post('/login', [
            'email' => $panitia->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('panitia.dashboard'));
    }

    public function test_peserta_login_redirects_to_dashboard(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);

        $response = $this->post('/login', [
            'email' => $peserta->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
    }

    public function test_navigation_shows_panitia_menu_for_panitia(): void
    {
        $panitia = User::factory()->create(['role' => 'panitia']);

        $response = $this->actingAs($panitia)
            ->get(route('dashboard'));

        $response->assertSee('Rekap Panitia');
        $response->assertSee('Kelola Pelatihan');
        $response->assertDontSee('Pendaftaran Saya');
    }

    public function test_navigation_shows_peserta_menu_for_peserta(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);

        $response = $this->actingAs($peserta)
            ->get(route('dashboard'));

        $response->assertSee('Pendaftaran Saya');
        $response->assertDontSee('Rekap Panitia');
        $response->assertDontSee('Kelola Pelatihan');
    }

    public function test_pelatihans_index_shows_pendaftar_and_presensi_links(): void
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create();

        $response = $this->actingAs($panitia)
            ->get(route('pelatihans.index'));

        $response->assertSee(route('pelatihan.pendaftar', $pelatihan));
        $response->assertSee(route('pelatihan.presensi', $pelatihan));
        $response->assertSee('Pendaftar');
        $response->assertSee('Presensi');
    }

    public function test_dashboard_rekap_shows_pelatihan_as_link(): void
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create();

        $response = $this->actingAs($panitia)
            ->get(route('panitia.dashboard'));

        $response->assertSee(route('pelatihan.pendaftar', $pelatihan));
    }

    public function test_pendaftar_page_has_back_button_and_presensi_link(): void
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create();

        $response = $this->actingAs($panitia)
            ->get(route('pelatihan.pendaftar', $pelatihan));

        $response->assertSee(route('panitia.dashboard'));
        $response->assertSee(route('pelatihan.presensi', $pelatihan));
        $response->assertSee('Kembali ke Rekap Panitia');
        $response->assertSee('Presensi');
    }

    public function test_presensi_page_has_back_button_to_pendaftar(): void
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create();

        $response = $this->actingAs($panitia)
            ->get(route('pelatihan.presensi', $pelatihan));

        $response->assertSee(route('pelatihan.pendaftar', $pelatihan));
        $response->assertSee('Kembali ke Pendaftar');
    }

    public function test_beranda_shows_panitia_buttons_for_authenticated_panitia(): void
    {
        $panitia = User::factory()->create(['role' => 'panitia']);

        $response = $this->actingAs($panitia)
            ->get('/');

        $response->assertSee(route('panitia.dashboard'));
        $response->assertSee(route('pelatihans.index'));
    }

    public function test_beranda_shows_search_form_for_peserta(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);

        $response = $this->actingAs($peserta)
            ->get('/');

        $response->assertSee('Cari Pelatihan');
    }
}
