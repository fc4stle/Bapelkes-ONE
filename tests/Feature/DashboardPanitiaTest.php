<?php

namespace Tests\Feature;

use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPanitiaTest extends TestCase
{
    use RefreshDatabase;

    protected User $panitia;

    protected User $peserta;

    protected Pelatihan $pelatihan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->panitia = User::factory()->create(['role' => 'panitia']);
        $this->peserta = User::factory()->create(['role' => 'peserta']);
        $this->pelatihan = Pelatihan::factory()->create([
            'status' => 'dibuka',
            'kuota' => 50,
            'tanggal_mulai' => '2026-08-01',
            'tanggal_selesai' => '2026-08-03',
        ]);
    }

    public function test_panitia_dapat_akses_dashboard(): void
    {
        $response = $this->actingAs($this->panitia)
            ->get(route('panitia.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('pelatihans.dashboard');
    }

    public function test_peserta_tidak_bisa_akses_dashboard(): void
    {
        $response = $this->actingAs($this->peserta)
            ->get(route('panitia.dashboard'));

        $response->assertForbidden();
    }

    public function test_guest_redirect_ke_login(): void
    {
        $response = $this->get(route('panitia.dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_dashboard_menampilkan_rekap_benar(): void
    {
        // 1 terverifikasi + hadir + asrama + sertifikat
        Pendaftaran::factory()->create([
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'hadir_at' => now(),
            'butuh_asrama' => true,
            'kode_sertifikat' => 'CERT-TEST1',
            'sertifikat_generated_at' => now(),
        ]);

        // 1 terverifikasi + hadir + tanpa asrama
        Pendaftaran::factory()->create([
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'hadir_at' => now(),
            'butuh_asrama' => false,
        ]);

        // 1 terverifikasi + belum hadir + asrama
        Pendaftaran::factory()->create([
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'hadir_at' => null,
            'butuh_asrama' => true,
        ]);

        // 1 terverifikasi + hadir + asrama + sertifikat
        Pendaftaran::factory()->create([
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'hadir_at' => now(),
            'butuh_asrama' => true,
            'kode_sertifikat' => 'CERT-TEST2',
            'sertifikat_generated_at' => now(),
        ]);

        // 2 menunggu verifikasi
        Pendaftaran::factory()->count(2)->create([
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'pending',
        ]);

        // 1 ditolak
        Pendaftaran::factory()->count(1)->create([
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'ditolak',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
        ]);

        $response = $this->actingAs($this->panitia)
            ->get(route('panitia.dashboard'));

        $response->assertStatus(200);

        // Total pendaftar: 2 + 1 + 1 + 2 + 1 = 7
        $pelatihans = $response->viewData('pelatihans');
        $firstPelatihan = $pelatihans->first();

        $this->assertEquals(7, $firstPelatihan->total_pendaftar);
        $this->assertEquals(4, $firstPelatihan->terverifikasi); // 2 + 1 + 1
        $this->assertEquals(2, $firstPelatihan->menunggu_verifikasi);
        $this->assertEquals(1, $firstPelatihan->ditolak);
        $this->assertEquals(3, $firstPelatihan->hadir); // 2 + 1
        $this->assertEquals(3, $firstPelatihan->butuh_asrama); // 2 + 1
        $this->assertEquals(2, $firstPelatihan->sertifikat_terunduh);
    }

    public function test_dashboard_menampilkan_nama_pelatihan(): void
    {
        $response = $this->actingAs($this->panitia)
            ->get(route('panitia.dashboard'));

        $response->assertStatus(200);
        $response->assertSee($this->pelatihan->nama);
    }

    public function test_dashboard_kosong_tampil_empty_state(): void
    {
        Pelatihan::query()->delete();

        $response = $this->actingAs($this->panitia)
            ->get(route('panitia.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Belum ada pelatihan');
    }

    public function test_rekap_multi_pelatihan(): void
    {
        $pelatihan2 = Pelatihan::factory()->create([
            'status' => 'dibuka',
            'kuota' => 30,
            'tanggal_mulai' => '2026-09-01',
            'tanggal_selesai' => '2026-09-03',
        ]);

        Pendaftaran::factory()->count(5)->create([
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        Pendaftaran::factory()->count(3)->create([
            'pelatihan_id' => $pelatihan2->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $response = $this->actingAs($this->panitia)
            ->get(route('panitia.dashboard'));

        $response->assertStatus(200);
        $response->assertSee($this->pelatihan->nama);
        $response->assertSee($pelatihan2->nama);
    }

    public function test_total_pendaftar_sesuai_database(): void
    {
        Pendaftaran::factory()->count(10)->create([
            'pelatihan_id' => $this->pelatihan->id,
        ]);

        $response = $this->actingAs($this->panitia)
            ->get(route('panitia.dashboard'));

        $response->assertStatus(200);

        $pelatihans = $response->viewData('pelatihans');
        $firstPelatihan = $pelatihans->first();

        $this->assertEquals(10, $firstPelatihan->total_pendaftar);
    }
}
