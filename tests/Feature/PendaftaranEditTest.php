<?php

namespace Tests\Feature;

use App\Models\Asrama;
use App\Models\Kamar;
use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PendaftaranEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_peserta_dapat_melihat_form_edit_pendaftaran_sendiri(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'status_verifikasi' => 'pending',
            'data_diri' => ['nama' => 'Budi Santoso'],
        ]);

        $response = $this->actingAs($peserta)->get(route('pendaftarans.edit', $pendaftaran));

        $response->assertStatus(200);
        $response->assertSee('Budi Santoso');
    }

    public function test_peserta_tidak_dapat_melihat_form_edit_pendaftaran_orang_lain(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $other = User::factory()->create(['role' => 'peserta']);
        $pendaftaran = Pendaftaran::factory()->create(['peserta_id' => $other->id]);

        $response = $this->actingAs($peserta)->get(route('pendaftarans.edit', $pendaftaran));

        $response->assertForbidden();
    }

    public function test_peserta_dapat_mengubah_data_diri_pendaftaran_yang_masih_pending(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'status_verifikasi' => 'pending',
            'data_diri' => ['nama' => 'Nama Lama', 'nik' => '1111111111111111', 'kontak' => '081', 'profesi' => 'Perawat', 'instansi' => 'RS Lama'],
        ]);

        $response = $this->actingAs($peserta)->put(route('pendaftarans.updateSelf', $pendaftaran), [
            'nama' => 'Nama Baru',
            'nik' => '2222222222222222',
            'kontak' => '082',
            'profesi' => 'Dokter',
            'instansi' => 'RS Baru',
        ]);

        $response->assertRedirect(route('pendaftarans.index'));
        $response->assertSessionHas('success');

        $pendaftaran->refresh();
        $this->assertSame('Nama Baru', $pendaftaran->data_diri['nama']);
        $this->assertSame('RS Baru', $pendaftaran->data_diri['instansi']);
    }

    public function test_pendaftaran_yang_sudah_diverifikasi_tidak_dapat_diubah(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $editResponse = $this->actingAs($peserta)->get(route('pendaftarans.edit', $pendaftaran));
        $editResponse->assertRedirect();
        $editResponse->assertSessionHas('error');

        $updateResponse = $this->actingAs($peserta)->put(route('pendaftarans.updateSelf', $pendaftaran), [
            'nama' => 'Mencoba Ubah',
            'nik' => '3333333333333333',
            'kontak' => '083',
            'profesi' => 'Bidan',
            'instansi' => 'RS Coba',
        ]);
        $updateResponse->assertRedirect();
        $updateResponse->assertSessionHas('error');

        $this->assertNotSame('Mencoba Ubah', $pendaftaran->refresh()->data_diri['nama'] ?? null);
    }

    public function test_peserta_tidak_dapat_mengubah_pendaftaran_orang_lain(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $other = User::factory()->create(['role' => 'peserta']);
        $pendaftaran = Pendaftaran::factory()->create(['peserta_id' => $other->id, 'status_verifikasi' => 'pending']);

        $response = $this->actingAs($peserta)->put(route('pendaftarans.updateSelf', $pendaftaran), [
            'nama' => 'Mencoba Ubah',
            'nik' => '4444444444444444',
            'kontak' => '084',
            'profesi' => 'Bidan',
            'instansi' => 'RS Coba',
        ]);

        $response->assertForbidden();
    }

    public function test_peserta_dapat_mengubah_jadwal_asrama_dan_dapat_kamar_baru(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'dibuka']);
        $asrama = Asrama::factory()->create(['nama' => 'Kunthi', 'kapasitas' => 40]);
        $kamar = Kamar::factory()->create(['asrama_id' => $asrama->id, 'nomor_kamar' => 'KUN-001', 'kapasitas' => 1]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'pending',
            'butuh_asrama' => true,
            'check_in' => '2026-10-01',
            'check_out' => '2026-10-03',
            'kamar_id' => $kamar->id,
            'data_diri' => ['nama' => 'Budi', 'nik' => '1', 'kontak' => '1', 'profesi' => '1', 'instansi' => '1'],
        ]);

        $response = $this->actingAs($peserta)->put(route('pendaftarans.updateSelf', $pendaftaran), [
            'nama' => 'Budi',
            'nik' => '1234567890123456',
            'kontak' => '0812',
            'profesi' => 'Perawat',
            'instansi' => 'RS A',
            'butuh_asrama' => '1',
            'check_in' => '2026-11-01',
            'check_out' => '2026-11-03',
        ]);

        $response->assertRedirect(route('pendaftarans.index'));
        $response->assertSessionHas('success');

        $pendaftaran->refresh();
        $this->assertSame('2026-11-01', $pendaftaran->check_in->format('Y-m-d'));
        $this->assertSame($kamar->id, $pendaftaran->kamar_id);
    }

    public function test_peserta_mendapat_pesan_bentrok_saat_mengubah_ke_tanggal_yang_sudah_penuh(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pesertaLain = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'dibuka']);
        $asrama = Asrama::factory()->create(['nama' => 'Kunthi', 'kapasitas' => 40]);
        $kamar = Kamar::factory()->create(['asrama_id' => $asrama->id, 'nomor_kamar' => 'KUN-001', 'kapasitas' => 1]);

        // Kamar satu-satunya sudah terisi peserta lain untuk 10-12 Nov.
        Pendaftaran::factory()->create([
            'peserta_id' => $pesertaLain->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'kamar_id' => $kamar->id,
            'check_in' => '2026-11-10',
            'check_out' => '2026-11-12',
            'butuh_asrama' => true,
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'pending',
            'butuh_asrama' => false,
            'data_diri' => ['nama' => 'Citra', 'nik' => '1', 'kontak' => '1', 'profesi' => '1', 'instansi' => '1'],
        ]);

        // Peserta mengubah pendaftarannya sendiri agar bentrok dengan jadwal kamar yang sudah penuh.
        $response = $this->actingAs($peserta)->put(route('pendaftarans.updateSelf', $pendaftaran), [
            'nama' => 'Citra',
            'nik' => '1234567890123456',
            'kontak' => '0812',
            'profesi' => 'Bidan',
            'instansi' => 'RS B',
            'butuh_asrama' => '1',
            'check_in' => '2026-11-10',
            'check_out' => '2026-11-12',
        ]);

        $response->assertRedirect(route('pendaftarans.index'));
        $response->assertSessionHas('success', 'Pendaftaran berhasil diperbarui, tetapi asrama sudah penuh untuk tanggal tersebut. Anda tetap terdaftar tanpa kamar.');

        $pendaftaran->refresh();
        $this->assertNull($pendaftaran->kamar_id);
        $this->assertTrue($pendaftaran->butuh_asrama);
    }
}
