<?php

namespace Tests\Feature;

use App\Models\Pelatihan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PelatihanTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_200(): void
    {
        $response = $this->get(route('pelatihans.index'));

        $response->assertStatus(200);
        $response->assertViewIs('pelatihans.index');
    }

    public function test_index_shows_empty_state_when_no_data(): void
    {
        $response = $this->get(route('pelatihans.index'));

        $response->assertStatus(200);
        $response->assertSee('Belum ada pelatihan');
    }

    public function test_index_shows_pelatihan_data(): void
    {
        Pelatihan::factory()->create(['nama' => 'Pelatihan K3 Dasar']);

        $response = $this->get(route('pelatihans.index'));

        $response->assertStatus(200);
        $response->assertSee('Pelatihan K3 Dasar');
    }

    public function test_create_returns_200(): void
    {
        $response = $this->get(route('pelatihans.create'));

        $response->assertStatus(200);
        $response->assertViewIs('pelatihans.create');
    }

    public function test_store_with_valid_data(): void
    {
        $data = [
            'nama' => 'Pelatihan K3 Dasar',
            'deskripsi' => 'Pelatihan keselamatan kerja dasar',
            'tanggal_mulai' => '2026-10-01',
            'tanggal_selesai' => '2026-10-03',
            'lokasi' => 'Yogyakarta',
            'kuota' => 30,
            'status' => 'draft',
        ];

        $response = $this->post(route('pelatihans.store'), $data);

        $response->assertRedirect(route('pelatihans.index'));
        $this->assertDatabaseHas('pelatihans', ['nama' => 'Pelatihan K3 Dasar']);
    }

    public function test_store_with_invalid_data(): void
    {
        $response = $this->post(route('pelatihans.store'), [
            'nama' => '',
            'tanggal_mulai' => '',
            'tanggal_selesai' => '',
            'lokasi' => '',
            'kuota' => '',
            'status' => '',
        ]);

        $response->assertSessionHasErrors(['nama', 'tanggal_mulai', 'tanggal_selesai', 'lokasi', 'kuota', 'status']);
    }

    public function test_show_returns_200(): void
    {
        $pelatihan = Pelatihan::factory()->create();

        $response = $this->get(route('pelatihans.show', $pelatihan));

        $response->assertStatus(200);
        $response->assertViewIs('pelatihans.show');
        $response->assertSee($pelatihan->nama);
    }

    public function test_show_returns_404_for_missing_pelatihan(): void
    {
        $response = $this->get(route('pelatihans.show', ['pelatihan' => 99999]));

        $response->assertStatus(404);
    }

    public function test_edit_returns_200(): void
    {
        $pelatihan = Pelatihan::factory()->create();

        $response = $this->get(route('pelatihans.edit', $pelatihan));

        $response->assertStatus(200);
        $response->assertViewIs('pelatihans.edit');
    }

    public function test_update_with_valid_data(): void
    {
        $pelatihan = Pelatihan::factory()->create();

        $data = [
            'nama' => 'Pelatihan Updated',
            'deskripsi' => 'Deskripsi updated',
            'tanggal_mulai' => '2026-11-01',
            'tanggal_selesai' => '2026-11-05',
            'lokasi' => 'Jakarta',
            'kuota' => 50,
            'status' => 'dibuka',
        ];

        $response = $this->put(route('pelatihans.update', $pelatihan), $data);

        $response->assertRedirect(route('pelatihans.index'));
        $this->assertDatabaseHas('pelatihans', ['nama' => 'Pelatihan Updated']);
    }

    public function test_update_with_invalid_data(): void
    {
        $pelatihan = Pelatihan::factory()->create();

        $response = $this->put(route('pelatihans.update', $pelatihan), [
            'nama' => '',
            'tanggal_mulai' => '',
            'tanggal_selesai' => '',
            'lokasi' => '',
            'kuota' => '',
            'status' => '',
        ]);

        $response->assertSessionHasErrors(['nama', 'tanggal_mulai', 'tanggal_selesai', 'lokasi', 'kuota', 'status']);
    }

    public function test_update_returns_404_for_missing_pelatihan(): void
    {
        $response = $this->put(route('pelatihans.update', ['pelatihan' => 99999]), [
            'nama' => 'Pelatihan Updated',
            'tanggal_mulai' => '2026-11-01',
            'tanggal_selesai' => '2026-11-05',
            'lokasi' => 'Jakarta',
            'kuota' => 50,
            'status' => 'dibuka',
        ]);

        $response->assertStatus(404);
    }

    public function test_destroy_deletes_pelatihan(): void
    {
        $pelatihan = Pelatihan::factory()->create();

        $response = $this->delete(route('pelatihans.destroy', $pelatihan));

        $response->assertRedirect(route('pelatihans.index'));
        $this->assertDatabaseMissing('pelatihans', ['id' => $pelatihan->id]);
    }

    public function test_destroy_returns_404_for_missing_pelatihan(): void
    {
        $response = $this->delete(route('pelatihans.destroy', ['pelatihan' => 99999]));

        $response->assertStatus(404);
    }
}
