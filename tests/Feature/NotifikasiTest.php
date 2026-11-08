<?php

namespace Tests\Feature;

use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotifikasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifikasi_dikirim_saat_pendaftaran_diverifikasi(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create();
        $pendaftaran = Pendaftaran::factory()
            ->for($peserta, 'peserta')
            ->for($pelatihan, 'pelatihan')
            ->create(['status_verifikasi' => 'menunggu']);

        $pendaftaran->verifikasi($panitia);

        $notifikasi = $peserta->notifications()->first();
        $this->assertNotNull($notifikasi);
        $this->assertEquals('diverifikasi', $notifikasi->data['tipe']);
        $this->assertEquals($pelatihan->judul, $notifikasi->data['judul_pelatihan']);
        $this->assertEquals($pendaftaran->id, $notifikasi->data['pendaftaran_id']);
    }

    public function test_notifikasi_dikirim_saat_pendaftaran_ditolak(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create();
        $pendaftaran = Pendaftaran::factory()
            ->for($peserta, 'peserta')
            ->for($pelatihan, 'pelatihan')
            ->create(['status_verifikasi' => 'menunggu']);

        $pendaftaran->tolak($panitia, 'Dokumen tidak lengkap');

        $notifikasi = $peserta->notifications()->first();
        $this->assertNotNull($notifikasi);
        $this->assertEquals('ditolak', $notifikasi->data['tipe']);
        $this->assertEquals($pelatihan->judul, $notifikasi->data['judul_pelatihan']);
        $this->assertEquals($pendaftaran->id, $notifikasi->data['pendaftaran_id']);
    }

    public function test_notifikasi_dikirim_saat_kamar_dipindah_berhasil(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create();
        $asrama = \App\Models\Asrama::factory()->create();
        $kamarLama = \App\Models\Kamar::factory()
            ->for($asrama)
            ->create(['nomor_kamar' => 'A101']);
        $kamarBaru = \App\Models\Kamar::factory()
            ->for($asrama)
            ->create(['nomor_kamar' => 'B202']);

        $pendaftaran = Pendaftaran::factory()
            ->for($peserta, 'peserta')
            ->for($pelatihan, 'pelatihan')
            ->for($kamarLama, 'kamar')
            ->create(['status_verifikasi' => 'diverifikasi', 'butuh_asrama' => true, 'check_in' => now(), 'check_out' => now()->addDays(5)]);

        // Simulate pindahKamar via controller as panitia
        $response = $this->actingAs($panitia)
            ->patch(route('panitia.pendaftaran.pindah-kamar', $pendaftaran), [
                'kamar_id' => $kamarBaru->id,
            ]);

        $notifikasi = $peserta->notifications()->where('data->tipe', 'pindah_kamar')->first();
        $this->assertNotNull($notifikasi);
        $this->assertStringContainsString('dipindahkan ke kamar B202', $notifikasi->data['pesan']);
    }

    public function test_notifikasi_tidak_dikirim_saat_pindah_kamar_validasi_gagal(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create();
        $asrama = \App\Models\Asrama::factory()->create();
        $kamarLama = \App\Models\Kamar::factory()
            ->for($asrama)
            ->create(['nomor_kamar' => 'A101']);
        $kamarBaru = \App\Models\Kamar::factory()
            ->for($asrama)
            ->create(['nomor_kamar' => 'B202', 'kapasitas' => 0]); // Penuh

        $pendaftaran = Pendaftaran::factory()
            ->for($peserta, 'peserta')
            ->for($pelatihan, 'pelatihan')
            ->for($kamarLama, 'kamar')
            ->create(['status_verifikasi' => 'diverifikasi', 'butuh_asrama' => true, 'check_in' => now(), 'check_out' => now()->addDays(5)]);

        // Attempt pindahKamar - should fail validation
        $response = $this->actingAs($panitia)
            ->patch(route('panitia.pendaftaran.pindah-kamar', $pendaftaran), [
                'kamar_id' => $kamarBaru->id,
            ]);

        $response->assertSessionHasErrors('kamar_id');
        
        // Verify no pindah_kamar notification was sent
        $notifikasi = $peserta->notifications()->where('data->tipe', 'pindah_kamar')->first();
        $this->assertNull($notifikasi);
    }

    public function test_user_tidak_bisa_membaca_notifikasi_user_lain(): void
    {
        $peserta1 = User::factory()->create(['role' => 'peserta']);
        $peserta2 = User::factory()->create(['role' => 'peserta']);
        
        $pelatihan = Pelatihan::factory()->create();
        $panitia = User::factory()->create(['role' => 'panitia']);
        
        $pendaftaran1 = Pendaftaran::factory()
            ->for($peserta1, 'peserta')
            ->for($pelatihan, 'pelatihan')
            ->create(['status_verifikasi' => 'menunggu']);

        $pendaftaran1->verifikasi($panitia);

        $notifikasi = $peserta1->notifications()->first();
        
        // Peserta2 mencoba akses notifikasi peserta1
        $response = $this->actingAs($peserta2)
            ->patch(route('notifikasi.tandai-dibaca', $notifikasi->id));

        // Harus 404 atau redirect
        $response->assertStatus(404);
        
        // Verifikasi notifikasi belum dibaca
        $notifikasi->refresh();
        $this->assertNull($notifikasi->read_at);
    }

    public function test_tandai_dibaca_bekerja(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create();
        
        $pendaftaran = Pendaftaran::factory()
            ->for($peserta, 'peserta')
            ->for($pelatihan, 'pelatihan')
            ->create(['status_verifikasi' => 'menunggu']);

        $pendaftaran->verifikasi($panitia);

        $notifikasi = $peserta->notifications()->first();
        $this->assertNull($notifikasi->read_at);

        // Tandai dibaca
        $response = $this->actingAs($peserta)
            ->patch(route('notifikasi.tandai-dibaca', $notifikasi->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $notifikasi->refresh();
        $this->assertNotNull($notifikasi->read_at);
    }

    public function test_jumlah_unread_notifications_benar(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan1 = Pelatihan::factory()->create();
        $pelatihan2 = Pelatihan::factory()->create();

        $pendaftaran1 = Pendaftaran::factory()
            ->for($peserta, 'peserta')
            ->for($pelatihan1, 'pelatihan')
            ->create(['status_verifikasi' => 'menunggu']);

        $pendaftaran2 = Pendaftaran::factory()
            ->for($peserta, 'peserta')
            ->for($pelatihan2, 'pelatihan')
            ->create(['status_verifikasi' => 'menunggu']);

        $pendaftaran1->verifikasi($panitia);
        $pendaftaran2->tolak($panitia, 'Alasan');

        $this->assertEquals(2, $peserta->unreadNotifications->count());

        // Tandai satu dibaca
        $notifikasi = $peserta->notifications()->first();
        $notifikasi->markAsRead();

        $this->assertEquals(1, $peserta->fresh()->unreadNotifications->count());
    }

    public function test_notifikasi_index_halaman_berhasil_dibuka(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create();
        
        $pendaftaran = Pendaftaran::factory()
            ->for($peserta, 'peserta')
            ->for($pelatihan, 'pelatihan')
            ->create(['status_verifikasi' => 'menunggu']);

        $pendaftaran->verifikasi($panitia);

        $response = $this->actingAs($peserta)
            ->get(route('notifikasi.index'));

        $response->assertStatus(200);
        $response->assertSee('Notifikasi');
    }

    public function test_notifikasi_tidak_dikirim_dua_kali_jika_status_tidak_berubah(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create();
        
        $pendaftaran = Pendaftaran::factory()
            ->for($peserta, 'peserta')
            ->for($pelatihan, 'pelatihan')
            ->create(['status_verifikasi' => 'diverifikasi']);

        // Panggil verifikasi lagi - status sudah diverifikasi
        $pendaftaran->verifikasi($panitia);

        // Hanya ada 1 notifikasi (tidak terkirim karena status sudah 'diverifikasi')
        $notifikasiCount = $peserta->notifications()->where('data->tipe', 'diverifikasi')->count();
        $this->assertEquals(0, $notifikasiCount);
    }
}
