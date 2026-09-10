<?php

namespace Tests\Feature;

use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SertifikatTest extends TestCase
{
    use RefreshDatabase;

    protected User $peserta;

    protected User $pesertaLain;

    protected User $panitia;

    protected Pelatihan $pelatihan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->peserta = User::factory()->create(['role' => 'peserta']);
        $this->pesertaLain = User::factory()->create(['role' => 'peserta']);
        $this->panitia = User::factory()->create(['role' => 'panitia']);
        $this->pelatihan = Pelatihan::factory()->create([
            'status' => 'dibuka',
            'kuota' => 10,
            'tanggal_mulai' => '2026-08-01',
            'tanggal_selesai' => '2026-08-03',
        ]);
    }

    public function test_peserta_bisa_download_sertifikat_saat_syarat_terpenuhi(): void
    {
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'hadir_at' => now(),
            'data_diri' => ['nama' => 'Budi Santoso', 'nik' => '1234567890123456', 'kontak' => '08123456789', 'profesi' => 'Perawat', 'instansi' => 'RS Dr. Sardjito'],
        ]);

        $response = $this->actingAs($this->peserta)
            ->get(route('pendaftarans.sertifikat', $pendaftaran));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');

        // Pastikan kode_sertifikat digenerate
        $pendaftaran->refresh();
        $this->assertNotNull($pendaftaran->kode_sertifikat);
        $this->assertNotNull($pendaftaran->sertifikat_generated_at);
    }

    public function test_peserta_belum_presensi_dapat_pesan_jelas(): void
    {
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'hadir_at' => null, // Belum presensi
            'data_diri' => ['nama' => 'Budi Santoso', 'nik' => '1234567890123456', 'kontak' => '08123456789', 'profesi' => 'Perawat', 'instansi' => 'RS Dr. Sardjito'],
        ]);

        $response = $this->actingAs($this->peserta)
            ->get(route('pendaftarans.sertifikat', $pendaftaran));

        $response->assertStatus(200);
        $response->assertViewIs('pendaftarans.sertifikat_belum');
        $response->assertSee('Kehadiran belum tercatat');
    }

    public function test_peserta_pelatihan_belum_selesai_dapat_pesan_jelas(): void
    {
        $pelatihanAktif = Pelatihan::factory()->create([
            'status' => 'dibuka',
            'kuota' => 10,
            'tanggal_mulai' => '2026-10-01',
            'tanggal_selesai' => '2026-10-03',
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $pelatihanAktif->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'hadir_at' => now(),
            'data_diri' => ['nama' => 'Budi Santoso', 'nik' => '1234567890123456', 'kontak' => '08123456789', 'profesi' => 'Perawat', 'instansi' => 'RS Dr. Sardjito'],
        ]);

        $response = $this->actingAs($this->peserta)
            ->get(route('pendaftarans.sertifikat', $pendaftaran));

        $response->assertStatus(200);
        $response->assertViewIs('pendaftarans.sertifikat_belum');
        $response->assertSee('Pelatihan belum selesai');
    }

    public function test_peserta_belum_terverifikasi_dapat_pesan_jelas(): void
    {
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'pending',
            'data_diri' => ['nama' => 'Budi Santoso', 'nik' => '1234567890123456', 'kontak' => '08123456789', 'profesi' => 'Perawat', 'instansi' => 'RS Dr. Sardjito'],
        ]);

        $response = $this->actingAs($this->peserta)
            ->get(route('pendaftarans.sertifikat', $pendaftaran));

        $response->assertStatus(200);
        $response->assertViewIs('pendaftarans.sertifikat_belum');
        $response->assertSee('belum diverifikasi');
    }

    public function test_peserta_lain_tidak_bisa_akses_sertifikat(): void
    {
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->pesertaLain->id, // Milik peserta lain
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'hadir_at' => now(),
            'data_diri' => ['nama' => 'User Lain', 'nik' => '9999999999999999', 'kontak' => '0899999999', 'profesi' => 'Perawat', 'instansi' => 'RS Test'],
        ]);

        $response = $this->actingAs($this->peserta)
            ->get(route('pendaftarans.sertifikat', $pendaftaran));

        $response->assertForbidden();
    }

    public function test_panitia_tidak_bisa_akses_sertifikat(): void
    {
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'hadir_at' => now(),
            'data_diri' => ['nama' => 'Budi Santoso', 'nik' => '1234567890123456', 'kontak' => '08123456789', 'profesi' => 'Perawat', 'instansi' => 'RS Dr. Sardjito'],
        ]);

        $response = $this->actingAs($this->panitia)
            ->get(route('pendaftarans.sertifikat', $pendaftaran));

        $response->assertForbidden();
    }

    public function test_guest_redirect_ke_login(): void
    {
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'hadir_at' => now(),
            'data_diri' => ['nama' => 'Budi Santoso', 'nik' => '1234567890123456', 'kontak' => '08123456789', 'profesi' => 'Perawat', 'instansi' => 'RS Dr. Sardjito'],
        ]);

        $response = $this->get(route('pendaftarans.sertifikat', $pendaftaran));

        $response->assertRedirect(route('login'));
    }

    public function test_kode_sertifikat_unik_per_pendaftaran(): void
    {
        $pendaftaran1 = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'hadir_at' => now(),
            'data_diri' => ['nama' => 'User 1', 'nik' => '1111111111111111', 'kontak' => '0811111111', 'profesi' => 'Perawat', 'instansi' => 'RS Test'],
        ]);

        $pesertaBaru = User::factory()->create(['role' => 'peserta']);
        $pelatihanBaru = Pelatihan::factory()->create([
            'status' => 'dibuka',
            'kuota' => 10,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-07-03',
        ]);

        $pendaftaran2 = Pendaftaran::factory()->create([
            'peserta_id' => $pesertaBaru->id,
            'pelatihan_id' => $pelatihanBaru->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'hadir_at' => now(),
            'data_diri' => ['nama' => 'User 2', 'nik' => '2222222222222222', 'kontak' => '0822222222', 'profesi' => 'Dokter', 'instansi' => 'RS Lain'],
        ]);

        // Trigger generate kode
        $this->actingAs($this->peserta)->get(route('pendaftarans.sertifikat', $pendaftaran1));
        $this->actingAs($pesertaBaru)->get(route('pendaftarans.sertifikat', $pendaftaran2));

        $pendaftaran1->refresh();
        $pendaftaran2->refresh();

        $this->assertNotEquals($pendaftaran1->kode_sertifikat, $pendaftaran2->kode_sertifikat);
    }

    public function test_kode_sertifikat_tidak_berubah_saat_diakses_ulang(): void
    {
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'hadir_at' => now(),
            'data_diri' => ['nama' => 'Budi Santoso', 'nik' => '1234567890123456', 'kontak' => '08123456789', 'profesi' => 'Perawat', 'instansi' => 'RS Dr. Sardjito'],
        ]);

        // Akses pertama
        $this->actingAs($this->peserta)->get(route('pendaftarans.sertifikat', $pendaftaran));
        $pendaftaran->refresh();
        $kodePertama = $pendaftaran->kode_sertifikat;

        // Akses kedua
        $this->actingAs($this->peserta)->get(route('pendaftarans.sertifikat', $pendaftaran));
        $pendaftaran->refresh();
        $kodeKedua = $pendaftaran->kode_sertifikat;

        $this->assertEquals($kodePertama, $kodeKedua);
    }

    public function test_sertifikat_ditolak_tidak_bisa_download(): void
    {
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'ditolak',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'alasan_penolakan' => 'Data tidak lengkap',
            'data_diri' => ['nama' => 'Budi Santoso', 'nik' => '1234567890123456', 'kontak' => '08123456789', 'profesi' => 'Perawat', 'instansi' => 'RS Dr. Sardjito'],
        ]);

        $response = $this->actingAs($this->peserta)
            ->get(route('pendaftarans.sertifikat', $pendaftaran));

        $response->assertStatus(200);
        $response->assertViewIs('pendaftarans.sertifikat_belum');
    }
}
