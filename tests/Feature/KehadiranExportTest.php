<?php

namespace Tests\Feature;

use App\Models\Asrama;
use App\Models\Kamar;
use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KehadiranExportTest extends TestCase
{
    use RefreshDatabase;

    private Pelatihan $pelatihan;

    private Pelatihan $pelatihan2;

    private User $peserta1;

    private User $peserta2;

    private User $peserta3;

    private User $peserta4;

    private Asrama $asrama;

    protected function setUp(): void
    {
        parent::setUp();

        // Create pelatihans
        $this->pelatihan = Pelatihan::factory()->create(['nama' => 'Pelatihan Alpha']);
        $this->pelatihan2 = Pelatihan::factory()->create(['nama' => 'Pelatihan Beta']);

        // Create peserta
        $this->peserta1 = User::factory()->create(['role' => 'peserta', 'email' => 'ahmad@test.com']);
        $this->peserta2 = User::factory()->create(['role' => 'peserta', 'email' => 'budi@test.com']);
        $this->peserta3 = User::factory()->create(['role' => 'peserta', 'email' => 'citra@test.com']);
        $this->peserta4 = User::factory()->create(['role' => 'peserta', 'email' => 'dini@test.com']);

        // Create asrama and kamar
        $this->asrama = Asrama::factory()->create(['nama' => 'Asrama A']);
        $kamar1 = Kamar::factory()->create(['asrama_id' => $this->asrama->id, 'nomor_kamar' => '101']);
        $kamar2 = Kamar::factory()->create(['asrama_id' => $this->asrama->id, 'nomor_kamar' => '102']);

        // Create pendaftarans for pelatihan 1
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

        // Peserta 4: Ditolak (tidak boleh masuk export)
        Pendaftaran::factory()->create([
            'pelatihan_id' => $this->pelatihan->id,
            'peserta_id' => $this->peserta4->id,
            'status_verifikasi' => 'ditolak',
            'verified_at' => null,
            'kamar_id' => null,
            'hadir_at' => null,
            'data_diri' => [
                'nama' => 'Dini Wijaya',
                'nik' => '1234567890123459',
                'kontak' => '081234567893',
                'profesi' => 'Ahli Gizi',
                'instansi' => 'Rumah Sakit Khusus',
            ],
        ]);

        // Create a pendaftaran for pelatihan 2 (harus tidak ikut di export pelatihan 1)
        Pendaftaran::factory()->create([
            'pelatihan_id' => $this->pelatihan2->id,
            'peserta_id' => $this->peserta1->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_at' => now(),
            'kamar_id' => null,
            'hadir_at' => now(),
            'data_diri' => [
                'nama' => 'Ahmad Sutrisno',
                'profesi' => 'Dokter',
                'instansi' => 'Rumah Sakit Umum',
            ],
        ]);
    }

    /**
     * Helper: parse CSV from response content, removing BOM.
     */
    private function parseCSV(string $content): array
    {
        // Remove BOM if present
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            $content = substr($content, 3);
        }

        $lines = explode("\n", trim($content));
        $rows = [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $rows[] = str_getcsv($line);
        }

        return $rows;
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

    public function test_csv_has_bom_utf8(): void
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $response = $this->actingAs($panitia)
            ->get(route('pelatihan.kehadiran.export', $this->pelatihan));

        $content = $response->streamedContent();
        // Check BOM UTF-8 at the beginning
        $this->assertEquals("\xEF\xBB\xBF", substr($content, 0, 3));
    }

    public function test_csv_header_correct(): void
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $response = $this->actingAs($panitia)
            ->get(route('pelatihan.kehadiran.export', $this->pelatihan));

        $content = $response->streamedContent();
        $rows = $this->parseCSV($content);

        $this->assertGreaterThan(0, count($rows), 'CSV should have at least header row');

        $expectedHeader = ['No', 'Nama', 'Email', 'Profesi', 'Instansi', 'Status Verifikasi', 'Kehadiran', 'Waktu Hadir', 'Kamar'];
        $this->assertEquals($expectedHeader, $rows[0]);
    }

    public function test_csv_only_verified_pendaftarans(): void
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $response = $this->actingAs($panitia)
            ->get(route('pelatihan.kehadiran.export', $this->pelatihan));

        $content = $response->streamedContent();
        $rows = $this->parseCSV($content);

        // Skip header row
        $dataRows = array_slice($rows, 1);

        // Should have exactly 2 data rows (only diverifikasi: Ahmad and Budi)
        // Citra (pending) and Dini (ditolak) should not be included
        $this->assertCount(2, $dataRows, 'CSV should contain only verified pendaftarans');

        // Check names are Ahmad and Budi (in alphabetical order)
        $this->assertEquals('Ahmad Sutrisno', $dataRows[0][1]);
        $this->assertEquals('Budi Hartono', $dataRows[1][1]);
    }

    public function test_csv_no_pendaftaran_from_other_pelatihans(): void
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $response = $this->actingAs($panitia)
            ->get(route('pelatihan.kehadiran.export', $this->pelatihan));

        $content = $response->streamedContent();
        $rows = $this->parseCSV($content);

        // Skip header row
        $dataRows = array_slice($rows, 1);

        // Check that no row contains data from pelatihan2
        // (Ahmad from pelatihan2 shouldn't be duplicated)
        $this->assertCount(2, $dataRows, 'Should not include pendaftarans from other pelatihans');
    }

    public function test_csv_kehadiran_status_correct(): void
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $response = $this->actingAs($panitia)
            ->get(route('pelatihan.kehadiran.export', $this->pelatihan));

        $content = $response->streamedContent();
        $rows = $this->parseCSV($content);

        $dataRows = array_slice($rows, 1);

        // Ahmad has hadir_at, so should be "Hadir"
        $this->assertEquals('Hadir', $dataRows[0][6]);

        // Budi has no hadir_at, so should be "Belum"
        $this->assertEquals('Belum', $dataRows[1][6]);
    }

    public function test_csv_waktu_hadir_format(): void
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $response = $this->actingAs($panitia)
            ->get(route('pelatihan.kehadiran.export', $this->pelatihan));

        $content = $response->streamedContent();
        $rows = $this->parseCSV($content);

        $dataRows = array_slice($rows, 1);

        // Ahmad has hadir_at, should have formatted datetime (d/m/Y H:i)
        $this->assertMatchesRegularExpression('/^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}$/', $dataRows[0][7]);

        // Budi has no hadir_at, should be empty
        $this->assertEquals('', $dataRows[1][7]);
    }

    public function test_csv_kamar_format_with_asrama(): void
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $response = $this->actingAs($panitia)
            ->get(route('pelatihan.kehadiran.export', $this->pelatihan));

        $content = $response->streamedContent();
        $rows = $this->parseCSV($content);

        $dataRows = array_slice($rows, 1);

        // Ahmad has kamar, should show "101 (Asrama A)" (no escaping needed as it doesn't start with =, +, -, @, tab, or CR)
        $this->assertEquals('101 (Asrama A)', $dataRows[0][8], 'Kamar should be formatted as "nomor (asrama)"');

        // Budi has no kamar, should be empty
        $this->assertEquals('', $dataRows[1][8]);
    }

    public function test_csv_injection_prevention_equals(): void
    {
        $injectionPeserta = User::factory()->create(['role' => 'peserta', 'email' => 'inject@test.com']);

        Pendaftaran::factory()->create([
            'pelatihan_id' => $this->pelatihan->id,
            'peserta_id' => $injectionPeserta->id,
            'status_verifikasi' => 'diverifikasi',
            'data_diri' => [
                'nama' => '=SYSTEM("malicious")',
                'profesi' => 'Dokter',
                'instansi' => 'Rumah Sakit',
            ],
        ]);

        $panitia = User::factory()->create(['role' => 'panitia']);
        $response = $this->actingAs($panitia)
            ->get(route('pelatihan.kehadiran.export', $this->pelatihan));

        $content = $response->streamedContent();
        $rows = $this->parseCSV($content);

        $dataRows = array_slice($rows, 1);
        // Find row with injection attempt
        $injectionRow = array_values(array_filter($dataRows, fn ($row) => str_contains($row[1], 'SYSTEM')));
        $this->assertCount(1, $injectionRow);

        // Check that nama cell is escaped (starts with single quote)
        $this->assertTrue(str_starts_with($injectionRow[0][1], "'"));
    }

    public function test_csv_injection_prevention_plus(): void
    {
        $injectionPeserta = User::factory()->create(['role' => 'peserta', 'email' => 'inject2@test.com']);

        Pendaftaran::factory()->create([
            'pelatihan_id' => $this->pelatihan->id,
            'peserta_id' => $injectionPeserta->id,
            'status_verifikasi' => 'diverifikasi',
            'data_diri' => [
                'nama' => 'Safe Name',
                'profesi' => '+INDIRECT("Sheet1!A1")',
                'instansi' => 'Rumah Sakit',
            ],
        ]);

        $panitia = User::factory()->create(['role' => 'panitia']);
        $response = $this->actingAs($panitia)
            ->get(route('pelatihan.kehadiran.export', $this->pelatihan));

        $content = $response->streamedContent();
        $rows = $this->parseCSV($content);

        $dataRows = array_slice($rows, 1);
        // Find row with + injection
        $injectionRow = array_values(array_filter($dataRows, fn ($row) => str_contains($row[3], 'INDIRECT')));
        $this->assertCount(1, $injectionRow);

        // Check profesi is escaped
        $this->assertTrue(str_starts_with($injectionRow[0][3], "'"));
    }

    public function test_csv_injection_prevention_at(): void
    {
        $injectionPeserta = User::factory()->create(['role' => 'peserta', 'email' => 'inject3@test.com']);

        Pendaftaran::factory()->create([
            'pelatihan_id' => $this->pelatihan->id,
            'peserta_id' => $injectionPeserta->id,
            'status_verifikasi' => 'diverifikasi',
            'data_diri' => [
                'nama' => 'Safe Name',
                'profesi' => 'Dokter',
                'instansi' => '@SUM(A1:A10)',
            ],
        ]);

        $panitia = User::factory()->create(['role' => 'panitia']);
        $response = $this->actingAs($panitia)
            ->get(route('pelatihan.kehadiran.export', $this->pelatihan));

        $content = $response->streamedContent();
        $rows = $this->parseCSV($content);

        $dataRows = array_slice($rows, 1);
        $injectionRow = array_values(array_filter($dataRows, fn ($row) => str_contains($row[4], 'SUM')));
        $this->assertCount(1, $injectionRow);

        // Check instansi is escaped
        $this->assertTrue(str_starts_with($injectionRow[0][4], "'"));
    }

    public function test_csv_injection_prevention_minus(): void
    {
        $injectionPeserta = User::factory()->create(['role' => 'peserta', 'email' => 'inject4@test.com']);

        Pendaftaran::factory()->create([
            'pelatihan_id' => $this->pelatihan->id,
            'peserta_id' => $injectionPeserta->id,
            'status_verifikasi' => 'diverifikasi',
            'data_diri' => [
                'nama' => '-2+5+cmd|\'',
                'profesi' => 'Dokter',
                'instansi' => 'Rumah Sakit',
            ],
        ]);

        $panitia = User::factory()->create(['role' => 'panitia']);
        $response = $this->actingAs($panitia)
            ->get(route('pelatihan.kehadiran.export', $this->pelatihan));

        $content = $response->streamedContent();
        $rows = $this->parseCSV($content);

        $dataRows = array_slice($rows, 1);
        $injectionRow = array_values(array_filter($dataRows, fn ($row) => str_contains($row[1], 'cmd')));
        $this->assertCount(1, $injectionRow);

        // Check nama is escaped
        $this->assertTrue(str_starts_with($injectionRow[0][1], "'"));
    }

    public function test_csv_injection_prevention_tab(): void
    {
        $injectionPeserta = User::factory()->create(['role' => 'peserta', 'email' => 'inject5@test.com']);

        Pendaftaran::factory()->create([
            'pelatihan_id' => $this->pelatihan->id,
            'peserta_id' => $injectionPeserta->id,
            'status_verifikasi' => 'diverifikasi',
            'data_diri' => [
                'nama' => "\t=1+1",
                'profesi' => 'Dokter',
                'instansi' => 'Rumah Sakit',
            ],
        ]);

        $panitia = User::factory()->create(['role' => 'panitia']);
        $response = $this->actingAs($panitia)
            ->get(route('pelatihan.kehadiran.export', $this->pelatihan));

        $content = $response->streamedContent();
        // Just verify that response doesn't error and contains escaped value
        $this->assertStringContainsString("'\t", $content, 'Tab at start should be escaped with single quote');
    }

    public function test_csv_injection_prevention_carriage_return(): void
    {
        $injectionPeserta = User::factory()->create(['role' => 'peserta', 'email' => 'inject6@test.com']);

        Pendaftaran::factory()->create([
            'pelatihan_id' => $this->pelatihan->id,
            'peserta_id' => $injectionPeserta->id,
            'status_verifikasi' => 'diverifikasi',
            'data_diri' => [
                'nama' => "\r=1+1",
                'profesi' => 'Dokter',
                'instansi' => 'Rumah Sakit',
            ],
        ]);

        $panitia = User::factory()->create(['role' => 'panitia']);
        $response = $this->actingAs($panitia)
            ->get(route('pelatihan.kehadiran.export', $this->pelatihan));

        $content = $response->streamedContent();
        $rows = $this->parseCSV($content);

        $dataRows = array_slice($rows, 1);
        // Check nama is escaped
        $this->assertTrue(str_starts_with($dataRows[0][1], "'"));
    }

    public function test_csv_rows_ordered_by_nama(): void
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $response = $this->actingAs($panitia)
            ->get(route('pelatihan.kehadiran.export', $this->pelatihan));

        $content = $response->streamedContent();
        $rows = $this->parseCSV($content);

        $dataRows = array_slice($rows, 1);

        // Check alphabetical order: Ahmad before Budi
        $names = array_map(fn ($row) => $row[1], $dataRows);
        $this->assertEquals(['Ahmad Sutrisno', 'Budi Hartono'], $names);
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

    public function test_no_n_plus_one_queries(): void
    {
        // Create multiple pendaftarans
        for ($i = 0; $i < 5; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            Pendaftaran::factory()->create([
                'pelatihan_id' => $this->pelatihan->id,
                'peserta_id' => $peserta->id,
                'status_verifikasi' => 'diverifikasi',
                'kamar_id' => null,
                'hadir_at' => now(),
                'data_diri' => [
                    'nama' => "Peserta $i",
                    'profesi' => 'Dokter',
                    'instansi' => 'Rumah Sakit',
                ],
            ]);
        }

        // Count queries with a smaller set
        DB::enableQueryLog();
        $panitia = User::factory()->create(['role' => 'panitia']);
        $this->actingAs($panitia)
            ->get(route('pelatihan.kehadiran.export', $this->pelatihan));
        $queryCountLarge = count(DB::getQueryLog());
        DB::disableQueryLog();

        // The number of queries should be bounded (not N+1)
        // Expecting: 1 for pendaftarans + 1 for users (eager load)
        // Allow reasonable overhead but should not scale with N
        $this->assertLessThan(10, $queryCountLarge, 'Query count should not scale with pendaftaran count');
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
}
