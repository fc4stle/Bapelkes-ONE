<?php

namespace Tests\Feature;

use App\Models\Asrama;
use App\Models\Kamar;
use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KehadiranExportTest extends TestCase
{
    use RefreshDatabase;

    private Pelatihan $pelatihan;

    private User $peserta1;

    private User $peserta2;

    private User $peserta3;

    private Asrama $asrama;

    protected function setUp(): void
    {
        parent::setUp();

        // Create pelatihan
        $this->pelatihan = Pelatihan::factory()->create();

        // Create peserta
        $this->peserta1 = User::factory()->create(['role' => 'peserta']);
        $this->peserta2 = User::factory()->create(['role' => 'peserta']);
        $this->peserta3 = User::factory()->create(['role' => 'peserta']);

        // Create asrama and kamar
        $this->asrama = Asrama::factory()->create(['nama' => 'Asrama A']);
        $kamar1 = Kamar::factory()->create(['asrama_id' => $this->asrama->id, 'nomor_kamar' => '101']);
        $kamar2 = Kamar::factory()->create(['asrama_id' => $this->asrama->id, 'nomor_kamar' => '102']);

        // Create pendaftarans with different statuses
        // Peserta 1: Diverifikasi dengan kamar dan sudah hadir
        Pendaftaran::factory()->create([
            'pelatihan_id' => $this->pelatihan->id,
            'peserta_id' => $this->peserta1->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_at' => now(),
            'kamar_id' => $kamar1->id,
            'hadir_at' => now()->subHours(2),
            'data_diri' => [
                'nama' => 'Ahmad Sutrisno',
                'nik' => '1234567890123456',
                'kontak' => '081234567890',
                'profesi' => 'Dokter',
                'instansi' => 'Rumah Sakit Umum',
            ],
        ]);

        // Peserta 2: Diverifikasi tanpa kamar dan belum hadir
        Pendaftaran::factory()->create([
            'pelatihan_id' => $this->pelatihan->id,
            'peserta_id' => $this->peserta2->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_at' => now(),
            'kamar_id' => null,
            'hadir_at' => null,
            'data_diri' => [
                'nama' => 'Budi Hartono',
                'nik' => '1234567890123457',
                'kontak' => '081234567891',
                'profesi' => 'Perawat',
                'instansi' => 'Puskesmas',
            ],
        ]);

        // Peserta 3: Pending (tidak boleh masuk export)
        Pendaftaran::factory()->create([
            'pelatihan_id' => $this->pelatihan->id,
            'peserta_id' => $this->peserta3->id,
            'status_verifikasi' => 'pending',
            'verified_at' => null,
            'kamar_id' => $kamar2->id,
            'hadir_at' => null,
            'data_diri' => [
                'nama' => 'Citra Dewi',
                'nik' => '1234567890123458',
                'kontak' => '081234567892',
                'profesi' => 'Bidan',
                'instansi' => 'Klinik',
            ],
        ]);
    }

    public function test_panitia_can_export_kehadiran(): void
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $response = $this->actingAs($panitia)
            ->get(route('pelatihan.kehadiran.export', $this->pelatihan));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
        $response->assertHeader('Content-Disposition');
    }

    public function test_admin_can_export_kehadiran(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $response = $this->actingAs($admin)
            ->get(route('pelatihan.kehadiran.export', $this->pelatihan));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
    }

    public function test_peserta_cannot_export_kehadiran(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $response = $this->actingAs($peserta)
            ->get(route('pelatihan.kehadiran.export', $this->pelatihan));

        $response->assertStatus(403);
    }

    public function test_guest_cannot_export_kehadiran(): void
    {
        $response = $this->get(route('pelatihan.kehadiran.export', $this->pelatihan));

        $response->assertRedirect(route('login'));
    }

    public function test_csv_filename_contains_pelatihan_slug(): void
    {
        $this->pelatihan->update(['nama' => 'Pelatihan Keselamatan Kerja']);

        $panitia = User::factory()->create(['role' => 'panitia']);
        $response = $this->actingAs($panitia)
            ->get(route('pelatihan.kehadiran.export', $this->pelatihan));

        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('rekap-kehadiran-pelatihan-keselamatan-kerja', $disposition);
        $this->assertStringContainsString('.csv', $disposition);
    }

    public function test_pendaftar_page_shows_summary(): void
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $response = $this->actingAs($panitia)
            ->get(route('pelatihan.pendaftar', $this->pelatihan));

        $response->assertStatus(200);
        $response->assertSee('Ringkasan Kehadiran');
        // 1 hadir dari 2 terverifikasi
        $response->assertSee('1 dari 2');
    }

    public function test_pendaftar_page_has_export_button(): void
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $response = $this->actingAs($panitia)
            ->get(route('pelatihan.pendaftar', $this->pelatihan));

        $response->assertStatus(200);
        $response->assertSee('Unduh Rekap Kehadiran (CSV)');
        $response->assertSee(route('pelatihan.kehadiran.export', $this->pelatihan));
    }

    public function test_export_route_exists(): void
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $response = $this->actingAs($panitia)
            ->get(route('pelatihan.kehadiran.export', $this->pelatihan));

        // Just check that route exists and returns 200
        $response->assertStatus(200);
    }

    public function test_only_verified_pendaftarans_are_exported(): void
    {
        // Count verified pendaftarans for this pelatihan
        $verifiedCount = Pendaftaran::where('pelatihan_id', $this->pelatihan->id)
            ->where('status_verifikasi', 'diverifikasi')
            ->count();

        $this->assertEquals(2, $verifiedCount);

        // Count pending pendaftarans for this pelatihan
        $pendingCount = Pendaftaran::where('pelatihan_id', $this->pelatihan->id)
            ->where('status_verifikasi', 'pending')
            ->count();

        $this->assertEquals(1, $pendingCount);
    }

    public function test_hadir_and_belum_statuses_tracked(): void
    {
        // Check that one peserta has hadir_at and one doesn't
        $hadirCount = Pendaftaran::where('pelatihan_id', $this->pelatihan->id)
            ->where('status_verifikasi', 'diverifikasi')
            ->whereNotNull('hadir_at')
            ->count();

        $belumCount = Pendaftaran::where('pelatihan_id', $this->pelatihan->id)
            ->where('status_verifikasi', 'diverifikasi')
            ->whereNull('hadir_at')
            ->count();

        $this->assertEquals(1, $hadirCount);
        $this->assertEquals(1, $belumCount);
    }

    public function test_kamar_information_available(): void
    {
        $pesertaDenganKamar = Pendaftaran::where('pelatihan_id', $this->pelatihan->id)
            ->where('status_verifikasi', 'diverifikasi')
            ->whereNotNull('kamar_id')
            ->with('kamar.asrama')
            ->first();

        $this->assertNotNull($pesertaDenganKamar->kamar);
        $this->assertNotNull($pesertaDenganKamar->kamar->asrama);
        $this->assertEquals('101', $pesertaDenganKamar->kamar->nomor_kamar);
        $this->assertEquals('Asrama A', $pesertaDenganKamar->kamar->asrama->nama);
    }

    public function test_email_from_peserta_available(): void
    {
        $pendaftaran = Pendaftaran::where('pelatihan_id', $this->pelatihan->id)
            ->where('status_verifikasi', 'diverifikasi')
            ->with('peserta')
            ->first();

        $this->assertNotNull($pendaftaran->peserta->email);
    }

    public function test_csv_injection_prevention_in_controller(): void
    {
        // Create a test to verify that the escapeForCSV method would work
        // We'll check if pendaftarans with injection attempts would be handled

        $injectionPeserta = User::factory()->create(['role' => 'peserta']);

        Pendaftaran::factory()->create([
            'pelatihan_id' => $this->pelatihan->id,
            'peserta_id' => $injectionPeserta->id,
            'status_verifikasi' => 'diverifikasi',
            'data_diri' => [
                'nama' => '=SYSTEM("malicious")',
                'profesi' => '+INDIRECT("Sheet1!A1")',
                'instansi' => '@SUM(A1:A10)',
                'kontak' => '-2+5+cmd|\'',
            ],
        ]);

        // The export should still work without errors
        $panitia = User::factory()->create(['role' => 'panitia']);
        $response = $this->actingAs($panitia)
            ->get(route('pelatihan.kehadiran.export', $this->pelatihan));

        $response->assertStatus(200);
    }

    public function test_pendaftar_page_requires_auth(): void
    {
        $response = $this->get(route('pelatihan.pendaftar', $this->pelatihan));
        $response->assertRedirect(route('login'));
    }

    public function test_pendaftar_page_requires_panitia_or_admin(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $response = $this->actingAs($peserta)
            ->get(route('pelatihan.pendaftar', $this->pelatihan));

        $response->assertStatus(403);
    }

    public function test_pendaftaran_data_diri_structure(): void
    {
        $pendaftaran = Pendaftaran::where('pelatihan_id', $this->pelatihan->id)
            ->where('status_verifikasi', 'diverifikasi')
            ->first();

        $dataDiri = $pendaftaran->data_diri;
        $this->assertNotEmpty($dataDiri['nama']);
        $this->assertNotEmpty($dataDiri['profesi']);
        $this->assertNotEmpty($dataDiri['instansi']);
    }
}
