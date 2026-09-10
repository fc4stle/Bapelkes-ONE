<?php

namespace Tests\Feature;

use App\Enums\MetodePelatihan;
use App\Enums\StatusPelatihan;
use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PelatihanKatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['role' => 'peserta']));
    }

    public function test_katalog_returns_200_for_authenticated_peserta(): void
    {
        $response = $this->get(route('pelatihan.katalog'));

        $response->assertStatus(200);
        $response->assertViewIs('pelatihans.katalog');
    }

    public function test_katalog_only_shows_dibuka_pelatihan(): void
    {
        Pelatihan::factory()->create([
            'nama' => 'Pelatihan Dibuka',
            'status' => StatusPelatihan::Dibuka,
        ]);
        Pelatihan::factory()->create([
            'nama' => 'Pelatihan Draft',
            'status' => StatusPelatihan::Draft,
        ]);

        $response = $this->get(route('pelatihan.katalog'));

        $response->assertStatus(200);
        $response->assertSee('Pelatihan Dibuka');
        $response->assertDontSee('Pelatihan Draft');
    }

    public function test_katalog_search_by_nama(): void
    {
        Pelatihan::factory()->create([
            'nama' => 'Pelatihan Kanker Dasar',
            'status' => StatusPelatihan::Dibuka,
        ]);
        Pelatihan::factory()->create([
            'nama' => 'Pelatihan K3 Dasar',
            'status' => StatusPelatihan::Dibuka,
        ]);

        $response = $this->get(route('pelatihan.katalog', ['q' => 'kanker']));

        $response->assertStatus(200);
        $response->assertSee('Pelatihan Kanker Dasar');
        $response->assertDontSee('Pelatihan K3 Dasar');
    }

    public function test_katalog_filter_by_metode(): void
    {
        Pelatihan::factory()->create([
            'nama' => 'Pelatihan Luring',
            'status' => StatusPelatihan::Dibuka,
            'metode' => MetodePelatihan::Luring,
        ]);
        Pelatihan::factory()->create([
            'nama' => 'Pelatihan Daring',
            'status' => StatusPelatihan::Dibuka,
            'metode' => MetodePelatihan::Daring,
        ]);

        $response = $this->get(route('pelatihan.katalog', ['metode' => 'luring']));

        $response->assertStatus(200);
        $response->assertSee('Pelatihan Luring');
        $response->assertDontSee('Pelatihan Daring');
    }

    public function test_katalog_search_and_filter_combined(): void
    {
        Pelatihan::factory()->create([
            'nama' => 'Pelatihan Kanker Luring',
            'status' => StatusPelatihan::Dibuka,
            'metode' => MetodePelatihan::Luring,
        ]);
        Pelatihan::factory()->create([
            'nama' => 'Pelatihan Kanker Daring',
            'status' => StatusPelatihan::Dibuka,
            'metode' => MetodePelatihan::Daring,
        ]);
        Pelatihan::factory()->create([
            'nama' => 'Pelatihan K3 Luring',
            'status' => StatusPelatihan::Dibuka,
            'metode' => MetodePelatihan::Luring,
        ]);

        $response = $this->get(route('pelatihan.katalog', [
            'q' => 'kanker',
            'metode' => 'luring',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Pelatihan Kanker Luring');
        $response->assertDontSee('Pelatihan Kanker Daring');
        $response->assertDontSee('Pelatihan K3 Luring');
    }

    public function test_katalog_shows_kuota_terisi(): void
    {
        $pelatihan = Pelatihan::factory()->create([
            'nama' => 'Pelatihan Kuota',
            'status' => StatusPelatihan::Dibuka,
            'kuota' => 10,
        ]);
        Pendaftaran::factory()->count(3)->create([
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $response = $this->get(route('pelatihan.katalog'));

        $response->assertStatus(200);
        $response->assertSee('3 / 10');
    }

    public function test_katalog_shows_penuh_badge_when_full(): void
    {
        $pelatihan = Pelatihan::factory()->create([
            'nama' => 'Pelatihan Penuh',
            'status' => StatusPelatihan::Dibuka,
            'kuota' => 2,
        ]);
        Pendaftaran::factory()->count(2)->create([
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $response = $this->get(route('pelatihan.katalog'));

        $response->assertStatus(200);
        $response->assertSee('Penuh');
    }

    public function test_katalog_daftar_button_links_to_form(): void
    {
        $pelatihan = Pelatihan::factory()->create([
            'nama' => 'Pelatihan Tersedia',
            'status' => StatusPelatihan::Dibuka,
            'kuota' => 10,
        ]);

        $response = $this->get(route('pelatihan.katalog'));

        $response->assertStatus(200);
        $response->assertSee(route('pelatihan.daftar', $pelatihan));
    }

    public function test_katalog_shows_empty_state_when_no_results(): void
    {
        Pelatihan::factory()->create([
            'nama' => 'Pelatihan Lain',
            'status' => StatusPelatihan::Dibuka,
        ]);

        $response = $this->get(route('pelatihan.katalog', ['q' => 'tidak_ada']));

        $response->assertStatus(200);
        $response->assertSee('Tidak ada pelatihan ditemukan');
    }
}
