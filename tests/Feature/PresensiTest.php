<?php

namespace Tests\Feature;

use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\User;
use Carbon\Carbon;
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
        // Set test time agar berada dalam rentang tanggal pelatihan
        $tanggal_mulai = now()->startOfDay();
        Carbon::setTestNow($tanggal_mulai);

        $this->pelatihan->update([
            'tanggal_mulai' => $tanggal_mulai,
            'tanggal_selesai' => $tanggal_mulai->clone()->addDays(5),
        ]);

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
        // Set test time agar berada dalam rentang tanggal pelatihan
        $tanggal_mulai = now()->startOfDay();
        Carbon::setTestNow($tanggal_mulai);

        $this->pelatihan->update([
            'tanggal_mulai' => $tanggal_mulai,
            'tanggal_selesai' => $tanggal_mulai->clone()->addDays(5),
        ]);

        $response = $this->actingAs($this->panitia)
            ->post(route('pelatihan.presensi.proses', $this->pelatihan), [
                'kode_presensi' => 'KODE_TIDAK_ADA',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_presensi_gagal_kode_pelatihan_lain(): void
    {
        // Set test time agar berada dalam rentang tanggal pelatihan
        $tanggal_mulai = now()->startOfDay();
        Carbon::setTestNow($tanggal_mulai);

        $this->pelatihan->update([
            'tanggal_mulai' => $tanggal_mulai,
            'tanggal_selesai' => $tanggal_mulai->clone()->addDays(5),
        ]);

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
        // Set test time agar berada dalam rentang tanggal pelatihan
        $tanggal_mulai = now()->startOfDay();
        Carbon::setTestNow($tanggal_mulai);

        $this->pelatihan->update([
            'tanggal_mulai' => $tanggal_mulai,
            'tanggal_selesai' => $tanggal_mulai->clone()->addDays(5),
        ]);

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
        // Set test time agar berada dalam rentang tanggal pelatihan
        $tanggal_mulai = now()->startOfDay();
        Carbon::setTestNow($tanggal_mulai);

        $this->pelatihan->update([
            'tanggal_mulai' => $tanggal_mulai,
            'tanggal_selesai' => $tanggal_mulai->clone()->addDays(5),
        ]);

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
        // Set test time agar berada dalam rentang tanggal pelatihan
        $tanggal_mulai = now()->startOfDay();
        Carbon::setTestNow($tanggal_mulai);

        $this->pelatihan->update([
            'tanggal_mulai' => $tanggal_mulai,
            'tanggal_selesai' => $tanggal_mulai->clone()->addDays(5),
        ]);

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
        // Set test time agar berada dalam rentang tanggal pelatihan
        $tanggal_mulai = now()->startOfDay();
        Carbon::setTestNow($tanggal_mulai);

        $this->pelatihan->update([
            'tanggal_mulai' => $tanggal_mulai,
            'tanggal_selesai' => $tanggal_mulai->clone()->addDays(5),
        ]);

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
        // Set test time agar berada dalam rentang tanggal pelatihan
        $tanggal_mulai = now()->startOfDay();
        Carbon::setTestNow($tanggal_mulai);

        $this->pelatihan->update([
            'tanggal_mulai' => $tanggal_mulai,
            'tanggal_selesai' => $tanggal_mulai->clone()->addDays(5),
        ]);

        $response = $this->actingAs($this->panitia)
            ->post(route('pelatihan.presensi.proses', $this->pelatihan), [
                'kode_presensi' => '',
            ]);

        $response->assertSessionHasErrors('kode_presensi');
    }

    // ============== NEW TESTS FOR JSON RESPONSE & DATE VALIDATION ==============

    public function test_presensi_json_kode_valid_status_sukses(): void
    {
        // Set test time agar berada dalam rentang tanggal pelatihan
        $tanggal_mulai = now()->startOfDay();
        Carbon::setTestNow($tanggal_mulai);

        $this->pelatihan->update([
            'tanggal_mulai' => $tanggal_mulai,
            'tanggal_selesai' => $tanggal_mulai->clone()->addDays(5),
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'kode_presensi' => 'JSON_SUKSES_TEST',
            'kode_generated_at' => now(),
            'data_diri' => ['nama' => 'JSON Success', 'nik' => '4444444444444444', 'kontak' => '0844444444', 'profesi' => 'Perawat', 'instansi' => 'RS Test'],
        ]);

        $response = $this->actingAs($this->panitia)
            ->postJson(route('pelatihan.presensi.proses', $this->pelatihan), [
                'kode_presensi' => 'JSON_SUKSES_TEST',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'sukses',
            'nama' => 'JSON Success',
        ]);

        $pendaftaran->refresh();
        $this->assertTrue($pendaftaran->isHadir());
    }

    public function test_presensi_json_kode_sudah_hadir(): void
    {
        // Set test time agar berada dalam rentang tanggal pelatihan
        $tanggal_mulai = now()->startOfDay();
        Carbon::setTestNow($tanggal_mulai);

        $this->pelatihan->update([
            'tanggal_mulai' => $tanggal_mulai,
            'tanggal_selesai' => $tanggal_mulai->clone()->addDays(5),
        ]);

        $hadir_waktu_lalu = now()->subHours(1);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'kode_presensi' => 'JSON_SUDAH_HADIR',
            'kode_generated_at' => now(),
            'hadir_at' => $hadir_waktu_lalu,
            'data_diri' => ['nama' => 'Already Present', 'nik' => '5555555555555555', 'kontak' => '0855555555', 'profesi' => 'Perawat', 'instansi' => 'RS Test'],
        ]);

        $response = $this->actingAs($this->panitia)
            ->postJson(route('pelatihan.presensi.proses', $this->pelatihan), [
                'kode_presensi' => 'JSON_SUDAH_HADIR',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'sudah_hadir',
            'nama' => 'Already Present',
        ]);

        // Pastikan hadir_at tidak berubah
        $pendaftaran->refresh();
        $this->assertEquals($hadir_waktu_lalu->timestamp, $pendaftaran->hadir_at->timestamp);
    }

    public function test_presensi_json_kode_tidak_ada_404(): void
    {
        // Set test time agar berada dalam rentang tanggal pelatihan
        $tanggal_mulai = now()->startOfDay();
        Carbon::setTestNow($tanggal_mulai);

        $this->pelatihan->update([
            'tanggal_mulai' => $tanggal_mulai,
            'tanggal_selesai' => $tanggal_mulai->clone()->addDays(5),
        ]);

        $response = $this->actingAs($this->panitia)
            ->postJson(route('pelatihan.presensi.proses', $this->pelatihan), [
                'kode_presensi' => 'KODE_TIDAK_TERDAFTAR',
            ]);

        $response->assertStatus(404);
        $response->assertJson([
            'status' => 'error',
        ]);
    }

    public function test_presensi_json_belum_terverifikasi(): void
    {
        // Set test time agar berada dalam rentang tanggal pelatihan
        $tanggal_mulai = now()->startOfDay();
        Carbon::setTestNow($tanggal_mulai);

        $this->pelatihan->update([
            'tanggal_mulai' => $tanggal_mulai,
            'tanggal_selesai' => $tanggal_mulai->clone()->addDays(5),
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'pending',
            'kode_presensi' => 'UNVERIFIED_CODE',
            'kode_generated_at' => now(),
            'data_diri' => ['nama' => 'Unverified', 'nik' => '6666666666666666', 'kontak' => '0866666666', 'profesi' => 'Perawat', 'instansi' => 'RS Test'],
        ]);

        $response = $this->actingAs($this->panitia)
            ->postJson(route('pelatihan.presensi.proses', $this->pelatihan), [
                'kode_presensi' => $pendaftaran->kode_presensi,
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'error',
        ]);
    }

    public function test_presensi_sebelum_tanggal_mulai_ditolak(): void
    {
        // Set test time SEBELUM tanggal_mulai pelatihan
        $tanggal_mulai = now()->addDays(5)->startOfDay();
        $tanggal_test = $tanggal_mulai->clone()->subDays(1);
        Carbon::setTestNow($tanggal_test);

        $this->pelatihan->update([
            'tanggal_mulai' => $tanggal_mulai,
            'tanggal_selesai' => $tanggal_mulai->clone()->addDays(5),
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'kode_presensi' => 'BEFORE_START',
            'kode_generated_at' => now(),
            'data_diri' => ['nama' => 'Before Start', 'nik' => '7777777777777777', 'kontak' => '0877777777', 'profesi' => 'Perawat', 'instansi' => 'RS Test'],
        ]);

        $response = $this->actingAs($this->panitia)
            ->post(route('pelatihan.presensi.proses', $this->pelatihan), [
                'kode_presensi' => 'BEFORE_START',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', fn ($message) => str_contains($message, 'Presensi hanya bisa dicatat pada tanggal pelatihan'));

        $pendaftaran->refresh();
        $this->assertNull($pendaftaran->hadir_at);
    }

    public function test_presensi_sesudah_tanggal_selesai_ditolak(): void
    {
        // Set test time SESUDAH tanggal_selesai pelatihan
        $tanggal_mulai = now()->startOfDay();
        $tanggal_selesai = $tanggal_mulai->clone()->addDays(3);
        $tanggal_test = $tanggal_selesai->clone()->addDays(1);
        Carbon::setTestNow($tanggal_test);

        $this->pelatihan->update([
            'tanggal_mulai' => $tanggal_mulai,
            'tanggal_selesai' => $tanggal_selesai,
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'kode_presensi' => 'AFTER_END',
            'kode_generated_at' => now(),
            'data_diri' => ['nama' => 'After End', 'nik' => '8888888888888888', 'kontak' => '0888888888', 'profesi' => 'Perawat', 'instansi' => 'RS Test'],
        ]);

        $response = $this->actingAs($this->panitia)
            ->post(route('pelatihan.presensi.proses', $this->pelatihan), [
                'kode_presensi' => 'AFTER_END',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', fn ($message) => str_contains($message, 'Presensi hanya bisa dicatat pada tanggal pelatihan'));

        $pendaftaran->refresh();
        $this->assertNull($pendaftaran->hadir_at);
    }

    public function test_presensi_pada_hari_pertama_diterima(): void
    {
        // Set test time = hari pertama pelatihan
        $tanggal_mulai = now()->startOfDay();
        Carbon::setTestNow($tanggal_mulai);

        $this->pelatihan->update([
            'tanggal_mulai' => $tanggal_mulai,
            'tanggal_selesai' => $tanggal_mulai->clone()->addDays(5),
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'kode_presensi' => 'FIRST_DAY',
            'kode_generated_at' => now(),
            'data_diri' => ['nama' => 'First Day', 'nik' => '9999999999999999', 'kontak' => '0899999999', 'profesi' => 'Perawat', 'instansi' => 'RS Test'],
        ]);

        $response = $this->actingAs($this->panitia)
            ->post(route('pelatihan.presensi.proses', $this->pelatihan), [
                'kode_presensi' => 'FIRST_DAY',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $pendaftaran->refresh();
        $this->assertTrue($pendaftaran->isHadir());
    }

    public function test_presensi_pada_hari_terakhir_diterima(): void
    {
        // Set test time = hari terakhir pelatihan
        $tanggal_mulai = now()->subDays(3)->startOfDay();
        $tanggal_selesai = now()->startOfDay();
        Carbon::setTestNow($tanggal_selesai);

        $this->pelatihan->update([
            'tanggal_mulai' => $tanggal_mulai,
            'tanggal_selesai' => $tanggal_selesai,
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'kode_presensi' => 'LAST_DAY',
            'kode_generated_at' => now(),
            'data_diri' => ['nama' => 'Last Day', 'nik' => '1010101010101010', 'kontak' => '0810101010', 'profesi' => 'Perawat', 'instansi' => 'RS Test'],
        ]);

        $response = $this->actingAs($this->panitia)
            ->post(route('pelatihan.presensi.proses', $this->pelatihan), [
                'kode_presensi' => 'LAST_DAY',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $pendaftaran->refresh();
        $this->assertTrue($pendaftaran->isHadir());
    }

    public function test_presensi_peserta_dapat_403(): void
    {
        // Set test time agar berada dalam rentang tanggal pelatihan
        $tanggal_mulai = now()->startOfDay();
        Carbon::setTestNow($tanggal_mulai);

        $this->pelatihan->update([
            'tanggal_mulai' => $tanggal_mulai,
            'tanggal_selesai' => $tanggal_mulai->clone()->addDays(5),
        ]);

        $response = $this->actingAs($this->peserta)
            ->get(route('pelatihan.presensi', $this->pelatihan));

        $response->assertStatus(403);
    }

    public function test_presensi_endpoint_peserta_dapat_403(): void
    {
        // Set test time agar berada dalam rentang tanggal pelatihan
        $tanggal_mulai = now()->startOfDay();
        Carbon::setTestNow($tanggal_mulai);

        $this->pelatihan->update([
            'tanggal_mulai' => $tanggal_mulai,
            'tanggal_selesai' => $tanggal_mulai->clone()->addDays(5),
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'kode_presensi' => 'PESERTA_403',
            'kode_generated_at' => now(),
            'data_diri' => ['nama' => 'Peserta Test', 'nik' => '1111111111111111', 'kontak' => '0811111111', 'profesi' => 'Perawat', 'instansi' => 'RS Test'],
        ]);

        $response = $this->actingAs($this->peserta)
            ->post(route('pelatihan.presensi.proses', $this->pelatihan), [
                'kode_presensi' => 'PESERTA_403',
            ]);

        $response->assertStatus(403);
    }

    public function test_presensi_halaman_berisi_tombol_scan_dan_form_manual(): void
    {
        // Set test time agar berada dalam rentang tanggal pelatihan
        $tanggal_mulai = now()->startOfDay();
        Carbon::setTestNow($tanggal_mulai);

        $this->pelatihan->update([
            'tanggal_mulai' => $tanggal_mulai,
            'tanggal_selesai' => $tanggal_mulai->clone()->addDays(5),
        ]);

        $response = $this->actingAs($this->panitia)
            ->get(route('pelatihan.presensi', $this->pelatihan));

        $response->assertStatus(200);
        $response->assertSee('Mulai Scan');
        $response->assertSee('kode_presensi'); // Form input manual
    }

    public function test_presensi_mencatat_waktu_hadir(): void
    {
        // Set test time agar berada dalam rentang tanggal pelatihan
        $tanggal_mulai = now()->startOfDay();
        Carbon::setTestNow($tanggal_mulai);

        $this->pelatihan->update([
            'tanggal_mulai' => $tanggal_mulai,
            'tanggal_selesai' => $tanggal_mulai->clone()->addDays(5),
        ]);

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

    public function test_presensi_halaman_tidak_mengandung_dynamic_import_dan_menggunakan_vite_presensi_scan(): void
    {
        $response = $this->actingAs($this->panitia)
            ->get(route('pelatihan.presensi', $this->pelatihan));

        $response->assertStatus(200);
        // BUG 1 FIX: Pastikan tidak ada await import('html5-qrcode') di view
        $this->assertStringNotContainsString("import('html5-qrcode')", $response->getContent());
        // BUG 1 FIX: Pastikan ada referensi ke aset presensi-scan (Vite built)
        $response->assertSee('presensi-scan', escape: false);
    }
}
