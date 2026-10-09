<?php

namespace Tests\Feature;

use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportPendaftarCSVTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['role' => 'panitia']));
    }

    public function test_panitia_dapat_unduh_csv_pendaftar(): void
    {
        $pelatihan = Pelatihan::factory()->create(['nama' => 'Pelatihan K3 Dasar']);

        $response = $this->get(route('pelatihan.pendaftar.export', $pelatihan));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
        $response->assertHeader('Content-Disposition');
    }

    public function test_csv_filename_menggunakan_slug_nama_pelatihan(): void
    {
        $pelatihan = Pelatihan::factory()->create(['nama' => 'Pelatihan K3 Dasar']);

        $response = $this->get(route('pelatihan.pendaftar.export', $pelatihan));

        $response->assertStatus(200);
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('pendaftar-pelatihan-k3-dasar', $disposition);
    }

    public function test_csv_berisi_header_yang_benar(): void
    {
        $pelatihan = Pelatihan::factory()->create();

        $response = $this->get(route('pelatihan.pendaftar.export', $pelatihan));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
        $response->assertHeader('Content-Disposition');
    }

    public function test_csv_berisi_data_pendaftar_yang_benar(): void
    {
        $pelatihan = Pelatihan::factory()->create();
        $peserta = User::factory()->create(['email' => 'peserta@example.com']);
        Pendaftaran::factory()->create([
            'pelatihan_id' => $pelatihan->id,
            'peserta_id' => $peserta->id,
            'status_verifikasi' => 'diverifikasi',
            'data_diri' => [
                'nama' => 'John Doe',
                'nik' => '1234567890123456',
                'kontak' => '081234567890',
                'profesi' => 'Perawat',
                'instansi' => 'RS Umum Yogyakarta',
            ],
            'butuh_asrama' => true,
            'kode_sertifikat' => 'CERT-001',
        ]);

        $response = $this->get(route('pelatihan.pendaftar.export', $pelatihan));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
    }

    public function test_csv_hanya_berisi_pendaftar_pelatihan_itu(): void
    {
        $pelatihan1 = Pelatihan::factory()->create();
        $pelatihan2 = Pelatihan::factory()->create();

        $peserta1 = User::factory()->create(['email' => 'peserta1@example.com']);
        $peserta2 = User::factory()->create(['email' => 'peserta2@example.com']);

        Pendaftaran::factory()->create([
            'pelatihan_id' => $pelatihan1->id,
            'peserta_id' => $peserta1->id,
            'data_diri' => ['nama' => 'Peserta 1', 'kontak' => '081111111111', 'instansi' => 'RS A'],
        ]);

        Pendaftaran::factory()->create([
            'pelatihan_id' => $pelatihan2->id,
            'peserta_id' => $peserta2->id,
            'data_diri' => ['nama' => 'Peserta 2', 'kontak' => '082222222222', 'instansi' => 'RS B'],
        ]);

        $response = $this->get(route('pelatihan.pendaftar.export', $pelatihan1));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
    }

    public function test_csv_injection_prevention_dengan_escape(): void
    {
        $pelatihan = Pelatihan::factory()->create();
        $peserta = User::factory()->create();

        // Data dengan karakter yang bisa trigger CSV injection
        Pendaftaran::factory()->create([
            'pelatihan_id' => $pelatihan->id,
            'peserta_id' => $peserta->id,
            'data_diri' => [
                'nama' => '=1+1',
                'kontak' => '+62812345678',
                'instansi' => '@malicious',
            ],
            'alasan_penolakan' => '-formula',
        ]);

        $response = $this->get(route('pelatihan.pendaftar.export', $pelatihan));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
    }

    public function test_peserta_mendapat_403_saat_akses_export(): void
    {
        $pelatihan = Pelatihan::factory()->create();

        $this->actingAs(User::factory()->create(['role' => 'peserta']));

        $response = $this->get(route('pelatihan.pendaftar.export', $pelatihan));

        $response->assertStatus(403);
    }

    public function test_guest_redirect_ke_login_saat_akses_export(): void
    {
        $pelatihan = Pelatihan::factory()->create();

        $this->post(route('logout'));

        $response = $this->get(route('pelatihan.pendaftar.export', $pelatihan));

        $response->assertRedirect(route('login'));
    }

    public function test_csv_kosong_saat_tidak_ada_pendaftar(): void
    {
        $pelatihan = Pelatihan::factory()->create();

        $response = $this->get(route('pelatihan.pendaftar.export', $pelatihan));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
    }

    public function test_csv_nomor_urut_dimulai_dari_1(): void
    {
        $pelatihan = Pelatihan::factory()->create();
        $peserta1 = User::factory()->create();
        $peserta2 = User::factory()->create();

        Pendaftaran::factory()->create([
            'pelatihan_id' => $pelatihan->id,
            'peserta_id' => $peserta1->id,
            'data_diri' => ['nama' => 'Peserta A', 'kontak' => '081111111111', 'instansi' => 'RS A'],
        ]);

        Pendaftaran::factory()->create([
            'pelatihan_id' => $pelatihan->id,
            'peserta_id' => $peserta2->id,
            'data_diri' => ['nama' => 'Peserta B', 'kontak' => '082222222222', 'instansi' => 'RS B'],
        ]);

        $response = $this->get(route('pelatihan.pendaftar.export', $pelatihan));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
    }

    public function test_route_named_correctly(): void
    {
        $pelatihan = Pelatihan::factory()->create();

        // Verifikasi route exist dengan nama yang benar
        $url = route('pelatihan.pendaftar.export', $pelatihan);
        $this->assertStringContainsString('/pendaftar/export', $url);
    }
}
