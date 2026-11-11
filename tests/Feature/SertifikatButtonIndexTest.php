<?php

namespace Tests\Feature;

use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SertifikatButtonIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: peserta berhak (diverifikasi + hadir + pelatihan selesai) melihat tautan "Unduh Sertifikat"
     * di halaman pendaftarans.index dengan href yang benar.
     */
    public function test_peserta_berhak_melihat_tombol_unduh_sertifikat(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_selesai' => now()->subDay(),
        ]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'hadir_at' => now()->subDay(), // Untuk isHadir()
        ]);

        $response = $this->actingAs($peserta)
            ->get(route('pendaftarans.index'));

        $response->assertOk();
        // Debug: lihat pelatihan status dan tanggal
        $pelatihan->refresh();
        $this->assertTrue($pelatihan->sudahSelesai(), 'Pelatihan should be selesai');
        $this->assertTrue($pendaftaran->isDiverifikasi(), 'Pendaftaran should be diverifikasi');
        $this->assertTrue($pendaftaran->isHadir(), 'Pendaftaran should have hadir_at');
        $this->assertTrue($pendaftaran->isPelatihanSelesai(), 'Pendaftaran->pelatihan should be selesai');

        $response->assertSeeText('Unduh Sertifikat');
        $response->assertSee(route('pendaftarans.sertifikat', $pendaftaran));
    }

    /**
     * Test: peserta belum hadir tidak melihat tombol "Unduh Sertifikat".
     */
    public function test_peserta_belum_hadir_tidak_melihat_tombol_sertifikat(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_selesai' => now()->subDay(),
        ]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'hadir_at' => null, // Belum hadir
        ]);

        $response = $this->actingAs($peserta)
            ->get(route('pendaftarans.index'));

        $response->assertOk();
        $response->assertDontSeeText('Unduh Sertifikat');
    }

    /**
     * Test: peserta belum diverifikasi tidak melihat tombol "Unduh Sertifikat".
     */
    public function test_peserta_belum_diverifikasi_tidak_melihat_tombol_sertifikat(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_selesai' => now()->subDay(),
        ]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'pending',
            'hadir_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($peserta)
            ->get(route('pendaftarans.index'));

        $response->assertOk();
        $response->assertDontSeeText('Unduh Sertifikat');
    }

    /**
     * Test: peserta dengan pelatihan belum selesai tidak melihat tombol "Unduh Sertifikat".
     */
    public function test_peserta_pelatihan_belum_selesai_tidak_melihat_tombol_sertifikat(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create([
            'status' => 'dibuka',
            'tanggal_selesai' => now()->addDays(7),
        ]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'hadir_at' => now(),
        ]);

        $response = $this->actingAs($peserta)
            ->get(route('pendaftarans.index'));

        $response->assertOk();
        $response->assertDontSeeText('Unduh Sertifikat');
    }

    /**
     * Test: pada tanggal selesai jam 10:30, peserta TIDAK berhak melihat tombol.
     * Carbon::setTestNow() untuk consistency dengan sudahSelesai().
     */
    public function test_peserta_tidak_berhak_pada_tanggal_selesai_jam_1030(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);

        // Set test now ke jam 10:30 hari ini
        $waktuTest = now()->startOfDay()->addHours(10)->addMinutes(30);
        Carbon::setTestNow($waktuTest);

        $hariSelesai = now()->startOfDay(); // Sekarang berdasarkan waktu test

        // Pakai status 'dibuka' saja, rely pada date logic di sudahSelesai()
        $pelatihan = Pelatihan::factory()->create([
            'status' => 'dibuka', // NOT selesai, rely pada tanggal_selesai
            'tanggal_selesai' => $hariSelesai,
        ]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'hadir_at' => $hariSelesai,
        ]);

        $response = $this->actingAs($peserta)
            ->get(route('pendaftarans.index'));

        $response->assertOk();
        // Debug
        $pelatihan->refresh();
        $this->assertFalse($pelatihan->sudahSelesai(), 'Pelatihan should NOT be selesai on same day at 10:30');
        $this->assertFalse($pendaftaran->isPelatihanSelesai(), 'Pendaftaran->pelatihan should NOT be selesai');

        // Pada jam 10:30, pelatihan belum selesai (sudahSelesai() false karena masih hari yang sama)
        $response->assertDontSeeText('Unduh Sertifikat');
    }

    /**
     * Test: pada tanggal selesai jam 23:59, peserta TIDAK berhak melihat tombol (masih hari yang sama).
     */
    public function test_peserta_tidak_berhak_pada_tanggal_selesai_jam_2359(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);

        // Set test now ke jam 23:59 hari ini
        $waktuTest = now()->startOfDay()->addHours(23)->addMinutes(59);
        Carbon::setTestNow($waktuTest);

        $hariSelesai = now()->startOfDay();

        $pelatihan = Pelatihan::factory()->create([
            'status' => 'dibuka',
            'tanggal_selesai' => $hariSelesai,
        ]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'hadir_at' => $hariSelesai,
        ]);

        $response = $this->actingAs($peserta)
            ->get(route('pendaftarans.index'));

        $response->assertOk();
        // Pada jam 23:59, pelatihan belum selesai (masih hari yang sama)
        $response->assertDontSeeText('Unduh Sertifikat');
    }

    /**
     * Test: keesokan hari tanggal selesai (jam 00:01), peserta berhak melihat tombol.
     */
    public function test_peserta_berhak_keesokan_hari_tanggal_selesai(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $hariSelesai = now()->startOfDay();

        // Set test now ke jam 00:01 hari berikutnya
        $hariBerikutnya = now()->startOfDay()->addDay()->addMinute();
        Carbon::setTestNow($hariBerikutnya);

        $pelatihan = Pelatihan::factory()->create([
            'status' => 'dibuka',
            'tanggal_selesai' => $hariSelesai,
        ]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'hadir_at' => $hariSelesai,
        ]);

        $response = $this->actingAs($peserta)
            ->get(route('pendaftarans.index'));

        $response->assertOk();
        // Keesokan harinya, pelatihan sudah selesai
        $response->assertSeeText('Unduh Sertifikat');
        $response->assertSee(route('pendaftarans.sertifikat', $pendaftaran));
    }

    /**
     * Test: klik route "Unduh Sertifikat" mengembalikan PDF 200 untuk peserta berhak.
     */
    public function test_klik_unduh_sertifikat_return_pdf_200(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_selesai' => now()->subDay(),
        ]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'hadir_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($peserta)
            ->get(route('pendaftarans.sertifikat', $pendaftaran));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}
