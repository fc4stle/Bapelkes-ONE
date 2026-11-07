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

    // TESTS UNTUK 3 BAGIAN BARU

    public function test_kartu_menunggu_verifikasi_hitung_benar(): void
    {
        // 3 pending di pelatihan 1, 2 pending di pelatihan 2
        $pelatihan2 = Pelatihan::factory()->create([
            'status' => 'dibuka',
            'kuota' => 50,
        ]);

        Pendaftaran::factory()->count(3)->create([
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'pending',
        ]);

        Pendaftaran::factory()->count(2)->create([
            'pelatihan_id' => $pelatihan2->id,
            'status_verifikasi' => 'pending',
        ]);

        $response = $this->actingAs($this->panitia)
            ->get(route('panitia.dashboard'));

        $response->assertStatus(200);

        // Total pending = 5
        $this->assertEquals(5, $response->viewData('total_pending'));

        // Pelatihan dengan pending terbanyak = pelatihan 1
        $pelatihan_pending_terbanyak = $response->viewData('pelatihan_pending_terbanyak');
        $this->assertEquals($this->pelatihan->id, $pelatihan_pending_terbanyak->id);
    }

    public function test_kartu_menunggu_verifikasi_kosong(): void
    {
        $response = $this->actingAs($this->panitia)
            ->get(route('panitia.dashboard'));

        $response->assertStatus(200);
        $this->assertEquals(0, $response->viewData('total_pending'));
        $response->assertSee('Tidak ada');
    }

    public function test_kartu_perhatian_kuota_hitung_benar(): void
    {
        // Pelatihan 1: 0 pendaftar
        // Pelatihan 2: 50 pendaftar dari 60 kuota (83.3%) - bukan perhatian
        // Pelatihan 3: 54 pendaftar dari 60 kuota (90%) - perhatian
        $pelatihan2 = Pelatihan::factory()->create([
            'status' => 'dibuka',
            'kuota' => 60,
        ]);

        $pelatihan3 = Pelatihan::factory()->create([
            'status' => 'dibuka',
            'kuota' => 60,
        ]);

        // 50 pendaftar di pelatihan 2
        Pendaftaran::factory()->count(50)->create([
            'pelatihan_id' => $pelatihan2->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        // 54 pendaftar di pelatihan 3
        Pendaftaran::factory()->count(54)->create([
            'pelatihan_id' => $pelatihan3->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $response = $this->actingAs($this->panitia)
            ->get(route('panitia.dashboard'));

        $response->assertStatus(200);

        // Perhatian: pelatihan 1 (0) + pelatihan 3 (90%) = 2
        $this->assertEquals(2, $response->viewData('pelatihan_perhatian'));
    }

    public function test_antrian_verifikasi_maksimal_5(): void
    {
        // 8 pending pendaftaran
        Pendaftaran::factory()->count(8)->create([
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'pending',
        ]);

        $response = $this->actingAs($this->panitia)
            ->get(route('panitia.dashboard'));

        $response->assertStatus(200);

        $antrian = $response->viewData('antrian_verifikasi');
        $this->assertCount(5, $antrian);
    }

    public function test_antrian_verifikasi_diurutkan_terlama(): void
    {
        $pendaftaran1 = Pendaftaran::factory()->create([
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'pending',
            'created_at' => now()->subDays(10),
        ]);

        $pendaftaran2 = Pendaftaran::factory()->create([
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'pending',
            'created_at' => now()->subDays(5),
        ]);

        $response = $this->actingAs($this->panitia)
            ->get(route('panitia.dashboard'));

        $response->assertStatus(200);

        $antrian = $response->viewData('antrian_verifikasi');
        $this->assertEquals($pendaftaran1->id, $antrian->first()->id);
        $this->assertEquals($pendaftaran2->id, $antrian->last()->id);
    }

    public function test_antrian_verifikasi_kosong(): void
    {
        $response = $this->actingAs($this->panitia)
            ->get(route('panitia.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Semua pendaftaran sudah diverifikasi');
    }

    public function test_hasil_tiyas_terbaru_hitung_benar(): void
    {
        $pelatihan_selesai = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_selesai' => now()->subDays(5),
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'pelatihan_id' => $pelatihan_selesai->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        // Buat 3 TIYAS responses
        $pendaftaran->tiyasResponse()->create([
            'identitas' => [
                'nama' => 'John Doe',
                'email' => 'john@example.com',
            ],
            'jawaban' => [
                'q1' => 'sangat setuju',
                'q2' => 'setuju',
            ],
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->panitia)
            ->get(route('panitia.dashboard'));

        $response->assertStatus(200);

        $tiyas = $response->viewData('tiyas_terbaru');
        $this->assertCount(1, $tiyas);
        $this->assertEquals($pelatihan_selesai->id, $tiyas->first()->id);
        $this->assertEquals(1, $tiyas->first()->tiyas_responses_count);
    }

    public function test_hasil_tiyas_kosong(): void
    {
        // Buat pelatihan selesai tanpa TIYAS
        Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_selesai' => now()->subDays(5),
        ]);

        $response = $this->actingAs($this->panitia)
            ->get(route('panitia.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Belum ada evaluasi TIYAS yang masuk');
    }

    public function test_pelatihan_tanpa_tiyas_hitung_benar(): void
    {
        $pelatihan_selesai_1 = Pelatihan::factory()->create([
            'status' => 'selesai',
        ]);

        $pelatihan_selesai_2 = Pelatihan::factory()->create([
            'status' => 'selesai',
        ]);

        // Pelatihan selesai 2 punya TIYAS
        $pendaftaran = Pendaftaran::factory()->create([
            'pelatihan_id' => $pelatihan_selesai_2->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $pendaftaran->tiyasResponse()->create([
            'identitas' => [
                'nama' => 'Jane Smith',
                'email' => 'jane@example.com',
            ],
            'jawaban' => [
                'q1' => 'sangat setuju',
            ],
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->panitia)
            ->get(route('panitia.dashboard'));

        $response->assertStatus(200);

        // Hanya pelatihan_selesai_1 yang tanpa TIYAS
        $this->assertEquals(1, $response->viewData('pelatihan_tanpa_tiyas'));
    }
}
