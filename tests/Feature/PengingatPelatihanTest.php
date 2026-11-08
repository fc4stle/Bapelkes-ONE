<?php

namespace Tests\Feature;

use App\Models\Pendaftaran;
use App\Models\Pelatihan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PengingatPelatihanTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Tidak ada pengingat untuk pendaftaran pending
     */
    public function test_tidak_ada_pengingat_untuk_pending(): void
    {
        Carbon::setTestNow('2025-01-15');

        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create([
            'tanggal_mulai' => '2025-01-20',
            'tanggal_selesai' => '2025-01-25',
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'pending',
        ]);

        $this->assertNull($pendaftaran->pengingat());
    }

    /**
     * Tidak ada pengingat untuk pendaftaran ditolak
     */
    public function test_tidak_ada_pengingat_untuk_ditolak(): void
    {
        Carbon::setTestNow('2025-01-15');

        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create([
            'tanggal_mulai' => '2025-01-20',
            'tanggal_selesai' => '2025-01-25',
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'ditolak',
        ]);

        $this->assertNull($pendaftaran->pengingat());
    }

    /**
     * Tidak ada pengingat jika pelatihan mulai lebih dari 7 hari lagi
     */
    public function test_tidak_ada_pengingat_jika_mulai_lebih_dari_7_hari(): void
    {
        Carbon::setTestNow('2025-01-15');

        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create([
            'tanggal_mulai' => '2025-01-25', // 10 hari lagi
            'tanggal_selesai' => '2025-01-30',
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $this->assertNull($pendaftaran->pengingat());
    }

    /**
     * Label benar untuk 3 hari lagi
     */
    public function test_label_benar_untuk_3_hari_lagi(): void
    {
        Carbon::setTestNow('2025-01-15');

        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create([
            'tanggal_mulai' => '2025-01-18', // 3 hari lagi
            'tanggal_selesai' => '2025-01-25',
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $pengingat = $pendaftaran->pengingat();
        $this->assertNotNull($pengingat);
        $this->assertEquals('Dimulai 3 hari lagi', $pengingat['label']);
        $this->assertEquals(3, $pengingat['selisih_hari']);
        $this->assertEquals('sedang', $pengingat['urgensi']);
    }

    /**
     * Label benar untuk besok
     */
    public function test_label_benar_untuk_besok(): void
    {
        Carbon::setTestNow('2025-01-15');

        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create([
            'tanggal_mulai' => '2025-01-16', // besok
            'tanggal_selesai' => '2025-01-25',
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $pengingat = $pendaftaran->pengingat();
        $this->assertNotNull($pengingat);
        $this->assertEquals('Besok', $pengingat['label']);
        $this->assertEquals(1, $pengingat['selisih_hari']);
        $this->assertEquals('tinggi', $pengingat['urgensi']);
    }

    /**
     * Label benar untuk hari ini
     */
    public function test_label_benar_untuk_hari_ini(): void
    {
        Carbon::setTestNow('2025-01-15');

        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create([
            'tanggal_mulai' => '2025-01-15', // hari ini
            'tanggal_selesai' => '2025-01-25',
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $pengingat = $pendaftaran->pengingat();
        $this->assertNotNull($pengingat);
        $this->assertEquals('Hari ini', $pengingat['label']);
        $this->assertEquals(0, $pengingat['selisih_hari']);
        $this->assertEquals('tinggi', $pengingat['urgensi']);
    }

    /**
     * Label benar untuk sedang berlangsung
     */
    public function test_label_benar_untuk_sedang_berlangsung(): void
    {
        Carbon::setTestNow('2025-01-16');

        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create([
            'tanggal_mulai' => '2025-01-15',
            'tanggal_selesai' => '2025-01-25', // belum selesai
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $pengingat = $pendaftaran->pengingat();
        $this->assertNotNull($pengingat);
        $this->assertEquals('Sedang berlangsung', $pengingat['label']);
        $this->assertEquals(-1, $pengingat['selisih_hari']);
    }

    /**
     * Tidak ada pengingat jika pelatihan sudah selesai
     */
    public function test_tidak_ada_pengingat_jika_pelatihan_sudah_selesai(): void
    {
        Carbon::setTestNow('2025-01-26');

        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create([
            'tanggal_mulai' => '2025-01-15',
            'tanggal_selesai' => '2025-01-25', // sudah selesai
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $this->assertNull($pendaftaran->pengingat());
    }

    /**
     * Kamar dan check-in tampil hanya jika butuh_asrama
     */
    public function test_kamar_check_in_tampil_hanya_jika_butuh_asrama(): void
    {
        Carbon::setTestNow('2025-01-15');

        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create([
            'tanggal_mulai' => '2025-01-18',
            'tanggal_selesai' => '2025-01-25',
        ]);

        // Pendaftaran tanpa asrama
        $pendaftaran_no_asrama = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'butuh_asrama' => false,
        ]);

        $response = $this->actingAs($peserta)->get(route('dashboard'));
        $response->assertOk();
        $response->assertViewHas('pengingats');
        
        $pengingats = $response->original->getData()['pengingats'];
        $this->assertGreaterThan(0, $pengingats->count());
    }

    /**
     * Panitia tidak melihat kartu pengingat di dashboard peserta
     */
    public function test_panitia_tidak_melihat_pengingat(): void
    {
        Carbon::setTestNow('2025-01-15');

        $panitia = User::factory()->create(['role' => 'panitia']);

        $response = $this->actingAs($panitia)->get(route('dashboard'));
        $response->assertRedirectToRoute('panitia.dashboard');
    }

    /**
     * Banner muncul hanya untuk sisa 0-1 hari
     */
    public function test_banner_pengingat_muncul_untuk_0_1_hari(): void
    {
        Carbon::setTestNow('2025-01-15');

        $peserta = User::factory()->create(['role' => 'peserta']);
        
        // Pelatihan besok
        $pelatihan_besok = Pelatihan::factory()->create([
            'nama' => 'Pelatihan Besok',
            'tanggal_mulai' => '2025-01-16',
            'tanggal_selesai' => '2025-01-25',
        ]);

        Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan_besok->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $response = $this->actingAs($peserta)->get(route('dashboard'));
        $response->assertOk();
        $response->assertViewHas('bannerPengingat');
        
        $bannerPengingat = $response->original->getData()['bannerPengingat'];
        $this->assertNotNull($bannerPengingat);
        $this->assertEquals('Besok', $bannerPengingat['label']);
    }

    /**
     * Banner tidak muncul untuk sisa lebih dari 1 hari
     */
    public function test_banner_pengingat_tidak_muncul_untuk_lebih_dari_1_hari(): void
    {
        Carbon::setTestNow('2025-01-15');

        $peserta = User::factory()->create(['role' => 'peserta']);
        
        // Pelatihan 3 hari lagi
        $pelatihan_3_hari = Pelatihan::factory()->create([
            'nama' => 'Pelatihan 3 Hari',
            'tanggal_mulai' => '2025-01-18',
            'tanggal_selesai' => '2025-01-25',
        ]);

        Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan_3_hari->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $response = $this->actingAs($peserta)->get(route('dashboard'));
        $response->assertOk();
        $response->assertViewHas('bannerPengingat');
        
        $bannerPengingat = $response->original->getData()['bannerPengingat'];
        $this->assertNull($bannerPengingat);
    }

    /**
     * Kartu pengingat diurutkan dari paling dekat
     */
    public function test_kartu_pengingat_diurutkan_dari_paling_dekat(): void
    {
        Carbon::setTestNow('2025-01-15');

        $peserta = User::factory()->create(['role' => 'peserta']);
        
        // Pelatihan 5 hari lagi
        $pelatihan_5 = Pelatihan::factory()->create([
            'nama' => 'Pelatihan 5 Hari',
            'tanggal_mulai' => '2025-01-20',
            'tanggal_selesai' => '2025-01-25',
        ]);

        // Pelatihan 2 hari lagi
        $pelatihan_2 = Pelatihan::factory()->create([
            'nama' => 'Pelatihan 2 Hari',
            'tanggal_mulai' => '2025-01-17',
            'tanggal_selesai' => '2025-01-25',
        ]);

        // Pelatihan besok
        $pelatihan_1 = Pelatihan::factory()->create([
            'nama' => 'Pelatihan Besok',
            'tanggal_mulai' => '2025-01-16',
            'tanggal_selesai' => '2025-01-25',
        ]);

        Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan_5->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan_2->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan_1->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $response = $this->actingAs($peserta)->get(route('dashboard'));
        $response->assertOk();
        $response->assertViewHas('pengingats');
        
        $pengingats = $response->original->getData()['pengingats'];
        $this->assertEquals(3, $pengingats->count());
        
        // Cek urutan: 1 hari, 2 hari, 5 hari
        $this->assertEquals(1, $pengingats[0]['selisih_hari']);
        $this->assertEquals(2, $pengingats[1]['selisih_hari']);
        $this->assertEquals(5, $pengingats[2]['selisih_hari']);
    }
}
