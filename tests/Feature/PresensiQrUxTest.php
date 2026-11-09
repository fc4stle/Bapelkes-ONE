<?php

namespace Tests\Feature;

use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresensiQrUxTest extends TestCase
{
    use RefreshDatabase;

    protected User $panitia;

    protected Pelatihan $pelatihan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->panitia = User::factory()->create(['role' => 'panitia']);
        $this->pelatihan = Pelatihan::factory()->create();
    }

    /**
     * Test: Halaman presensi menampilkan hitungan X/Y peserta dan daftar hadir.
     * Kosong menampilkan "Belum ada yang hadir."
     */
    public function test_presensi_menampilkan_hitungan_dan_daftar_hadir(): void
    {
        $pendaftarans = Pendaftaran::factory(5)
            ->for($this->pelatihan)
            ->create(['status_verifikasi' => 'diverifikasi']);

        // Tandai 2 peserta hadir
        $pendaftarans[0]->tandaiHadir();
        $pendaftarans[1]->tandaiHadir();

        $response = $this->actingAs($this->panitia)
            ->get(route('pelatihan.presensi', $this->pelatihan));

        $response->assertStatus(200);
        $response->assertViewHas('hadir_count', 2);
        $response->assertViewHas('total_count', 5);
        $response->assertViewHas('hadir_list');

        $hadirList = $response->viewData('hadir_list');
        $this->assertCount(2, $hadirList);
        $this->assertArrayHasKey('nama', $hadirList[0]);
        $this->assertArrayHasKey('hadir_at', $hadirList[0]);
    }

    /**
     * Test: Halaman presensi kosong menampilkan "Belum ada yang hadir."
     */
    public function test_presensi_kosong_tampilkan_empty_state(): void
    {
        Pendaftaran::factory(3)
            ->for($this->pelatihan)
            ->create(['status_verifikasi' => 'diverifikasi']);

        $response = $this->actingAs($this->panitia)
            ->get(route('pelatihan.presensi', $this->pelatihan));

        $response->assertStatus(200);
        $response->assertViewHas('hadir_count', 0);
        $response->assertViewHas('total_count', 3);
        $hadirList = $response->viewData('hadir_list');
        $this->assertEmpty($hadirList);
    }

    /**
     * Test: JSON response sukses presensi berisi nama, hadir_at, dan hadir_count.
     */
    public function test_proses_presensi_json_response_berisi_hadir_at_dan_count(): void
    {
        // Set test time agar berada dalam rentang tanggal pelatihan
        $tanggal_mulai = now()->startOfDay();
        Carbon::setTestNow($tanggal_mulai);

        $this->pelatihan->update([
            'tanggal_mulai' => $tanggal_mulai,
            'tanggal_selesai' => $tanggal_mulai->clone()->addDays(5),
        ]);

        $pendaftaran = Pendaftaran::factory()
            ->for($this->pelatihan)
            ->create([
                'status_verifikasi' => 'diverifikasi',
                'verified_by' => $this->panitia->id,
                'verified_at' => now(),
                'kode_presensi' => 'TEST_KODE_001',
                'kode_generated_at' => now(),
                'data_diri' => ['nama' => 'Andi Wijaya', 'nik' => '1111111111111111', 'kontak' => '0821111111', 'profesi' => 'Perawat', 'instansi' => 'RS Test'],
            ]);

        // Scan pertama berhasil
        $response = $this->actingAs($this->panitia)
            ->postJson(
                route('pelatihan.presensi.proses', $this->pelatihan),
                ['kode_presensi' => 'TEST_KODE_001']
            );

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'sukses',
            'nama' => 'Andi Wijaya',
        ]);
        $this->assertArrayHasKey('hadir_at', $response->json());
        $this->assertArrayHasKey('hadir_count', $response->json());
        $this->assertEquals(1, $response->json('hadir_count'));
        $this->assertMatchesRegularExpression('/^\d{2}:\d{2}$/', $response->json('hadir_at'));
    }

    /**
     * Test: JSON response sudah_hadir berisi nama dan count sesuai.
     */
    public function test_proses_presensi_sudah_hadir_tetap_punya_count(): void
    {
        // Set test time agar berada dalam rentang tanggal pelatihan
        $tanggal_mulai = now()->startOfDay();
        Carbon::setTestNow($tanggal_mulai);

        $this->pelatihan->update([
            'tanggal_mulai' => $tanggal_mulai,
            'tanggal_selesai' => $tanggal_mulai->clone()->addDays(5),
        ]);

        $pendaftaran = Pendaftaran::factory()
            ->for($this->pelatihan)
            ->create([
                'status_verifikasi' => 'diverifikasi',
                'verified_by' => $this->panitia->id,
                'verified_at' => now(),
                'kode_presensi' => 'TEST_KODE_002',
                'kode_generated_at' => now(),
            ]);

        // Scan pertama
        $this->actingAs($this->panitia)
            ->postJson(
                route('pelatihan.presensi.proses', $this->pelatihan),
                ['kode_presensi' => 'TEST_KODE_002']
            );

        // Scan ulang
        $response = $this->actingAs($this->panitia)
            ->postJson(
                route('pelatihan.presensi.proses', $this->pelatihan),
                ['kode_presensi' => 'TEST_KODE_002']
            );

        $response->assertStatus(200);
        $response->assertJson(['status' => 'sudah_hadir']);
    }

    /**
     * Test: Halaman pendaftar menampilkan badge "Hadir H:i d M" untuk yang hadir.
     */
    public function test_pendaftar_tampilkan_kehadiran_hadir(): void
    {
        $pendaftaran = Pendaftaran::factory()
            ->for($this->pelatihan)
            ->create(['status_verifikasi' => 'diverifikasi']);

        $pendaftaran->tandaiHadir();

        $response = $this->actingAs($this->panitia)
            ->get(route('pelatihan.pendaftar', $this->pelatihan));

        $response->assertStatus(200);
        $response->assertSeeText('Hadir');
        $response->assertSeeText($pendaftaran->hadir_at->format('H:i d M'));
    }

    /**
     * Test: Halaman pendaftar menampilkan badge "Belum" untuk yang belum hadir.
     */
    public function test_pendaftar_tampilkan_kehadiran_belum(): void
    {
        Pendaftaran::factory()
            ->for($this->pelatihan)
            ->create(['status_verifikasi' => 'diverifikasi', 'hadir_at' => null]);

        $response = $this->actingAs($this->panitia)
            ->get(route('pelatihan.pendaftar', $this->pelatihan));

        $response->assertStatus(200);
        $response->assertSeeText('Belum');
    }

    /**
     * Test: Daftar hadir urut hadir_at terbaru (DESC), maksimal 50.
     */
    public function test_daftar_hadir_urut_terbaru_dan_limit_50(): void
    {
        // Set test time agar berada dalam rentang tanggal pelatihan
        $tanggal_mulai = now()->startOfDay();
        Carbon::setTestNow($tanggal_mulai);

        $this->pelatihan->update([
            'tanggal_mulai' => $tanggal_mulai,
            'tanggal_selesai' => $tanggal_mulai->clone()->addDays(5),
        ]);

        // Buat 60 pendaftaran
        $pendaftarans = Pendaftaran::factory(60)
            ->for($this->pelatihan)
            ->create(['status_verifikasi' => 'diverifikasi']);

        // Tandai semua hadir (dengan interval waktu menit agar beda saat di-format H:i)
        foreach ($pendaftarans as $index => $p) {
            $p->update(['hadir_at' => now()->addMinutes($index)]);
        }

        $response = $this->actingAs($this->panitia)
            ->get(route('pelatihan.presensi', $this->pelatihan));

        $response->assertStatus(200);
        $hadirList = $response->viewData('hadir_list');

        // Hanya 50 terbaru
        $this->assertCount(50, $hadirList);

        // Urut DESC (terbaru = indeks 0)
        // Bandingkan string time untuk memastikan urutan
        $first_time = strtotime('1970-01-01 '.$hadirList[0]['hadir_at']);
        $second_time = strtotime('1970-01-01 '.$hadirList[1]['hadir_at']);
        $this->assertGreaterThanOrEqual(
            $second_time,
            $first_time,
            'Daftar harus urut terbaru (DESC)'
        );
    }
}
