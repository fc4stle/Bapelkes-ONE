<?php

namespace Tests\Feature;

use App\Models\Asrama;
use App\Models\Kamar;
use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservasiAsramaTest extends TestCase
{
    use RefreshDatabase;

    protected User $peserta;

    protected Pelatihan $pelatihan;

    protected Kamar $kamar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->peserta = User::factory()->create(['role' => 'peserta']);
        $this->pelatihan = Pelatihan::factory()->create(['status' => 'dibuka', 'kuota' => 10]);

        $asrama = Asrama::factory()->create(['nama' => 'Kunthi', 'kapasitas' => 40]);
        $this->kamar = Kamar::factory()->create([
            'asrama_id' => $asrama->id,
            'nomor_kamar' => 'KUN-001',
            'kapasitas' => 1,
        ]);
    }

    public function test_peserta_dapat_kamar_otomatis_saat_tersedia(): void
    {
        $response = $this->actingAs($this->peserta)
            ->post(route('pelatihans.daftar', $this->pelatihan), [
                'nama' => 'Budi Santoso',
                'nik' => '1234567890123456',
                'kontak' => '08123456789',
                'profesi' => 'Perawat',
                'instansi' => 'RS Dr. Sardjito',
                'butuh_asrama' => '1',
                'check_in' => '2026-10-01',
                'check_out' => '2026-10-03',
            ]);

        $response->assertRedirect(route('pendaftarans.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('pendaftarans', [
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'butuh_asrama' => true,
            'check_in' => '2026-10-01',
            'check_out' => '2026-10-03',
            'kamar_id' => $this->kamar->id,
        ]);
    }

    public function test_pesan_kamar_dialokasikan(): void
    {
        $this->actingAs($this->peserta)
            ->post(route('pelatihans.daftar', $this->pelatihan), [
                'nama' => 'Budi Santoso',
                'nik' => '1234567890123456',
                'kontak' => '08123456789',
                'profesi' => 'Perawat',
                'instansi' => 'RS Dr. Sardjito',
                'butuh_asrama' => '1',
                'check_in' => '2026-10-01',
                'check_out' => '2026-10-03',
            ]);

        $this->get(route('pendaftarans.index'))
            ->assertSee('KUN-001')
            ->assertSee('Kunthi');
    }

    public function test_pendaftaran_tetap_berhasil_tanpa_kamar_saat_asrama_penuh(): void
    {
        // Isi kamar dengan pendaftar lain
        $pesertaLain = User::factory()->create(['role' => 'peserta']);
        Pendaftaran::factory()->create([
            'peserta_id' => $pesertaLain->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'kamar_id' => $this->kamar->id,
            'check_in' => '2026-10-01',
            'check_out' => '2026-10-03',
            'butuh_asrama' => true,
        ]);

        $response = $this->actingAs($this->peserta)
            ->post(route('pelatihans.daftar', $this->pelatihan), [
                'nama' => 'Andi Wijaya',
                'nik' => '6543210987654321',
                'kontak' => '08765432109',
                'profesi' => 'Dokter',
                'instansi' => 'RSUP Dr. M. Djamil',
                'butuh_asrama' => '1',
                'check_in' => '2026-10-01',
                'check_out' => '2026-10-03',
            ]);

        $response->assertRedirect(route('pendaftarans.index'));
        $response->assertSessionHas('success');

        // Pendaftaran tetap ada tapi tanpa kamar
        $this->assertDatabaseHas('pendaftarans', [
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'butuh_asrama' => true,
            'kamar_id' => null,
        ]);
    }

    public function test_pesan_asrama_penuh_tampil_saat_kamar_habis(): void
    {
        // Isi kamar dengan pendaftar lain
        $pesertaLain = User::factory()->create(['role' => 'peserta']);
        Pendaftaran::factory()->create([
            'peserta_id' => $pesertaLain->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'kamar_id' => $this->kamar->id,
            'check_in' => '2026-10-01',
            'check_out' => '2026-10-03',
            'butuh_asrama' => true,
        ]);

        $this->actingAs($this->peserta)
            ->post(route('pelatihans.daftar', $this->pelatihan), [
                'nama' => 'Andi Wijaya',
                'nik' => '6543210987654321',
                'kontak' => '08765432109',
                'profesi' => 'Dokter',
                'instansi' => 'RSUP Dr. M. Djamil',
                'butuh_asrama' => '1',
                'check_in' => '2026-10-01',
                'check_out' => '2026-10-03',
            ]);

        $this->get(route('pendaftarans.index'))
            ->assertSee('asrama sudah penuh');
    }

    public function test_pendaftaran_tanpa_asrama_tidak_perlu_kamar(): void
    {
        $response = $this->actingAs($this->peserta)
            ->post(route('pelatihans.daftar', $this->pelatihan), [
                'nama' => 'Citra Dewi',
                'nik' => '1122334455667788',
                'kontak' => '081122334455',
                'profesi' => 'Bidan',
                'instansi' => 'Puskesmas Sewon',
                'butuh_asrama' => null,
            ]);

        $response->assertRedirect(route('pendaftarans.index'));

        $this->assertDatabaseHas('pendaftarans', [
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'butuh_asrama' => false,
            'kamar_id' => null,
            'check_in' => null,
            'check_out' => null,
        ]);
    }

    public function test_check_in_check_out_tersimpan_di_tabel(): void
    {
        $this->actingAs($this->peserta)
            ->post(route('pelatihans.daftar', $this->pelatihan), [
                'nama' => 'Budi Santoso',
                'nik' => '1234567890123456',
                'kontak' => '08123456789',
                'profesi' => 'Perawat',
                'instansi' => 'RS Dr. Sardjito',
                'butuh_asrama' => '1',
                'check_in' => '2026-10-01',
                'check_out' => '2026-10-03',
            ]);

        $this->assertDatabaseHas('pendaftarans', [
            'peserta_id' => $this->peserta->id,
            'check_in' => '2026-10-01',
            'check_out' => '2026-10-03',
        ]);
    }

    public function test_kamar_dengan_kapasitas_lebih_dapat_menampung_banyak(): void
    {
        // Hanya buat 1 kamar dengan kapasitas 2 (hapus kamar dari setUp dengan tidak menggunakannya)
        Kamar::query()->delete(); // Bersihkan kamar dari setUp

        $asrama = Asrama::factory()->create(['nama' => 'Srikandi', 'kapasitas' => 40]);
        $kamarKapasitas2 = Kamar::factory()->create([
            'asrama_id' => $asrama->id,
            'nomor_kamar' => 'SRI-001',
            'kapasitas' => 2,
        ]);

        // Pendaftar pertama
        $peserta1 = User::factory()->create(['role' => 'peserta']);
        $this->actingAs($peserta1)
            ->post(route('pelatihans.daftar', $this->pelatihan), [
                'nama' => 'Peserta Satu',
                'nik' => '1111111111111111',
                'kontak' => '081111111111',
                'profesi' => 'Perawat',
                'instansi' => 'RS A',
                'butuh_asrama' => '1',
                'check_in' => '2026-10-01',
                'check_out' => '2026-10-03',
            ]);

        // Pendaftar kedua - masih bisa dapat kamar yang sama karena kapasitas 2
        $peserta2 = User::factory()->create(['role' => 'peserta']);
        $response = $this->actingAs($peserta2)
            ->post(route('pelatihans.daftar', $this->pelatihan), [
                'nama' => 'Peserta Dua',
                'nik' => '2222222222222222',
                'kontak' => '082222222222',
                'profesi' => 'Dokter',
                'instansi' => 'RS B',
                'butuh_asrama' => '1',
                'check_in' => '2026-10-01',
                'check_out' => '2026-10-03',
            ]);

        $response->assertRedirect(route('pendaftarans.index'));

        // Kedua peserta dapat kamar yang sama
        $this->assertDatabaseHas('pendaftarans', [
            'peserta_id' => $peserta1->id,
            'kamar_id' => $kamarKapasitas2->id,
        ]);
        $this->assertDatabaseHas('pendaftarans', [
            'peserta_id' => $peserta2->id,
            'kamar_id' => $kamarKapasitas2->id,
        ]);
    }

    public function test_kamar_yang_sudah_penuh_tidak_dapat_dipesan_lagi(): void
    {
        $asrama = Asrama::factory()->create(['nama' => 'Srikandi', 'kapasitas' => 40]);
        $kamarPenuh = Kamar::factory()->create([
            'asrama_id' => $asrama->id,
            'nomor_kamar' => 'SRI-001',
            'kapasitas' => 1,
        ]);

        // Isi kamar
        $pesertaLain = User::factory()->create(['role' => 'peserta']);
        Pendaftaran::factory()->create([
            'peserta_id' => $pesertaLain->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'kamar_id' => $kamarPenuh->id,
            'check_in' => '2026-10-01',
            'check_out' => '2026-10-03',
            'butuh_asrama' => true,
        ]);

        // Peserta baru daftar - kamar ini tidak tersedia
        $response = $this->actingAs($this->peserta)
            ->post(route('pelatihans.daftar', $this->pelatihan), [
                'nama' => 'Peserta Baru',
                'nik' => '3333333333333333',
                'kontak' => '083333333333',
                'profesi' => 'Bidan',
                'instansi' => 'RS C',
                'butuh_asrama' => '1',
                'check_in' => '2026-10-01',
                'check_out' => '2026-10-03',
            ]);

        // Harusnya dapet kamar lain (kamar dari setUp)
        $response->assertRedirect(route('pendaftarans.index'));

        $this->assertDatabaseHas('pendaftarans', [
            'peserta_id' => $this->peserta->id,
            'kamar_id' => $this->kamar->id, // Dapat kamar lain, bukan yang penuh
        ]);
    }

    public function test_tanggal_berbeda_tidak_bertabrakan(): void
    {
        // Isi kamar untuk tanggal 1-3 Oktober
        $pesertaLain = User::factory()->create(['role' => 'peserta']);
        Pendaftaran::factory()->create([
            'peserta_id' => $pesertaLain->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'kamar_id' => $this->kamar->id,
            'check_in' => '2026-10-01',
            'check_out' => '2026-10-03',
            'butuh_asrama' => true,
        ]);

        // Peserta baru daftar untuk tanggal 5-7 (tidak overlap)
        $response = $this->actingAs($this->peserta)
            ->post(route('pelatihans.daftar', $this->pelatihan), [
                'nama' => 'Peserta Baru',
                'nik' => '4444444444444444',
                'kontak' => '084444444444',
                'profesi' => 'Apoteker',
                'instansi' => 'RS D',
                'butuh_asrama' => '1',
                'check_in' => '2026-10-05',
                'check_out' => '2026-10-07',
            ]);

        $response->assertRedirect(route('pendaftarans.index'));

        // Dapat kamar yang sama karena tanggal tidak overlap
        $this->assertDatabaseHas('pendaftarans', [
            'peserta_id' => $this->peserta->id,
            'kamar_id' => $this->kamar->id,
        ]);
    }

    public function test_concurrent_kamar_terakhir_hanya_satu_yang_berhasil(): void
    {
        // SQLite tidak mendukung row-level lock (lockForUpdate), skip test di SQLite
        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('lockForUpdate tidak efektif di SQLite, hanya di MySQL/PostgreSQL');
        }

        // Buat 2 user peserta baru untuk simulasi concurrent
        $peserta1 = User::factory()->create(['role' => 'peserta']);
        $peserta2 = User::factory()->create(['role' => 'peserta']);

        $checkIn = '2026-10-01';
        $checkOut = '2026-10-03';

        $scriptPath = __DIR__.'/concurrent_pendaftaran.php';

        // Jalankan 2 proses PHP secara paralel
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process1 = proc_open(
            "php {$scriptPath} {$peserta1->id} {$this->pelatihan->id} {$checkIn} {$checkOut}",
            $descriptors,
            $pipes1
        );

        $process2 = proc_open(
            "php {$scriptPath} {$peserta2->id} {$this->pelatihan->id} {$checkIn} {$checkOut}",
            $descriptors,
            $pipes2
        );

        // Ambil output dari kedua proses
        $output1 = stream_get_contents($pipes1[1]);
        $output2 = stream_get_contents($pipes2[1]);

        // Tutup pipes dan proses
        fclose($pipes1[0]);
        fclose($pipes1[1]);
        fclose($pipes1[2]);
        fclose($pipes2[0]);
        fclose($pipes2[1]);
        fclose($pipes2[2]);

        proc_close($process1);
        proc_close($process2);

        $result1 = json_decode($output1, true);
        $result2 = json_decode($output2, true);

        // Salah satu harus berhasil dapat kamar, satunya lagi tidak
        $hasKamar1 = $result1['has_kamar'] ?? false;
        $hasKamar2 = $result2['has_kamar'] ?? false;

        // XOR: hanya satu yang boleh dapat kamar
        $this->assertTrue(
            ($hasKamar1 && ! $hasKamar2) || (! $hasKamar1 && $hasKamar2),
            "Salah satu peserta harus gagal dapat kamar.\nPeserta 1: ".json_encode($result1)."\nPeserta 2: ".json_encode($result2)
        );

        // Verifikasi di database: hanya 1 pendaftaran yang punya kamar
        $kamarTerpakai = Pendaftaran::where('kamar_id', $this->kamar->id)
            ->where('check_in', $checkIn)
            ->where('check_out', $checkOut)
            ->count();

        $this->assertEquals(1, $kamarTerpakai, 'Hanya 1 pendaftaran yang boleh mendapat kamar untuk tanggal ini');
    }
}
