<?php

namespace Tests\Feature;

use App\Models\Asrama;
use App\Models\Kamar;
use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KartuPesertaTest extends TestCase
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
        $this->pelatihan = Pelatihan::factory()->create(['status' => 'dibuka', 'kuota' => 10]);
    }

    public function test_peserta_terverifikasi_bisa_lihat_kartu_sendiri(): void
    {
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'kode_presensi' => 'ABC123DEF4567890',
            'kode_generated_at' => now(),
            'data_diri' => [
                'nama' => 'Budi Santoso',
                'nik' => '1234567890123456',
                'kontak' => '08123456789',
                'profesi' => 'Perawat',
                'instansi' => 'RS Dr. Sardjito',
            ],
        ]);

        $response = $this->actingAs($this->peserta)
            ->get(route('pendaftarans.kartu', $pendaftaran));

        $response->assertStatus(200);
        $response->assertViewIs('pendaftarans.kartu');
        $response->assertSee('Budi Santoso');
        $response->assertSee('Kartu Peserta Digital');
        $response->assertSee('QR Code Presensi');
    }

    public function test_kartu_menampilkan_qr_code(): void
    {
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'kode_presensi' => 'TESTCODE12345678',
            'kode_generated_at' => now(),
            'data_diri' => ['nama' => 'Test User', 'nik' => '1111111111111111', 'kontak' => '0811111111', 'profesi' => 'Dokter', 'instansi' => 'RS Test'],
        ]);

        $response = $this->actingAs($this->peserta)
            ->get(route('pendaftarans.kartu', $pendaftaran));

        $response->assertStatus(200);
        // SVG QR code should be present
        $response->assertSee('<svg', false);
        $response->assertSee('</svg>', false);
    }

    public function test_kartu_menampilkan_info_asrama(): void
    {
        $asrama = Asrama::factory()->create(['nama' => 'Kunthi', 'kapasitas' => 40]);
        $kamar = Kamar::factory()->create([
            'asrama_id' => $asrama->id,
            'nomor_kamar' => 'KUN-001',
            'kapasitas' => 1,
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'kode_presensi' => 'TESTASRAMA123456',
            'kode_generated_at' => now(),
            'butuh_asrama' => true,
            'check_in' => '2026-10-01',
            'check_out' => '2026-10-03',
            'kamar_id' => $kamar->id,
            'data_diri' => ['nama' => 'Test User', 'nik' => '2222222222222222', 'kontak' => '0822222222', 'profesi' => 'Perawat', 'instansi' => 'RS Test'],
        ]);

        $response = $this->actingAs($this->peserta)
            ->get(route('pendaftarans.kartu', $pendaftaran));

        $response->assertStatus(200);
        $response->assertSee('Kunthi');
        $response->assertSee('KUN-001');
        $response->assertSee('01 Oct 2026');
        $response->assertSee('03 Oct 2026');
    }

    public function test_peserta_belum_terverifikasi_dapat_pesan_menunggu(): void
    {
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'pending',
            'data_diri' => ['nama' => 'Test User', 'nik' => '3333333333333333', 'kontak' => '0833333333', 'profesi' => 'Perawat', 'instansi' => 'RS Test'],
        ]);

        $response = $this->actingAs($this->peserta)
            ->get(route('pendaftarans.kartu', $pendaftaran));

        $response->assertStatus(200);
        $response->assertViewIs('pendaftarans.kartu');
        $response->assertSee('Menunggu Verifikasi');
        $response->assertDontSee('QR Code Presensi');
    }

    public function test_peserta_ditolak_dapat_pesan_penolakan(): void
    {
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'ditolak',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'alasan_penolakan' => 'Data tidak lengkap',
            'data_diri' => ['nama' => 'Test User', 'nik' => '4444444444444444', 'kontak' => '0844444444', 'profesi' => 'Perawat', 'instansi' => 'RS Test'],
        ]);

        $response = $this->actingAs($this->peserta)
            ->get(route('pendaftarans.kartu', $pendaftaran));

        $response->assertStatus(200);
        $response->assertViewIs('pendaftarans.kartu');
        $response->assertSee('Pendaftaran Ditolak');
        $response->assertSee('Data tidak lengkap');
    }

    public function test_peserta_tidak_bisa_lihat_kartu_peserta_lain(): void
    {
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->pesertaLain->id, // Milik peserta lain
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'kode_presensi' => 'OTHERUSER12345678',
            'kode_generated_at' => now(),
            'data_diri' => ['nama' => 'User Lain', 'nik' => '5555555555555555', 'kontak' => '0855555555', 'profesi' => 'Perawat', 'instansi' => 'RS Test'],
        ]);

        $response = $this->actingAs($this->peserta)
            ->get(route('pendaftarans.kartu', $pendaftaran));

        $response->assertForbidden();
    }

    public function test_panitia_tidak_bisa_lihat_kartu_peserta(): void
    {
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'kode_presensi' => 'PANITIATIDAKBOLEH',
            'kode_generated_at' => now(),
            'data_diri' => ['nama' => 'Test User', 'nik' => '6666666666666666', 'kontak' => '0866666666', 'profesi' => 'Perawat', 'instansi' => 'RS Test'],
        ]);

        $response = $this->actingAs($this->panitia)
            ->get(route('pendaftarans.kartu', $pendaftaran));

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
            'kode_presensi' => 'GUESTTEST123456789',
            'kode_generated_at' => now(),
            'data_diri' => ['nama' => 'Test User', 'nik' => '7777777777777777', 'kontak' => '0877777777', 'profesi' => 'Perawat', 'instansi' => 'RS Test'],
        ]);

        $response = $this->get(route('pendaftarans.kartu', $pendaftaran));

        $response->assertRedirect(route('login'));
    }

    public function test_kode_presensi_digenerate_saat_verifikasi(): void
    {
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'pending',
            'data_diri' => ['nama' => 'Test User', 'nik' => '8888888888888888', 'kontak' => '0888888888', 'profesi' => 'Perawat', 'instansi' => 'RS Test'],
        ]);

        // Verify the registration
        $response = $this->actingAs($this->panitia)
            ->patch(route('pendaftarans.verifikasi', $pendaftaran));

        $response->assertRedirect();

        $pendaftaran->refresh();
        $this->assertEquals('diverifikasi', $pendaftaran->status_verifikasi);
        $this->assertNotNull($pendaftaran->kode_presensi);
        $this->assertNotNull($pendaftaran->kode_generated_at);
        $this->assertEquals($this->panitia->id, $pendaftaran->verified_by);
    }

    public function test_kode_presensi_berbeda_untuk_setiap_pendaftaran(): void
    {
        $pendaftaran1 = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'pending',
            'data_diri' => ['nama' => 'User 1', 'nik' => '9999999999999999', 'kontak' => '0899999999', 'profesi' => 'Perawat', 'instansi' => 'RS Test'],
        ]);

        $pesertaBaru = User::factory()->create(['role' => 'peserta']);
        $pelatihanBaru = Pelatihan::factory()->create(['status' => 'dibuka', 'kuota' => 10]);

        $pendaftaran2 = Pendaftaran::factory()->create([
            'peserta_id' => $pesertaBaru->id,
            'pelatihan_id' => $pelatihanBaru->id,
            'status_verifikasi' => 'pending',
            'data_diri' => ['nama' => 'User 2', 'nik' => '0000000000000000', 'kontak' => '0800000000', 'profesi' => 'Dokter', 'instansi' => 'RS Lain'],
        ]);

        $this->actingAs($this->panitia)
            ->patch(route('pendaftarans.verifikasi', $pendaftaran1));

        $this->actingAs($this->panitia)
            ->patch(route('pendaftarans.verifikasi', $pendaftaran2));

        $pendaftaran1->refresh();
        $pendaftaran2->refresh();

        $this->assertNotEquals($pendaftaran1->kode_presensi, $pendaftaran2->kode_presensi);
    }

    public function test_kartu_menampilkan_pesan_asrama_penuh(): void
    {
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'kode_presensi' => 'ASRAMAPENUH123456',
            'kode_generated_at' => now(),
            'butuh_asrama' => true,
            'kamar_id' => null, // Asrama penuh
            'data_diri' => ['nama' => 'Test User', 'nik' => '1212121212121212', 'kontak' => '0812121212', 'profesi' => 'Perawat', 'instansi' => 'RS Test'],
        ]);

        $response = $this->actingAs($this->peserta)
            ->get(route('pendaftarans.kartu', $pendaftaran));

        $response->assertStatus(200);
        $response->assertSee('Asrama penuh');
    }

    public function test_kartu_menampilkan_kode_presensi(): void
    {
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'kode_presensi' => 'KODETEST123456789',
            'kode_generated_at' => now(),
            'data_diri' => ['nama' => 'Test User', 'nik' => '1313131313131313', 'kontak' => '0813131313', 'profesi' => 'Perawat', 'instansi' => 'RS Test'],
        ]);

        $response = $this->actingAs($this->peserta)
            ->get(route('pendaftarans.kartu', $pendaftaran));

        $response->assertStatus(200);
        $response->assertSee('KODETEST123456789');
    }
}
