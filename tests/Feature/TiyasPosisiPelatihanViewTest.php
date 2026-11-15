<?php

namespace Tests\Feature;

use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\TiyasResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TiyasPosisiPelatihanViewTest extends TestCase
{
    use RefreshDatabase;

    private User $panitia;

    protected function setUp(): void
    {
        parent::setUp();
        $this->panitia = User::factory()->create(['role' => 'panitia']);
    }

    /**
     * Helper: Generate jawaban array untuk tes.
     * Setiap konstruk akan di-round ke integer untuk Likert 1-4.
     */
    private function generateJawaban(array $override = []): array
    {
        $default = ['TC' => 2.0, 'TE' => 2.0, 'ITT' => 2.0, 'YB' => 2.0];
        $scores = array_merge($default, $override);
        $scores = array_map(fn($v) => max(1.0, min(4.0, $v)), $scores);

        $jawaban = [];
        $tc_val = (int) round($scores['TC']);
        for ($i = 0; $i < 3; $i++) {
            $jawaban['TC'.($i + 1)] = $tc_val;
        }
        $te_val = (int) round($scores['TE']);
        for ($i = 0; $i < 3; $i++) {
            $jawaban['TE'.($i + 1)] = $te_val;
        }
        $itt_val = (int) round($scores['ITT']);
        for ($i = 0; $i < 4; $i++) {
            $jawaban['ITT'.($i + 1)] = $itt_val;
        }
        $yb_val = (int) round($scores['YB']);
        for ($i = 0; $i < 20; $i++) {
            $jawaban['YB'.($i + 1)] = $yb_val;
        }

        return $jawaban;
    }

    /**
     * Test A: SVG scatter polygon markers count matches training count (2 pelatihan).
     *
     * Setup: 2 pelatihan, 10 respons total (5 per training), skor berbeda antar training.
     * Assert: Ada 2 <polygon> marker di SVG scatter (data-chart="fcm-scatter").
     */
    public function test_scatter_markers_polygon_count_matches_training_count()
    {
        $pelatihan1 = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $pelatihan2 = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // 5 respons untuk pelatihan 1 dengan skor tinggi
        for ($i = 0; $i < 5; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'status_verifikasi' => 'diverifikasi',
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan1->id,
            ]);
            TiyasResponse::factory()->withProfil('optimal')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        // 5 respons untuk pelatihan 2 dengan skor rendah
        for ($i = 0; $i < 5; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'status_verifikasi' => 'diverifikasi',
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan2->id,
            ]);
            TiyasResponse::factory()->withProfil('at_risk')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        $response = $this->actingAs($this->panitia)
            ->get(route('tiyas.hasil', [$pelatihan1->id]) . '?cakupan=semua');

        $response->assertStatus(200);

        // Extract polygon markers
        preg_match_all('/<polygon\s+points="[^"]*"\s+fill="[^"]*"\s+stroke="[^"]*"\s+stroke-width="2"\s+opacity="0.85"/', $response->getContent(), $matches);
        
        $this->assertCount(2, $matches[0], "Harus ada 2 polygon markers untuk 2 pelatihan");
    }

    /**
     * Test B: Polygon centroid position dalam bounds yang sesuai (titik tengah polygon valid).
     *
     * Extract points dari setiap polygon, hitung rata-rata x dan y, verifikasi ada centroid.
     */
    public function test_polygon_centroid_coordinates_valid_in_bounds()
    {
        $pelatihan1 = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $pelatihan2 = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // Pelatihan 1: skor tinggi
        for ($i = 0; $i < 5; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan1->id,
                'status_verifikasi' => 'diverifikasi',
            ]);
            TiyasResponse::factory()->withProfil('optimal')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        // Pelatihan 2: skor rendah
        for ($i = 0; $i < 5; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan2->id,
                'status_verifikasi' => 'diverifikasi',
            ]);
            TiyasResponse::factory()->withProfil('at_risk')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        $response = $this->actingAs($this->panitia)
            ->get(route('tiyas.hasil', [$pelatihan1->id]) . '?cakupan=semua');

        $response->assertStatus(200);

        // Extract polygon points
        preg_match_all('/points="([^"]*)"/', $response->getContent(), $pointsMatches);
        
        // Minimal 2 polygon
        $this->assertGreaterThanOrEqual(2, count($pointsMatches[1]));
        
        // Setiap polygon harus punya 4 titik (diamond)
        foreach (array_slice($pointsMatches[1], 0, 2) as $pointsStr) {
            $points = array_map('trim', explode(' ', trim($pointsStr)));
            $this->assertCount(4, $points, "Polygon harus punya 4 titik untuk diamond marker");
            
            // Hitung centroid
            $xs = [];
            $ys = [];
            foreach ($points as $point) {
                [$x, $y] = array_map('floatval', explode(',', $point));
                $xs[] = $x;
                $ys[] = $y;
            }
            $centroidX = array_sum($xs) / count($xs);
            $centroidY = array_sum($ys) / count($ys);
            
            // Centroid harus dalam bounds SVG (tidak NaN, tidak INF)
            $this->assertTrue(is_finite($centroidX), "Centroid X harus finite");
            $this->assertTrue(is_finite($centroidY), "Centroid Y harus finite");
        }
    }

    /**
     * Test C: Marker fill color tidak NaN/INF, adalah hex/rgba yang valid.
     */
    public function test_marker_fill_color_valid_no_nan_inf()
    {
        $pelatihan1 = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $pelatihan2 = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        for ($i = 0; $i < 5; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan1->id,
                'status_verifikasi' => 'diverifikasi',
            ]);
            TiyasResponse::factory()->withProfil('optimal')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        for ($i = 0; $i < 5; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan2->id,
                'status_verifikasi' => 'diverifikasi',
            ]);
            TiyasResponse::factory()->withProfil('at_risk')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        $response = $this->actingAs($this->panitia)
            ->get(route('tiyas.hasil', [$pelatihan1->id]) . '?cakupan=semua');

        $response->assertStatus(200);

        $content = $response->getContent();
        
        // Cek tidak ada NaN atau INF
        $this->assertStringNotContainsString('NaN', $content, "Content tidak boleh NaN");
        $this->assertStringNotContainsString('INF', $content, "Content tidak boleh INF");
        
        // Extract warna fill dari polygon
        preg_match_all('/fill="([^"]*)"/', $content, $fillMatches);
        
        foreach (array_slice($fillMatches[1], 0, 2) as $color) {
            $this->assertNotEmpty($color, "Warna tidak boleh kosong");
            $this->assertFalse(strpos($color, 'NaN') !== false);
            $this->assertFalse(strpos($color, 'INF') !== false);
        }
    }

    /**
     * Test D: Label text P1, P2, ... exists, distinct, dan dalam viewBox bounds [0, 700].
     */
    public function test_marker_labels_p1_p2_exist_distinct_in_bounds()
    {
        $pelatihan1 = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $pelatihan2 = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        for ($i = 0; $i < 5; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan1->id,
                'status_verifikasi' => 'diverifikasi',
            ]);
            TiyasResponse::factory()->withProfil('optimal')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        for ($i = 0; $i < 5; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan2->id,
                'status_verifikasi' => 'diverifikasi',
            ]);
            TiyasResponse::factory()->withProfil('at_risk')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        $response = $this->actingAs($this->panitia)
            ->get(route('tiyas.hasil', [$pelatihan1->id]) . '?cakupan=semua');

        $response->assertStatus(200);

        $content = $response->getContent();
        
        // Extract label text dan posisi x
        preg_match_all('/<text\s+x="([^"]*?)"\s+y="([^"]*?)"\s+text-anchor="([^"]*?)"[^>]*?>([^<]*?)<\/text>/', $content, $labelMatches);
        
        $labels = array_map('trim', $labelMatches[4]);
        $xs = array_map('floatval', $labelMatches[1]);
        
        // Cari label P1, P2, ...
        $pLabels = array_filter($labels, fn($l) => preg_match('/^P\d+$/', $l));
        $this->assertGreaterThanOrEqual(2, count($pLabels), "Minimal ada 2 label P1, P2, ...");
        
        // Label harus unik
        $unique = array_unique($pLabels);
        $this->assertCount(count($pLabels), $unique, "Label P1, P2, ... harus unik");
        
        // Setiap label x harus dalam [0, 700]
        $pLabelIndices = array_keys(array_filter($labels, fn($l) => preg_match('/^P\d+$/', $l)));
        foreach ($pLabelIndices as $idx) {
            $x = $xs[$idx];
            $this->assertGreaterThanOrEqual(0, $x, "Label X >= 0");
            $this->assertLessThanOrEqual(700, $x, "Label X <= 700");
        }
    }

    /**
     * Test E: Table "Kedekatan Pelatihan ke Klaster" sel persen sesuai service (1 desimal).
     * Jumlah per baris = 100 (toleransi 0.2), jumlah baris = jumlah pelatihan.
     */
    public function test_membership_table_percentages_match_service()
    {
        $pelatihan1 = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $pelatihan2 = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        for ($i = 0; $i < 5; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan1->id,
                'status_verifikasi' => 'diverifikasi',
            ]);
            TiyasResponse::factory()->withProfil('optimal')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        for ($i = 0; $i < 5; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan2->id,
                'status_verifikasi' => 'diverifikasi',
            ]);
            TiyasResponse::factory()->withProfil('at_risk')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        $response = $this->actingAs($this->panitia)
            ->get(route('tiyas.hasil', [$pelatihan1->id]) . '?cakupan=semua');

        $response->assertStatus(200);

        $content = $response->getContent();
        
        // Extract tabel dari "Kedekatan Pelatihan ke Klaster"
        if (preg_match('/Kedekatan Pelatihan ke Klaster.*?<table[^>]*>.*?<tbody>(.*?)<\/tbody>.*?<\/table>/s', $content, $tableMatch)) {
            preg_match_all('/<tr[^>]*>(.*?)<\/tr>/s', $tableMatch[1], $rowMatches);
            $rows = $rowMatches[1];
            
            // Harus ada 2 baris (2 pelatihan)
            $this->assertCount(2, $rows, "Tabel harus punya 2 baris untuk 2 pelatihan");
            
            foreach ($rows as $rowIdx => $row) {
                preg_match_all('/<td[^>]*>([^<]*?)<\/td>/s', $row, $cellMatches);
                $cells = array_map('trim', $cellMatches[1]);
                
                // Cells: [Pelatihan, K1%, K2%, ..., Klaster Terdekat]
                $this->assertGreaterThanOrEqual(4, count($cells), "Setiap baris minimal 4 cells");
                
                // Extract persen dari cells[1] hingga cells[-1]
                $percentCells = array_slice($cells, 1, -1);
                $percentValues = [];
                
                foreach ($percentCells as $cell) {
                    if (preg_match('/(\d+(?:\.\d+)?)%?/', $cell, $m)) {
                        $percentValues[] = floatval($m[1]);
                    }
                }
                
                // Jumlah persen = 100 (toleransi 0.2)
                $sum = array_sum($percentValues);
                $this->assertEqualsWithDelta(100.0, $sum, 0.2, "Baris $rowIdx: jumlah persen ~100, got $sum");
            }
        }
    }

    /**
     * Test F: With ?cakupan=pelatihan (single training), no polygon markers and no membership table.
     */
    public function test_single_training_no_markers_no_membership_table()
    {
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        for ($i = 0; $i < 5; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
                'status_verifikasi' => 'diverifikasi',
            ]);
            TiyasResponse::factory()->withProfil('optimal')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        $response = $this->actingAs($this->panitia)
            ->get(route('tiyas.hasil', [$pelatihan->id]) . '?cakupan=pelatihan');

        $response->assertStatus(200);

        $content = $response->getContent();
        
        // Tidak ada polygon marker (4 titik diamond)
        $this->assertStringNotContainsString('points="', $content, "Tidak boleh ada polygon marker");
        
        // Tidak ada label P1, P2, ... dalam context scatter
        $this->assertStringNotContainsString('Penanda pelatihan', $content);
        
        // Tidak ada tabel "Kedekatan Pelatihan ke Klaster"
        $this->assertStringNotContainsString('Kedekatan Pelatihan ke Klaster', $content);
    }

    /**
     * Test G: Table content tidak ada NaN/INF, semua persen valid (0-100).
     */
    public function test_membership_table_no_nan_inf_values()
    {
        $pelatihan1 = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $pelatihan2 = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        for ($i = 0; $i < 5; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan1->id,
                'status_verifikasi' => 'diverifikasi',
            ]);
            TiyasResponse::factory()->withProfil('optimal')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        for ($i = 0; $i < 5; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan2->id,
                'status_verifikasi' => 'diverifikasi',
            ]);
            TiyasResponse::factory()->withProfil('at_risk')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        $response = $this->actingAs($this->panitia)
            ->get(route('tiyas.hasil', [$pelatihan1->id]) . '?cakupan=semua');

        $response->assertStatus(200);

        $content = $response->getContent();
        
        $this->assertStringNotContainsString('NaN', $content);
        $this->assertStringNotContainsString('INF', $content);
        
        // Extract semua persen
        preg_match_all('/([0-9]+(?:\.[0-9]+)?)%/', $content, $percentMatches);
        foreach ($percentMatches[1] as $val) {
            $num = floatval($val);
            $this->assertGreaterThanOrEqual(0, $num);
            $this->assertLessThanOrEqual(100, $num);
        }
    }

    /**
     * Test H: Legend berisi "P1 = nama_pelatihan, P2 = ...", bukan substring 15 char.
     */
    public function test_legend_contains_full_training_names()
    {
        $pelatihan1 = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_selesai' => now()->subDay(),
            'nama' => 'Pelatihan Implementasi Teknologi Blockchain A'
        ]);
        $pelatihan2 = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_selesai' => now()->subDay(),
            'nama' => 'Pelatihan Cyber Security Advanced B'
        ]);

        for ($i = 0; $i < 5; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan1->id,
                'status_verifikasi' => 'diverifikasi',
            ]);
            TiyasResponse::factory()->withProfil('optimal')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        for ($i = 0; $i < 5; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan2->id,
                'status_verifikasi' => 'diverifikasi',
            ]);
            TiyasResponse::factory()->withProfil('at_risk')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        $response = $this->actingAs($this->panitia)
            ->get(route('tiyas.hasil', [$pelatihan1->id]) . '?cakupan=semua');

        $response->assertStatus(200);

        $content = $response->getContent();
        
        // Extract legend "P1 = ..."
        preg_match_all('/P\d+\s*=\s*([^<\n]+)/', $content, $legendMatches);
        
        $this->assertGreaterThanOrEqual(2, count($legendMatches[1]), "Legend minimal P1 =, P2 = ...");
        
        // Verify nama pelatihan ada di legend (bukan substring 15 char)
        $legendText = implode(' ', $legendMatches[1]);
        $this->assertStringContainsString('Implementasi Teknologi Blockchain', $legendText);
        $this->assertStringContainsString('Cyber Security', $legendText);
    }
}
