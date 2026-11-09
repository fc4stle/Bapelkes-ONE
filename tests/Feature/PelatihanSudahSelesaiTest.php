<?php

namespace Tests\Feature;

use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test satu sumber kebenaran: Pelatihan::sudahSelesai()
 *
 * Aturan:
 * - Jika status = Selesai, maka sudahSelesai = true
 * - Jika status != Selesai dan tanggal_selesai < hari ini, maka sudahSelesai = true
 * - Jika status != Selesai dan tanggal_selesai >= hari ini, maka sudahSelesai = false
 */
class PelatihanSudahSelesaiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-15')); // Hari ini: 15 Nov
    }

    // ============ sudahSelesai() method ============

    public function test_sudah_selesai_false_when_tanggal_selesai_is_today_and_status_not_selesai(): void
    {
        $pelatihan = Pelatihan::factory()->create([
            'status' => 'dibuka',
            'tanggal_selesai' => Carbon::parse('2026-11-15'), // Hari ini
        ]);

        $this->assertFalse($pelatihan->sudahSelesai());
    }

    public function test_sudah_selesai_true_when_tanggal_selesai_is_yesterday_and_status_not_selesai(): void
    {
        $pelatihan = Pelatihan::factory()->create([
            'status' => 'dibuka',
            'tanggal_selesai' => Carbon::parse('2026-11-14'), // Kemarin
        ]);

        $this->assertTrue($pelatihan->sudahSelesai());
    }

    public function test_sudah_selesai_true_when_status_is_selesai_regardless_of_date(): void
    {
        $pelatihan = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_selesai' => Carbon::parse('2026-12-25'), // Tanggal masa depan
        ]);

        $this->assertTrue($pelatihan->sudahSelesai());
    }

    public function test_sudah_selesai_false_when_tanggal_selesai_is_future_and_status_not_selesai(): void
    {
        $pelatihan = Pelatihan::factory()->create([
            'status' => 'dibuka',
            'tanggal_selesai' => Carbon::parse('2026-11-20'), // 5 hari ke depan
        ]);

        $this->assertFalse($pelatihan->sudahSelesai());
    }

    // ============ Scope sudahSelesai() ============

    public function test_scope_sudah_selesai_includes_status_selesai(): void
    {
        Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => '2026-12-25']);
        Pelatihan::factory()->create(['status' => 'dibuka', 'tanggal_selesai' => '2026-11-20']);

        $result = Pelatihan::query()->sudahSelesai()->get();

        $this->assertCount(1, $result);
        $this->assertEquals('selesai', $result->first()->status->value);
    }

    public function test_scope_sudah_selesai_includes_past_tanggal_selesai(): void
    {
        Pelatihan::factory()->create(['status' => 'dibuka', 'tanggal_selesai' => '2026-11-14']);
        Pelatihan::factory()->create(['status' => 'dibuka', 'tanggal_selesai' => '2026-11-15']);
        Pelatihan::factory()->create(['status' => 'dibuka', 'tanggal_selesai' => '2026-11-20']);

        $result = Pelatihan::query()->sudahSelesai()->get();

        $this->assertCount(1, $result);
        $this->assertEquals(Carbon::parse('2026-11-14'), $result->first()->tanggal_selesai->toDate());
    }

    public function test_scope_belum_selesai_includes_hari_ini_dan_masa_depan(): void
    {
        Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => '2026-11-14']);
        Pelatihan::factory()->create(['status' => 'dibuka', 'tanggal_selesai' => '2026-11-15']); // Hari ini
        Pelatihan::factory()->create(['status' => 'dibuka', 'tanggal_selesai' => '2026-11-20']); // Masa depan

        $result = Pelatihan::query()->belumSelesai()->get();

        // belumSelesai harus include: tanggal_selesai >= hari ini (tidak exclude hari ini)
        // Jadi B (11-15) dan C (11-20) harus masuk
        $this->assertCount(2, $result);
        $this->assertTrue($result->pluck('tanggal_selesai')->contains(Carbon::parse('2026-11-15')->toDate()));
        $this->assertTrue($result->pluck('tanggal_selesai')->contains(Carbon::parse('2026-11-20')->toDate()));
    }

    // ============ Sertifikat: hanya tersedia sehari setelah pelatihan selesai ============

    public function test_sertifikat_tidak_tersedia_pada_hari_terakhir_pelatihan(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $panitia = User::factory()->create(['role' => 'panitia']);

        $pelatihan = Pelatihan::factory()->create([
            'status' => 'dibuka',
            'tanggal_selesai' => Carbon::parse('2026-11-15'), // Hari ini
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $panitia->id,
            'verified_at' => now(),
            'hadir_at' => now(), // Sudah hadir
        ]);

        $this->assertFalse($pendaftaran->isPelatihanSelesai());
        $this->assertFalse($pendaftaran->bisaDownloadSertifikat());
    }

    public function test_sertifikat_tersedia_sehari_setelah_pelatihan_selesai(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $panitia = User::factory()->create(['role' => 'panitia']);

        $pelatihan = Pelatihan::factory()->create([
            'status' => 'dibuka',
            'tanggal_selesai' => Carbon::parse('2026-11-14'), // Kemarin
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $panitia->id,
            'verified_at' => now(),
            'hadir_at' => now(), // Sudah hadir
        ]);

        $this->assertTrue($pendaftaran->isPelatihanSelesai());
        $this->assertTrue($pendaftaran->bisaDownloadSertifikat());
    }

    // ============ TIYAS: konsistensi dengan sertifikat ============

    public function test_tiyas_dapat_diisi_sehari_setelah_tanggal_selesai_meski_status_belum_selesai(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $panitia = User::factory()->create(['role' => 'panitia']);

        $pelatihan = Pelatihan::factory()->create([
            'status' => 'dibuka', // Belum status selesai
            'tanggal_selesai' => Carbon::parse('2026-11-14'), // Kemarin
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $panitia->id,
            'verified_at' => now(),
        ]);

        // Policy harus membolehkan isi TIYAS
        $this->assertTrue(auth()->login($peserta) || true);
        $this->assertTrue($peserta->can('isiTiyas', $pendaftaran));
    }

    public function test_tiyas_tidak_dapat_diisi_pada_hari_terakhir_pelatihan(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $panitia = User::factory()->create(['role' => 'panitia']);

        $pelatihan = Pelatihan::factory()->create([
            'status' => 'dibuka',
            'tanggal_selesai' => Carbon::parse('2026-11-15'), // Hari ini
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $panitia->id,
            'verified_at' => now(),
        ]);

        // Policy harus melarang isi TIYAS
        $this->assertTrue(auth()->login($peserta) || true);
        $this->assertFalse($peserta->can('isiTiyas', $pendaftaran));
    }

    public function test_tiyas_dapat_diisi_bila_status_sudah_selesai_meski_tanggal_masa_depan(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $panitia = User::factory()->create(['role' => 'panitia']);

        $pelatihan = Pelatihan::factory()->create([
            'status' => 'selesai', // Status manual selesai
            'tanggal_selesai' => Carbon::parse('2026-12-25'), // Tanggal masa depan
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $panitia->id,
            'verified_at' => now(),
        ]);

        // Policy harus membolehkan isi TIYAS
        $this->assertTrue(auth()->login($peserta) || true);
        $this->assertTrue($peserta->can('isiTiyas', $pendaftaran));
    }

    // ============ Konsistensi: langkahBerikutnya untuk peserta diverifikasi ============

    public function test_langkah_berikutnya_setelah_tanggal_selesai_sama_dengan_status_selesai(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $panitia = User::factory()->create(['role' => 'panitia']);

        // Skenario A: tanggal selesai lewat, status belum selesai
        $pelatihan_a = Pelatihan::factory()->create([
            'status' => 'dibuka',
            'tanggal_selesai' => Carbon::parse('2026-11-14'),
        ]);

        $pendaftaran_a = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan_a->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $panitia->id,
            'verified_at' => now(),
            'hadir_at' => now(),
        ]);

        // Skenario B: status selesai manual
        $pelatihan_b = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_selesai' => Carbon::parse('2026-12-25'),
        ]);

        $pendaftaran_b = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan_b->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $panitia->id,
            'verified_at' => now(),
            'hadir_at' => now(),
        ]);

        $langkah_a = $pendaftaran_a->langkahBerikutnya();
        $langkah_b = $pendaftaran_b->langkahBerikutnya();

        // Keduanya harus memberikan langkah yang sama: "Pelatihan selesai"
        $this->assertEquals($langkah_a['judul'], $langkah_b['judul']);
        $this->assertEquals('Pelatihan selesai', $langkah_a['judul']);
    }

    // ============ Dashboard panitia: daftar TIYAS yang tersedia ============

    public function test_scope_sudah_selesai_dengan_tiyas_menampilkan_pelatihan_lewat_waktu(): void
    {
        // Pelatihan 1: tanggal lewat, status belum selesai, ada TIYAS
        $pelatihan_1 = Pelatihan::factory()->create([
            'status' => 'dibuka',
            'tanggal_selesai' => Carbon::parse('2026-11-14'),
        ]);
        $pendaftaran_1 = Pendaftaran::factory()->create(['pelatihan_id' => $pelatihan_1->id]);
        $pendaftaran_1->tiyasResponse()->createMany([
            ['identitas' => ['test' => 'data'], 'jawaban' => ['test' => 1], 'submitted_at' => now()],
        ]);

        // Pelatihan 2: status selesai manual, ada TIYAS
        $pelatihan_2 = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_selesai' => Carbon::parse('2026-12-25'),
        ]);
        $pendaftaran_2 = Pendaftaran::factory()->create(['pelatihan_id' => $pelatihan_2->id]);
        $pendaftaran_2->tiyasResponse()->createMany([
            ['identitas' => ['test' => 'data'], 'jawaban' => ['test' => 1], 'submitted_at' => now()],
        ]);

        // Pelatihan 3: tanggal belum lewat, belum ada TIYAS (tidak boleh ada di daftar)
        $pelatihan_3 = Pelatihan::factory()->create([
            'status' => 'dibuka',
            'tanggal_selesai' => Carbon::parse('2026-11-20'),
        ]);

        // Keduanya (pelatihan_1 dan pelatihan_2) harus ada di daftar sudahSelesai dengan TIYAS
        $pelatihan_sudah_selesai = Pelatihan::query()->sudahSelesai()->has('tiyasResponses')->get();
        $this->assertEquals(2, $pelatihan_sudah_selesai->count());
        $this->assertTrue($pelatihan_sudah_selesai->pluck('id')->contains($pelatihan_1->id));
        $this->assertTrue($pelatihan_sudah_selesai->pluck('id')->contains($pelatihan_2->id));
        $this->assertFalse($pelatihan_sudah_selesai->pluck('id')->contains($pelatihan_3->id));
    }
}
