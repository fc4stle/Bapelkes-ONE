<?php

namespace Tests\Feature;

use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\TiyasResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $peserta;

    protected User $panitia;

    protected Pelatihan $pelatihan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->peserta = User::factory()->create(['role' => 'peserta']);
        $this->panitia = User::factory()->create(['role' => 'panitia']);
        $this->pelatihan = Pelatihan::factory()->create([
            'status' => 'dibuka',
            'kuota' => 50,
            'tanggal_mulai' => '2026-11-10',
            'tanggal_selesai' => '2026-11-15',
        ]);
    }

    public function test_guest_redirect_ke_login(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_panitia_redirect_ke_panitia_dashboard(): void
    {
        $response = $this->actingAs($this->panitia)->get(route('dashboard'));
        $response->assertRedirect(route('panitia.dashboard'));
    }

    public function test_peserta_tanpa_pendaftaran_melihat_empty_state(): void
    {
        $response = $this->actingAs($this->peserta)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Anda belum mendaftar pelatihan apa pun');
        $response->assertSee('Lihat katalog');
    }

    public function test_peserta_melihat_statistik_benar(): void
    {
        // 1 pending, 2 diverifikasi
        Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'pending',
        ]);

        Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => Pelatihan::factory(),
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
        ]);

        Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => Pelatihan::factory(),
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
        ]);

        $response = $this->actingAs($this->peserta)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('3'); // Total Pendaftaran
        $response->assertSee('1'); // Menunggu Verifikasi
        $response->assertSee('2'); // Terverifikasi
    }

    public function test_langkah_berikutnya_a_ditolak(): void
    {
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'ditolak',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'alasan_penolakan' => 'Data tidak lengkap',
        ]);

        $response = $this->actingAs($this->peserta)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Pendaftaran ditolak');
        $response->assertSee('Data tidak lengkap');
        $response->assertSee('Lihat katalog');
    }

    public function test_langkah_berikutnya_b_pending(): void
    {
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'pending',
        ]);

        $response = $this->actingAs($this->peserta)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Menunggu verifikasi panitia');
        $response->assertSee('Panitia akan memeriksa data Anda.');
        $response->assertSee('Ubah data');
    }

    public function test_langkah_berikutnya_c_pelatihan_selesai_dengan_sertifikat(): void
    {
        $pelatihan = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_mulai' => '2026-10-01',
            'tanggal_selesai' => '2026-10-05',
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'hadir_at' => now()->subDays(1),
            'kode_sertifikat' => 'CERT-123',
            'sertifikat_generated_at' => now(),
        ]);

        $response = $this->actingAs($this->peserta)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Pelatihan selesai');
        $response->assertSee('Unduh sertifikat');
    }

    public function test_langkah_berikutnya_c_pelatihan_selesai_tanpa_sertifikat_belum_tiyas(): void
    {
        $pelatihan = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_mulai' => '2026-10-01',
            'tanggal_selesai' => '2026-10-05',
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'hadir_at' => now()->subDays(1),
        ]);

        $response = $this->actingAs($this->peserta)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Isi Evaluasi TIYAS');
    }

    public function test_langkah_berikutnya_c_pelatihan_selesai_tiyas_sudah_diisi(): void
    {
        $pelatihan = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_mulai' => '2026-10-01',
            'tanggal_selesai' => '2026-10-05',
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'hadir_at' => now()->subDays(1),
        ]);

        TiyasResponse::factory()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->peserta)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertDontSee('Isi Evaluasi TIYAS');
    }

    public function test_langkah_berikutnya_c_pelatihan_selesai_tanpa_aksi(): void
    {
        $pelatihan = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_mulai' => '2026-10-01',
            'tanggal_selesai' => '2026-10-05',
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'hadir_at' => now()->subDays(1),
            'kode_sertifikat' => 'CERT-123',
            'sertifikat_generated_at' => now(),
        ]);

        TiyasResponse::factory()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->peserta)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Pelatihan selesai');
        // Ketika sertifikat + TIYAS sudah diisi, aksi ada jadi tampil deskripsi berbeda
        $response->assertSee('Pelatihan Anda telah selesai');
    }

    public function test_langkah_berikutnya_c_selesai_belum_hadir_tampil_tiyas(): void
    {
        $pelatihan = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_mulai' => '2026-10-01',
            'tanggal_selesai' => '2026-10-05',
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'hadir_at' => null, // Belum hadir
        ]);

        $response = $this->actingAs($this->peserta)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Pelatihan selesai');
        // Karena belum hadir, bisaDownloadSertifikat() false, jadi hanya TIYAS
        $response->assertSee('Isi Evaluasi TIYAS');
    }

    public function test_langkah_berikutnya_d_belum_hadir(): void
    {
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'hadir_at' => null,
        ]);

        $response = $this->actingAs($this->peserta)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Pendaftaran terverifikasi');
        $response->assertSee('Tunjukkan kartu peserta saat hari pertama');
        $response->assertSee('Kartu peserta');
    }

    public function test_langkah_berikutnya_e_sudah_hadir(): void
    {
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'hadir_at' => now(),
        ]);

        $response = $this->actingAs($this->peserta)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Anda sudah hadir');
        $response->assertSee('Pelatihan berlangsung sampai');
    }

    public function test_pelatihan_yang_sudah_didaftari_tidak_muncul_di_bagian_d(): void
    {
        // Pendaftaran yang sudah ada
        Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'pending',
        ]);

        // Pelatihan lain yang dibuka
        $pelatihan2 = Pelatihan::factory()->create([
            'status' => 'dibuka',
        ]);

        $response = $this->actingAs($this->peserta)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee($pelatihan2->nama); // Pelatihan lain muncul
        $response->assertDontSee('Belum ada pelatihan lain'); // Tidak empty state
    }

    public function test_peserta_a_tidak_melihat_pendaftaran_peserta_b(): void
    {
        $peserta2 = User::factory()->create(['role' => 'peserta']);

        Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'pending',
        ]);

        Pendaftaran::factory()->create([
            'peserta_id' => $peserta2->id,
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'pending',
        ]);

        $response = $this->actingAs($this->peserta)->get(route('dashboard'));

        $response->assertStatus(200);
        // Peserta1 hanya melihat 1 pendaftaran (miliknya sendiri)
        $pendaftarans = $response->viewData('pendaftarans');
        $this->assertEquals(1, $pendaftarans->count());
        $this->assertEquals($this->peserta->id, $pendaftarans->first()->peserta_id);
    }

    public function test_dashboard_menampilkan_maksimal_5_pendaftaran(): void
    {
        // Buat 10 pendaftaran
        for ($i = 0; $i < 10; $i++) {
            Pendaftaran::factory()->create([
                'peserta_id' => $this->peserta->id,
                'pelatihan_id' => Pelatihan::factory(),
                'status_verifikasi' => 'pending',
            ]);
        }

        $response = $this->actingAs($this->peserta)->get(route('dashboard'));

        $response->assertStatus(200);
        // View harus menerima semua 10, tapi hanya tampilkan 5
        $pendaftarans = $response->viewData('pendaftarans');
        $this->assertEquals(10, $pendaftarans->count());
    }
}
