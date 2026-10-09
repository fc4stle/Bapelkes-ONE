<?php

namespace Tests\Feature;

use App\Models\Pelatihan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersyaratanPelatihanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['role' => 'panitia']));
    }

    public function test_panitia_dapat_mengisi_persyaratan_saat_membuat_pelatihan(): void
    {
        $data = [
            'nama' => 'Pelatihan K3 Dasar',
            'deskripsi' => 'Pelatihan keselamatan kerja dasar',
            'persyaratan' => 'Peserta adalah tenaga kesehatan bersertifikat dengan pengalaman minimal 2 tahun',
            'tanggal_mulai' => '2026-10-01',
            'tanggal_selesai' => '2026-10-03',
            'lokasi' => 'Yogyakarta',
            'kuota' => 30,
            'metode' => 'luring',
            'status' => 'draft',
        ];

        $response = $this->post(route('pelatihans.store'), $data);

        $response->assertRedirect(route('pelatihans.index'));
        $this->assertDatabaseHas('pelatihans', [
            'nama' => 'Pelatihan K3 Dasar',
            'persyaratan' => 'Peserta adalah tenaga kesehatan bersertifikat dengan pengalaman minimal 2 tahun',
        ]);
    }

    public function test_panitia_dapat_mengubah_persyaratan(): void
    {
        $pelatihan = Pelatihan::factory()->create([
            'persyaratan' => 'Syarat lama',
        ]);

        $data = [
            'nama' => $pelatihan->nama,
            'deskripsi' => $pelatihan->deskripsi,
            'persyaratan' => 'Syarat baru yang lebih detail dan komprehensif',
            'tanggal_mulai' => $pelatihan->tanggal_mulai->format('Y-m-d'),
            'tanggal_selesai' => $pelatihan->tanggal_selesai->format('Y-m-d'),
            'lokasi' => $pelatihan->lokasi,
            'kuota' => $pelatihan->kuota,
            'metode' => $pelatihan->metode->value,
            'status' => $pelatihan->status->value,
        ];

        $response = $this->put(route('pelatihans.update', $pelatihan), $data);

        $response->assertRedirect(route('pelatihans.index'));
        $this->assertDatabaseHas('pelatihans', [
            'id' => $pelatihan->id,
            'persyaratan' => 'Syarat baru yang lebih detail dan komprehensif',
        ]);
    }

    public function test_persyaratan_nullable_saat_membuat_pelatihan(): void
    {
        $data = [
            'nama' => 'Pelatihan Tanpa Syarat',
            'deskripsi' => 'Pelatihan tanpa persyaratan khusus',
            'tanggal_mulai' => '2026-10-01',
            'tanggal_selesai' => '2026-10-03',
            'lokasi' => 'Yogyakarta',
            'kuota' => 30,
            'metode' => 'luring',
            'status' => 'draft',
        ];

        $response = $this->post(route('pelatihans.store'), $data);

        $response->assertRedirect(route('pelatihans.index'));
        $this->assertDatabaseHas('pelatihans', [
            'nama' => 'Pelatihan Tanpa Syarat',
            'persyaratan' => null,
        ]);
    }

    public function test_persyaratan_max_2000_karakter(): void
    {
        $longText = str_repeat('a', 2001);

        $data = [
            'nama' => 'Pelatihan Test',
            'deskripsi' => 'Test',
            'persyaratan' => $longText,
            'tanggal_mulai' => '2026-10-01',
            'tanggal_selesai' => '2026-10-03',
            'lokasi' => 'Yogyakarta',
            'kuota' => 30,
            'metode' => 'luring',
            'status' => 'draft',
        ];

        $response = $this->post(route('pelatihans.store'), $data);

        $response->assertSessionHasErrors(['persyaratan']);
    }

    public function test_peserta_dapat_melihat_persyaratan_di_halaman_detail(): void
    {
        $pelatihan = Pelatihan::factory()->create([
            'persyaratan' => 'Peserta harus memiliki sertifikat resmi dan pengalaman 2 tahun',
            'status' => 'dibuka',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'peserta']));

        $response = $this->get(route('pelatihan.detail', $pelatihan));

        $response->assertStatus(200);
        $response->assertSee('Persyaratan');
        $response->assertSee('Peserta harus memiliki sertifikat resmi dan pengalaman 2 tahun');
    }

    public function test_peserta_tidak_melihat_persyaratan_jika_kosong(): void
    {
        $pelatihan = Pelatihan::factory()->create([
            'persyaratan' => null,
            'status' => 'dibuka',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'peserta']));

        $response = $this->get(route('pelatihan.detail', $pelatihan));

        $response->assertStatus(200);
        $response->assertDontSee('Persyaratan');
    }

    public function test_panitia_dapat_melihat_persyaratan_di_halaman_show(): void
    {
        $pelatihan = Pelatihan::factory()->create([
            'persyaratan' => 'Syarat yang ditampilkan di halaman show panitia',
        ]);

        $response = $this->get(route('pelatihans.show', $pelatihan));

        $response->assertStatus(200);
        $response->assertSee('Persyaratan');
        $response->assertSee('Syarat yang ditampilkan di halaman show panitia');
    }

    public function test_panitia_melihat_dash_jika_persyaratan_kosong_di_halaman_show(): void
    {
        $pelatihan = Pelatihan::factory()->create([
            'persyaratan' => null,
        ]);

        $response = $this->get(route('pelatihans.show', $pelatihan));

        $response->assertStatus(200);
        $response->assertSee('Persyaratan');
        $response->assertSee('-');
    }
}
