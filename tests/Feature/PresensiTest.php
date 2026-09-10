<?php

namespace Tests\Feature;

use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresensiTest extends TestCase
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
        $this->pelatihan = Pelatihan::factory()->create(['status' => 'dibuka', 'kuota' => 10]);
    }

    public function test_panitia_dapat_akses_halaman_presensi(): void
    {
        $response = $this->actingAs($this->panitia)
            ->get(route('pelatihan.presensi', $this->pelatihan));

        $response->assertStatus(200);
        $response->assertViewIs('pelatihans.presensi');
    }

    public function test_peserta_tidak_bisa_akses_halaman_presensi(): void
    {
        $response = $this->actingAs($this->peserta)
            ->get(route('pelatihan.presensi', $this->pelatihan));

        $response->assertForbidden();
    }

    public function test_guest_redirect_ke_login(): void
    {
        $response = $this->get(route('pelatihan.presensi', $this->pelatihan));
        $response->assertRedirect(route('login'));
    }

    public function test_presensi_berhasil_untuk_kode_valid(): void
    {
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'kode_presensi' => 'KODETEST123456789',
            'kode_generated_at' => now(),
            'data_diri' => ['nama' => 'Budi Santoso', 'nik' => '1234567890123456', 'kontak' => '08123456789', 'profesi' => 'Perawat', 'instansi' => 'RS Dr. Sardjito'],
        ]);

        $response = $this->actingAs($this->panitia)
            ->post(route('pelatihan.presensi.proses', $this->pelatihan), [
                'kode_presensi' => 'KODETEST123456789',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $pendaftaran->refresh();
        $this->assertNotNull($pendaftaran->hadir_at);
        $this->assertTrue($pendaftaran->isHadir());
    }

    public function test_presensi_gagal_kode_tidak_ditemukan(): void
    {
        $response = $this->actingAs($this->panitia)
            ->post(route('pelatihan.presensi.proses', $this->pelatihan), [
                'kode_presensi' => 'KODE_TIDAK_ADA',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_presensi_gagal_kode_pelatihan_lain(): void
    {
        $pelatihanLain = Pelatihan::factory()->create(['status' => 'dibuka', 'kuota' => 10]);
        $pendaftaranLain = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $pelatihanLain->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'kode_presensi' => 'KODE_PELATIHAN_LAIN',
            'kode_generated_at' => now(),
            'data_diri' => ['nama' => 'Budi Santoso', 'nik' => '1234567890123456', 'kontak' => '08123456789', 'profesi' => 'Perawat', 'instansi' => 'RS Dr. Sardjito'],
        ]);

        // Coba presensi di pelatihan yang berbeda
        $response = $this->actingAs($this->panitia)
            ->post(route('pelatihan.presensi.proses', $this->pelatihan), [
                'kode_presensi' => 'KODE_PELATIHAN_LAIN',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Pastikan tidak ada presensi yang tercatat untuk pelatihan ini
        $this->assertDatabaseMissing('pendaftarans', [
            'pelatihan_id' => $this->pelatihan->id,
            'kode_presensi' => 'KODE_PELATIHAN_LAIN',
            'hadir_at' => ! null,
        ]);
    }

    public function test_presensi_ganda_tidak_dapat_terjadi(): void
    {
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'kode_presensi' => 'KODE_GANDA_TEST',
            'kode_generated_at' => now(),
            'hadir_at' => now()->subMinutes(30), // Sudah presensi sebelumnya
            'data_diri' => ['nama' => 'Citra Dewi', 'nik' => '9876543210987654', 'kontak' => '08987654321', 'profesi' => 'Bidan', 'instansi' => 'Puskesmas Sewon'],
        ]);

        $response = $this->actingAs($this->panitia)
            ->post(route('pelatihan.presensi.proses', $this->pelatihan), [
                'kode_presensi' => 'KODE_GANDA_TEST',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('warning');

        // Pastikan hadir_at tidak berubah (masih waktu sebelumnya)
        $pendaftaran->refresh();
        $this->assertNotNull($pendaftaran->hadir_at);
        $this->assertTrue($pendaftaran->hadir_at->lt(now()->subMinutes(29)));
    }

    public function test_presensi_gagal_belum_terverifikasi(): void
    {
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'pending',
            'data_diri' => ['nama' => 'Test User', 'nik' => '1111111111111111', 'kontak' => '0811111111', 'profesi' => 'Perawat', 'instansi' => 'RS Test'],
        ]);

        $response = $this->actingAs($this->panitia)
            ->post(route('pelatihan.presensi.proses', $this->pelatihan), [
                'kode_presensi' => $pendaftaran->kode_presensi ?? 'KODE_BELUM_VERIFIKASI',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_presensi_dengan_kode_kecil_dan_spasi(): void
    {
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'kode_presensi' => 'MIXEDCASE12345678',
            'kode_generated_at' => now(),
            'data_diri' => ['nama' => 'Test Case', 'nik' => '2222222222222222', 'kontak' => '0822222222', 'profesi' => 'Perawat', 'instansi' => 'RS Test'],
        ]);

        // Kirim dengan lowercase dan spasi di awal/akhir
        $response = $this->actingAs($this->panitia)
            ->post(route('pelatihan.presensi.proses', $this->pelatihan), [
                'kode_presensi' => '  mixedcase12345678  ',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $pendaftaran->refresh();
        $this->assertTrue($pendaftaran->isHadir());
    }

    public function test_presensi_pesan_sukses_menampilkan_nama(): void
    {
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'kode_presensi' => 'NAMA_CHECK12345678',
            'kode_generated_at' => now(),
            'data_diri' => ['nama' => 'Rina Wijaya', 'nik' => '3333333333333333', 'kontak' => '0833333333', 'profesi' => 'Perawat', 'instansi' => 'RS Test'],
        ]);

        $response = $this->actingAs($this->panitia)
            ->post(route('pelatihan.presensi.proses', $this->pelatihan), [
                'kode_presensi' => 'NAMA_CHECK12345678',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $response->assertSessionHas('success', fn ($message) => str_contains($message, 'Rina Wijaya'));
    }

    public function test_presensi_tanpa_kode_gagal(): void
    {
        $response = $this->actingAs($this->panitia)
            ->post(route('pelatihan.presensi.proses', $this->pelatihan), [
                'kode_presensi' => '',
            ]);

        $response->assertSessionHasErrors('kode_presensi');
    }

    public function test_presensi_mencatat_waktu_hadir(): void
    {
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'kode_presensi' => 'WAKTU_HADIR12345678',
            'kode_generated_at' => now(),
            'data_diri' => ['nama' => 'Waktu Hadir', 'nik' => '4444444444444444', 'kontak' => '0844444444', 'profesi' => 'Perawat', 'instansi' => 'RS Test'],
        ]);

        $sekarang = now();
        $this->actingAs($this->panitia)
            ->post(route('pelatihan.presensi.proses', $this->pelatihan), [
                'kode_presensi' => 'WAKTU_HADIR12345678',
            ]);

        $pendaftaran->refresh();
        $this->assertNotNull($pendaftaran->hadir_at);

        // Pastikan waktu hadir mendekati waktu sekarang (dalam 5 detik)
        $this->assertTrue($pendaftaran->hadir_at->diffInSeconds($sekarang) < 5);
    }
}
