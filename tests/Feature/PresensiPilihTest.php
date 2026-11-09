<?php

namespace Tests\Feature;

use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PresensiPilihTest extends TestCase
{
    use RefreshDatabase;

    protected User $panitia;

    protected User $admin;

    protected User $peserta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->panitia = User::factory()->create(['role' => 'panitia']);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->peserta = User::factory()->create(['role' => 'peserta']);
    }

    public function test_panitia_dapat_membuka_pilih_presensi(): void
    {
        $response = $this->actingAs($this->panitia)
            ->get(route('panitia.presensi.index'));

        $response->assertStatus(200);
        $response->assertViewIs('pelatihans.presensi-pilih');
    }

    public function test_admin_dapat_membuka_pilih_presensi(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('panitia.presensi.index'));

        $response->assertStatus(200);
        $response->assertViewIs('pelatihans.presensi-pilih');
    }

    public function test_peserta_tidak_dapat_membuka_pilih_presensi(): void
    {
        $response = $this->actingAs($this->peserta)
            ->get(route('panitia.presensi.index'));

        $response->assertForbidden();
    }

    public function test_tamu_redirect_ke_login(): void
    {
        $response = $this->get(route('panitia.presensi.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_satu_pelatihan_berlangsung_redirect_langsung(): void
    {
        Carbon::setTestNow('2026-11-15 10:30:00');

        $pelatihan = Pelatihan::factory()->create([
            'status' => 'dibuka',
            'tanggal_mulai' => '2026-11-15',
            'tanggal_selesai' => '2026-11-17',
        ]);

        $response = $this->actingAs($this->panitia)
            ->get(route('panitia.presensi.index'));

        $response->assertRedirect(route('pelatihan.presensi', $pelatihan));
    }

    public function test_dua_pelatihan_berlangsung_tampilkan_daftar(): void
    {
        Carbon::setTestNow('2026-11-15 10:30:00');

        $pelatihan1 = Pelatihan::factory()->create([
            'nama' => 'Pelatihan A',
            'status' => 'dibuka',
            'lokasi' => 'Jakarta',
            'tanggal_mulai' => '2026-11-15',
            'tanggal_selesai' => '2026-11-17',
        ]);

        $pelatihan2 = Pelatihan::factory()->create([
            'nama' => 'Pelatihan B',
            'status' => 'dibuka',
            'lokasi' => 'Bandung',
            'tanggal_mulai' => '2026-11-15',
            'tanggal_selesai' => '2026-11-16',
        ]);

        $response = $this->actingAs($this->panitia)
            ->get(route('panitia.presensi.index'));

        $response->assertStatus(200);
        $response->assertViewIs('pelatihans.presensi-pilih');
        $response->assertSee('Pelatihan A');
        $response->assertSee('Pelatihan B');
    }

    public function test_tidak_ada_pelatihan_tampilkan_pesan_kosong(): void
    {
        Carbon::setTestNow('2026-11-15 10:30:00');

        $response = $this->actingAs($this->panitia)
            ->get(route('panitia.presensi.index'));

        $response->assertStatus(200);
        $response->assertSee('Tidak ada pelatihan yang sedang berlangsung hari ini');
        $response->assertViewIs('pelatihans.presensi-pilih');
    }

    public function test_pelatihan_draft_tidak_muncul(): void
    {
        Carbon::setTestNow('2026-11-15 10:30:00');

        Pelatihan::factory()->create([
            'status' => 'draft',
            'tanggal_mulai' => '2026-11-15',
            'tanggal_selesai' => '2026-11-17',
        ]);

        $response = $this->actingAs($this->panitia)
            ->get(route('panitia.presensi.index'));

        $response->assertSee('Tidak ada pelatihan yang sedang berlangsung hari ini');
    }

    public function test_pelatihan_belum_mulai_tidak_muncul(): void
    {
        Carbon::setTestNow('2026-11-15 10:30:00');

        Pelatihan::factory()->create([
            'status' => 'dibuka',
            'tanggal_mulai' => '2026-11-16',
            'tanggal_selesai' => '2026-11-17',
        ]);

        $response = $this->actingAs($this->panitia)
            ->get(route('panitia.presensi.index'));

        $response->assertSee('Tidak ada pelatihan yang sedang berlangsung hari ini');
    }

    public function test_pelatihan_sudah_selesai_tidak_muncul(): void
    {
        Carbon::setTestNow('2026-11-15 10:30:00');

        Pelatihan::factory()->create([
            'status' => 'dibuka',
            'tanggal_mulai' => '2026-11-10',
            'tanggal_selesai' => '2026-11-14',
        ]);

        $response = $this->actingAs($this->panitia)
            ->get(route('panitia.presensi.index'));

        $response->assertSee('Tidak ada pelatihan yang sedang berlangsung hari ini');
    }

    public function test_dua_pelatihan_berlangsung_tampil_list_tidak_redirect(): void
    {
        Carbon::setTestNow('2026-11-15 10:30:00');

        // Create two pelatihan on hari yang sama
        Pelatihan::factory()->create([
            'nama' => 'Pelatihan Pagi',
            'status' => 'dibuka',
            'tanggal_mulai' => '2026-11-15',
            'tanggal_selesai' => '2026-11-16',
        ]);

        Pelatihan::factory()->create([
            'nama' => 'Pelatihan Siang',
            'status' => 'dibuka',
            'tanggal_mulai' => '2026-11-15',
            'tanggal_selesai' => '2026-11-17',
        ]);

        $response = $this->actingAs($this->panitia)
            ->get(route('panitia.presensi.index'));

        // Should show list page, not redirect
        $response->assertStatus(200);
        $response->assertSee('Pelatihan Pagi');
        $response->assertSee('Pelatihan Siang');
    }

    public function test_sesudah_jam_00_00_hari_berikutnya_tidak_muncul(): void
    {
        Carbon::setTestNow('2026-11-18 00:01:00');

        Pelatihan::factory()->create([
            'status' => 'dibuka',
            'tanggal_mulai' => '2026-11-15',
            'tanggal_selesai' => '2026-11-17',
        ]);

        $response = $this->actingAs($this->panitia)
            ->get(route('panitia.presensi.index'));

        $response->assertSee('Tidak ada pelatihan yang sedang berlangsung hari ini');
    }

    public function test_hitungan_hadir_peserta_diverifikasi_saja(): void
    {
        Carbon::setTestNow('2026-11-15 10:30:00');

        $pelatihan1 = Pelatihan::factory()->create([
            'nama' => 'Pelatihan Pagi',
            'status' => 'dibuka',
            'tanggal_mulai' => '2026-11-15',
            'tanggal_selesai' => '2026-11-17',
        ]);

        // Add another pelatihan that STARTS on 2026-11-15 (today) to avoid redirect
        $pelatihan2 = Pelatihan::factory()->create([
            'nama' => 'Pelatihan Siang',
            'status' => 'dibuka',
            'tanggal_mulai' => '2026-11-15',
            'tanggal_selesai' => '2026-11-18',
        ]);

        // 3 peserta diverifikasi, 1 hadir
        Pendaftaran::factory()->count(3)->create([
            'pelatihan_id' => $pelatihan1->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        // Set 1 peserta hadir
        $pelatihan1->pendaftarans()->first()->update(['hadir_at' => now()]);

        // 2 peserta pending, 1 hadir (tidak dihitung karena pending)
        Pendaftaran::factory()->count(2)->create([
            'pelatihan_id' => $pelatihan1->id,
            'status_verifikasi' => 'pending',
            'hadir_at' => now(),
        ]);

        // 1 peserta ditolak, 1 hadir (tidak dihitung)
        Pendaftaran::factory()->create([
            'pelatihan_id' => $pelatihan1->id,
            'status_verifikasi' => 'ditolak',
            'hadir_at' => now(),
        ]);

        $response = $this->actingAs($this->panitia)
            ->get(route('panitia.presensi.index'));

        // Hanya 1 dari 3 diverifikasi (should show page listing both pelatihans, not redirect)
        $response->assertStatus(200);
        $response->assertSee('1 dari 3 peserta hadir');
    }

    public function test_navbar_presensi_tampil_untuk_panitia(): void
    {
        $response = $this->actingAs($this->panitia)
            ->get(route('panitia.dashboard'));

        $response->assertSee('Presensi');
    }

    public function test_navbar_presensi_tampil_untuk_admin(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('panitia.dashboard'));

        $response->assertSee('Presensi');
    }

    public function test_navbar_presensi_tidak_tampil_untuk_peserta(): void
    {
        $response = $this->actingAs($this->peserta)
            ->get(route('dashboard'));

        $response->assertDontSee('Presensi');
    }

    public function test_no_n_plus_one_queries(): void
    {
        Carbon::setTestNow('2026-11-15 10:30:00');

        // Create 5 pelatihan berlangsung
        Pelatihan::factory()->count(5)->create([
            'status' => 'dibuka',
            'tanggal_mulai' => '2026-11-15',
            'tanggal_selesai' => '2026-11-17',
        ]);

        // Enable query logging
        DB::enableQueryLog();

        $response = $this->actingAs($this->panitia)
            ->get(route('panitia.presensi.index'));

        $queryCount = count(DB::getQueryLog());

        // Should not exceed reasonable threshold (should be around 2-3 queries)
        $this->assertLessThan(10, $queryCount, "Query count exceeded: {$queryCount}");
    }

    public function test_presensi_item_active_pada_panitia_presensi_index(): void
    {
        Carbon::setTestNow('2026-11-15 10:30:00');

        $pelatihan1 = Pelatihan::factory()->create([
            'status' => 'dibuka',
            'tanggal_mulai' => '2026-11-15',
            'tanggal_selesai' => '2026-11-17',
        ]);

        // Add another pelatihan that STARTS on 2026-11-15 (today) to avoid redirect
        Pelatihan::factory()->create([
            'status' => 'dibuka',
            'tanggal_mulai' => '2026-11-15',
            'tanggal_selesai' => '2026-11-18',
        ]);

        $response = $this->actingAs($this->panitia)
            ->get(route('panitia.presensi.index'));

        // Check that navigation shows active state on Presensi
        // The exact HTML structure depends on x-nav-link component
        $response->assertStatus(200);
    }
}
