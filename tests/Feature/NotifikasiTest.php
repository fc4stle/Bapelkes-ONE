<?php

namespace Tests\Feature;

use App\Models\Asrama;
use App\Models\Kamar;
use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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
        $this->assertEquals($pelatihan->nama, $notifikasi->data['judul_pelatihan']);
        $this->assertEquals($pendaftaran->id, $notifikasi->data['pendaftaran_id']);
        $this->assertStringContainsString($pelatihan->nama, $notifikasi->data['pesan']);
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
        $this->assertEquals($pelatihan->nama, $notifikasi->data['judul_pelatihan']);
        $this->assertEquals($pendaftaran->id, $notifikasi->data['pendaftaran_id']);
        $this->assertStringContainsString($pelatihan->nama, $notifikasi->data['pesan']);
    }

    public function test_notifikasi_dikirim_saat_kamar_dipindah_berhasil(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create();
        $asrama = Asrama::factory()->create();
        $kamarLama = Kamar::factory()
            ->for($asrama)
            ->create(['nomor_kamar' => 'A101']);
        $kamarBaru = Kamar::factory()
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
        $asrama = Asrama::factory()->create();
        $kamarLama = Kamar::factory()
            ->for($asrama)
            ->create(['nomor_kamar' => 'A101']);
        $kamarBaru = Kamar::factory()
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

    public function test_tandai_semua_dibaca_bekerja(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan1 = Pelatihan::factory()->create();
        $pelatihan2 = Pelatihan::factory()->create();
        $pelatihan3 = Pelatihan::factory()->create();

        // Buat 3 pendaftaran yang akan menghasilkan 3 notifikasi belum dibaca
        $pendaftaran1 = Pendaftaran::factory()
            ->for($peserta, 'peserta')
            ->for($pelatihan1, 'pelatihan')
            ->create(['status_verifikasi' => 'menunggu']);

        $pendaftaran2 = Pendaftaran::factory()
            ->for($peserta, 'peserta')
            ->for($pelatihan2, 'pelatihan')
            ->create(['status_verifikasi' => 'menunggu']);

        $pendaftaran3 = Pendaftaran::factory()
            ->for($peserta, 'peserta')
            ->for($pelatihan3, 'pelatihan')
            ->create(['status_verifikasi' => 'menunggu']);

        $pendaftaran1->verifikasi($panitia);
        $pendaftaran2->verifikasi($panitia);
        $pendaftaran3->tolak($panitia, 'Alasan');

        // Verifikasi ada 3 notifikasi belum dibaca
        $this->assertEquals(3, $peserta->unreadNotifications->count());

        // Tandai semua dibaca
        $response = $this->actingAs($peserta)
            ->patch(route('notifikasi.tandai-semua-dibaca'));

        // Verifikasi redirect dan pesan sukses
        $response->assertRedirect();
        $response->assertSessionHas('success', 'Semua notifikasi telah ditandai dibaca.');

        // Verifikasi semua notifikasi sudah dibaca
        $this->assertEquals(0, $peserta->fresh()->unreadNotifications->count());
    }

    public function test_tandai_semua_tidak_menyentuh_notifikasi_user_lain(): void
    {
        $peserta1 = User::factory()->create(['role' => 'peserta']);
        $peserta2 = User::factory()->create(['role' => 'peserta']);
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan1 = Pelatihan::factory()->create();
        $pelatihan2 = Pelatihan::factory()->create();

        // Buat notifikasi untuk peserta1
        $pendaftaran1 = Pendaftaran::factory()
            ->for($peserta1, 'peserta')
            ->for($pelatihan1, 'pelatihan')
            ->create(['status_verifikasi' => 'menunggu']);

        $pendaftaran1->verifikasi($panitia);

        // Buat notifikasi untuk peserta2
        $pendaftaran2 = Pendaftaran::factory()
            ->for($peserta2, 'peserta')
            ->for($pelatihan2, 'pelatihan')
            ->create(['status_verifikasi' => 'menunggu']);

        $pendaftaran2->verifikasi($panitia);

        // Verifikasi peserta2 punya 1 notifikasi belum dibaca
        $this->assertEquals(1, $peserta2->unreadNotifications->count());

        // Peserta1 tandai semua dibaca
        $this->actingAs($peserta1)
            ->patch(route('notifikasi.tandai-semua-dibaca'));

        // Verifikasi notifikasi peserta2 tetap belum dibaca
        $this->assertEquals(1, $peserta2->fresh()->unreadNotifications->count());
    }

    public function test_halaman_notifikasi_menampilkan_pesan_dan_tombol_tandai_semua(): void
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

        // Verifikasi halaman menampilkan pesan notifikasi
        $notifikasi = $peserta->notifications()->first();
        $response->assertSee($notifikasi->data['judul_pelatihan']);

        // Verifikasi tombol "Tandai Semua Dibaca" ada
        $response->assertSee('Tandai Semua Dibaca');
    }

    /**
     * BUG 3: pengingat() mengembalikan 'Sedang berlangsung' pada HARI TERAKHIR pelatihan
     */
    public function test_pengingat_sedang_berlangsung_pada_hari_terakhir(): void
    {
        Carbon::setTestNow('2025-01-25'); // Hari terakhir

        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create([
            'tanggal_mulai' => '2025-01-15',
            'tanggal_selesai' => '2025-01-25', // hari ini adalah tanggal_selesai
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $pengingat = $pendaftaran->pengingat();
        $this->assertNotNull($pengingat);
        $this->assertEquals('Sedang berlangsung', $pengingat['label']);
    }

    /**
     * BUG 3: pengingat() mengembalikan null sehari setelah tanggal_selesai
     */
    public function test_pengingat_null_sehari_setelah_tanggal_selesai(): void
    {
        Carbon::setTestNow('2025-01-26'); // Sehari setelah

        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create([
            'tanggal_mulai' => '2025-01-15',
            'tanggal_selesai' => '2025-01-25', // kemarin
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $pengingat = $pendaftaran->pengingat();
        $this->assertNull($pengingat);
    }

    /**
     * BUG 2: Dashboard menampilkan nomor_kamar yang sebenarnya saat butuh_asrama dan kamar terisi
     */
    public function test_dashboard_menampilkan_nomor_kamar_sebenarnya(): void
    {
        Carbon::setTestNow('2025-01-15');

        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create([
            'tanggal_mulai' => '2025-01-18',
            'tanggal_selesai' => '2025-01-25',
        ]);

        $asrama = Asrama::factory()->create();
        $kamar = Kamar::factory()
            ->for($asrama)
            ->create(['nomor_kamar' => 'A101']);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'butuh_asrama' => true,
            'kamar_id' => $kamar->id,
            'check_in' => '2025-01-18',
            'check_out' => '2025-01-25',
        ]);

        $response = $this->actingAs($peserta)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('A101');
    }

    /**
     * BUG 1: Pesan notifikasi KamarDipindah mengandung nama pelatihan sebenarnya
     */
    public function test_notifikasi_kamar_dipindah_mengandung_nama_pelatihan(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['nama' => 'Pelatihan Testing']);
        $asrama = Asrama::factory()->create();
        $kamarLama = Kamar::factory()
            ->for($asrama)
            ->create(['nomor_kamar' => 'A101']);
        $kamarBaru = Kamar::factory()
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
        $this->assertStringContainsString('Pelatihan Testing', $notifikasi->data['pesan']);
    }
}
