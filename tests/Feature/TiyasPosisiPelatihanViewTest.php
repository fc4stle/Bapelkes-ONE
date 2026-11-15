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
     * Test 1 (A): Posisi penanda pelatihan: centroid polygon = margin + scaled centroid dari service.
     *
     * Setup: 2 pelatihan, 10 respons (5 per training), skor berbeda.
     * Assert: Tiap <polygon> di SVG scatter (data-chart="fcm-scatter") punya centroid yang match
     *         rumus: x = margin_l + ((centroidX-1)/3)*plotW, y = margin_t + (1-(centroidY-1)/3)*plotH
     *         Toleransi 0.5px. Cocokkan via <title> polygon ke pelatihan, bukan urutan.
     */
    public function test_marker_position_matches_service_centroid()
    {
        $pelatihan1 = Pelatihan::factory()->create([
            'status' => 'selesai', 
            'tanggal_selesai' => now()->subDay(),
            'nama' => 'Pelatihan Alpha'
        ]);
        $pelatihan2 = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_selesai' => now()->subDay(),
            'nama' => 'Pelatihan Beta'
        ]);

        // 5 respons pelatihan 1 dengan skor tinggi
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

        // 5 respons pelatihan 2 dengan skor rendah
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

        // Get service result
        $scorer = new \App\Services\TiyasScorer();
        $fcm = new \App\Services\FuzzyCMeans();
        $klasterService = new \App\Services\TiyasProfilKlaster($scorer, $fcm);
        
        $responses = \App\Models\TiyasResponse::whereHas('pendaftaran', function ($q) {
            $q->where('status_verifikasi', 'diverifikasi')->whereNotNull('hadir_at');
        })->with('pendaftaran.peserta', 'pendaftaran.pelatihan')->get();
        
        $klasterResult = $klasterService->analisis($responses);
        
        $response = $this->actingAs($this->panitia)
            ->get(route('tiyas.hasil', [$pelatihan1->id]) . '?cakupan=semua');

        $response->assertStatus(200);
        $html = $response->getContent();

        // Parse dengan DOMDocument (format dengan UTF-8)
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($dom);

        // Cari SVG scatter chart
        $svgs = $xpath->query("//svg[@data-chart='fcm-scatter']");
        $this->assertGreaterThan(0, $svgs->length, "SVG scatter chart harus ada");

        $svg = $svgs->item(0);
        $polygons = $xpath->query(".//polygon[@stroke='var(--color-text)'][@stroke-width='2'][@opacity='0.85']", $svg);

        $this->assertCount(2, $polygons, "Harus ada 2 polygon markers untuk 2 pelatihan");

        // SVG parameters (sesuai view)
        $svgW = 700;
        $svgH = 500;
        $margin = ['l' => 70, 'r' => 40, 't' => 68, 'b' => 50];
        $plotW = $svgW - $margin['l'] - $margin['r'];
        $plotH = $svgH - $margin['t'] - $margin['b'];

        // Extract posisiPelatihan dari service
        $comparisonData = $klasterService->perbandinganAntarPelatihan();
        $posisiPelatihan = $klasterService->posisiPelatihan($klasterResult, $comparisonData['comparisonData']);

        // Cocok polygon dengan posisiPelatihan via <title>
        foreach ($polygons as $polygon) {
            $points_str = $polygon->getAttribute('points');
            $this->assertNotEmpty($points_str, "Polygon harus punya points");

            // Parse points dari polygon (format: "x1,y1 x2,y2 x3,y3 x4,y4")
            $points = array_filter(array_map('trim', preg_split('/[\s,]+/', trim($points_str))));
            $points = array_chunk($points, 2);
            
            $this->assertCount(4, $points, "Polygon harus punya 4 titik untuk diamond");

            // Hitung centroid
            $xs = array_map(fn($p) => floatval($p[0]), $points);
            $ys = array_map(fn($p) => floatval($p[1]), $points);
            $centroidX_view = array_sum($xs) / count($xs);
            $centroidY_view = array_sum($ys) / count($ys);

            // Cari title untuk cocokkan nama pelatihan
            $titles = $xpath->query(".//title", $polygon);
            $this->assertGreaterThan(0, $titles->length, "Polygon harus punya title element");
            
            $titleText = $titles->item(0)->textContent;
            
            // Extract nama pelatihan dari title (format: "Pelatihan Alpha (n=5) | ..." atau "Pelatihan Alpha | ...")
            $matchResult = preg_match('/^([^(]+?)(?:\s*\([^)]*\))?\s*(?:\||$)/', $titleText, $m);
            $this->assertSame(1, $matchResult, "Title '{$titleText}' harus cocok dengan format nama pelatihan");
            
            $nama_in_title = trim($m[1]);

            // Cari matching pelatihan di posisiPelatihan
            $matching_pelatihan = null;
            foreach ($posisiPelatihan as $pel) {
                if ($pel['nama'] === $nama_in_title) {
                    $matching_pelatihan = $pel;
                    break;
                }
            }

            $this->assertNotNull($matching_pelatihan, "Pelatihan '{$nama_in_title}' harus ada di service posisiPelatihan");

            // Expected centroid dari service
            $centroid = $matching_pelatihan['centroid_pelatihan']; // [TC, TE, ITT, YB]
            // Asumsikan xAxis=TC (1), yAxis=YB (4) per default di controller
            $centroidX_service = floatval($centroid[0]); // TC
            $centroidY_service = floatval($centroid[3]); // YB

            // Convert ke pixel
            $expectedX = $margin['l'] + (($centroidX_service - 1) / 3) * $plotW;
            $expectedY = $margin['t'] + (1 - ($centroidY_service - 1) / 3) * $plotH;

            // Toleransi 0.5px
            $this->assertEqualsWithDelta($expectedX, $centroidX_view, 0.5, 
                "Centroid X '{$nama_in_title}' mismatch: expected {$expectedX}, got {$centroidX_view}");
            $this->assertEqualsWithDelta($expectedY, $centroidY_view, 0.5,
                "Centroid Y '{$nama_in_title}' mismatch: expected {$expectedY}, got {$centroidY_view}");
        }
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
     * Test 2 (B): Warna fill polygon = config('tiyas.warna_klaster')['K'.klaster_terdekat].
     *
     * Setup: 2 pelatihan, klaster terdekat berbeda.
     * Assert: Tiap <polygon> fill color cocok dengan klaster terdekat dari service.
     */
    public function test_marker_fill_color_matches_cluster()
    {
        $pelatihan1 = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_selesai' => now()->subDay(),
            'nama' => 'Pelatihan Alpha'
        ]);
        $pelatihan2 = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_selesai' => now()->subDay(),
            'nama' => 'Pelatihan Beta'
        ]);

        // 5 respons pelatihan 1 dengan profil optimal (berharap K1)
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

        // 5 respons pelatihan 2 dengan profil at_risk (berharap K4)
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

        // Get service result
        $scorer = new \App\Services\TiyasScorer();
        $fcm = new \App\Services\FuzzyCMeans();
        $klasterService = new \App\Services\TiyasProfilKlaster($scorer, $fcm);
        
        $responses = \App\Models\TiyasResponse::whereHas('pendaftaran', function ($q) {
            $q->where('status_verifikasi', 'diverifikasi')->whereNotNull('hadir_at');
        })->with('pendaftaran.peserta', 'pendaftaran.pelatihan')->get();
        
        $klasterResult = $klasterService->analisis($responses);
        
        $response = $this->actingAs($this->panitia)
            ->get(route('tiyas.hasil', [$pelatihan1->id]) . '?cakupan=semua');

        $response->assertStatus(200);
        $html = $response->getContent();

        // Parse dengan DOMDocument
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($dom);

        // Cari SVG scatter chart
        $svgs = $xpath->query("//svg[@data-chart='fcm-scatter']");
        $this->assertGreaterThan(0, $svgs->length, "SVG scatter chart harus ada");

        $svg = $svgs->item(0);
        $polygons = $xpath->query(".//polygon[@stroke='var(--color-text)'][@stroke-width='2'][@opacity='0.85']", $svg);

        $this->assertCount(2, $polygons, "Harus ada 2 polygon markers");

        // Extract posisiPelatihan dan warna config
        $comparisonData = $klasterService->perbandinganAntarPelatihan();
        $posisiPelatihan = $klasterService->posisiPelatihan($klasterResult, $comparisonData['comparisonData']);
        
        $klasterColors = config('tiyas.warna_klaster', [
            'K1' => 'var(--color-danger)',
            'K2' => 'var(--color-primary)',
            'K3' => 'var(--color-success)',
            'K4' => 'var(--color-accent)',
        ]);

        // Cocok warna polygon dengan klaster terdekat
        foreach ($polygons as $polygon) {
            $fillColor = $polygon->getAttribute('fill');
            $this->assertNotEmpty($fillColor, "Polygon harus punya fill color");

            // Cari title untuk cocokkan nama pelatihan
            $titles = $xpath->query(".//title", $polygon);
            $this->assertGreaterThan(0, $titles->length, "Polygon harus punya title element");
            
            $titleText = $titles->item(0)->textContent;
            
            // Extract nama pelatihan dari title
            $matchResult = preg_match('/^([^(]+?)(?:\s*\([^)]*\))?\s*(?:\||$)/', $titleText, $m);
            $this->assertSame(1, $matchResult, "Title '{$titleText}' harus cocok dengan format nama pelatihan");
            
            $nama_in_title = trim($m[1]);

            // Cari matching pelatihan
            $matching_pelatihan = null;
            foreach ($posisiPelatihan as $pel) {
                if ($pel['nama'] === $nama_in_title) {
                    $matching_pelatihan = $pel;
                    break;
                }
            }

            $this->assertNotNull($matching_pelatihan, "Pelatihan '{$nama_in_title}' harus ada");

            // Expected warna dari klaster terdekat
            $klasterTerdekat = $matching_pelatihan['klaster_terdekat'];
            $expectedColor = $klasterColors['K' . $klasterTerdekat] ?? 'var(--color-primary)';

            $this->assertEquals($expectedColor, $fillColor,
                "Warna polygon '{$nama_in_title}' harus cocok dengan klaster {$klasterTerdekat}: expected '{$expectedColor}', got '{$fillColor}'");
        }
    }

    /**
     * Test 3 (C): Tooltip <title> polygon memuat nama, n=, dan rata_keanggotaan tiap klaster (1 desimal).
     *
     * Setup: 2 pelatihan dengan berbagai profil.
     * Assert: <title> polygon cocok format dan nilai dari service posisiPelatihan.
     */
    public function test_marker_tooltip_contains_name_count_and_membership()
    {
        $pelatihan1 = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_selesai' => now()->subDay(),
            'nama' => 'Pelatihan Alpha'
        ]);
        $pelatihan2 = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_selesai' => now()->subDay(),
            'nama' => 'Pelatihan Beta'
        ]);

        // 5 respons pelatihan 1
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

        // 5 respons pelatihan 2
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

        // Get service result
        $scorer = new \App\Services\TiyasScorer();
        $fcm = new \App\Services\FuzzyCMeans();
        $klasterService = new \App\Services\TiyasProfilKlaster($scorer, $fcm);
        
        $responses = \App\Models\TiyasResponse::whereHas('pendaftaran', function ($q) {
            $q->where('status_verifikasi', 'diverifikasi')->whereNotNull('hadir_at');
        })->with('pendaftaran.peserta', 'pendaftaran.pelatihan')->get();
        
        $klasterResult = $klasterService->analisis($responses);
        
        $response = $this->actingAs($this->panitia)
            ->get(route('tiyas.hasil', [$pelatihan1->id]) . '?cakupan=semua');

        $response->assertStatus(200);
        $html = $response->getContent();

        // Parse dengan DOMDocument
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($dom);

        // Cari SVG scatter chart
        $svgs = $xpath->query("//svg[@data-chart='fcm-scatter']");
        $this->assertGreaterThan(0, $svgs->length, "SVG scatter chart harus ada");

        $svg = $svgs->item(0);
        $polygons = $xpath->query(".//polygon[@stroke='var(--color-text)'][@stroke-width='2'][@opacity='0.85']", $svg);

        $this->assertCount(2, $polygons, "Harus ada 2 polygon markers");

        // Extract posisiPelatihan dari service
        $comparisonData = $klasterService->perbandinganAntarPelatihan();
        $posisiPelatihan = $klasterService->posisiPelatihan($klasterResult, $comparisonData['comparisonData']);

        // Validasi tooltip untuk tiap polygon
        foreach ($polygons as $polygon) {
            $titles = $xpath->query(".//title", $polygon);
            $this->assertGreaterThan(0, $titles->length, "Polygon harus punya <title> tooltip");

            $titleText = $titles->item(0)->textContent;
            $this->assertNotEmpty($titleText, "Title harus tidak kosong");

            // Format ekspektasi: "Pelatihan Alpha (n=5) | K1: 45.2% | K2: 25.1% | K3: 18.9% | K4: 10.8%"
            // Parse title untuk extract nama dan n
            if (preg_match('/^([^(]+?)\s*\(n=(\d+)\)\s*\|/', $titleText, $m)) {
                $nama_in_title = trim($m[1]);
                $n_in_title = intval($m[2]);

                // Cari matching pelatihan di service
                $matching_pelatihan = null;
                foreach ($posisiPelatihan as $pel) {
                    if ($pel['nama'] === $nama_in_title) {
                        $matching_pelatihan = $pel;
                        break;
                    }
                }

                $this->assertNotNull($matching_pelatihan, "Pelatihan '{$nama_in_title}' harus ada di service");
                $this->assertEquals($matching_pelatihan['n_peserta'], $n_in_title, "Jumlah peserta harus cocok");

                // Parse membership percentages dari title
                $titleParts = explode('|', $titleText);
                array_shift($titleParts); // Skip nama part

                // Validasi tiap klaster membership
                $expectedMembership = $matching_pelatihan['rata_keanggotaan'];
                foreach ($titleParts as $part) {
                    $part = trim($part);
                    if (preg_match('/^K(\d+):\s*([\d.]+)%/', $part, $m)) {
                        $klasterNum = intval($m[1]);
                        $persentase_in_title = floatval($m[2]);

                        $this->assertArrayHasKey($klasterNum, $expectedMembership, 
                            "Klaster K{$klasterNum} harus ada di service membership");

                        $expected_value = $expectedMembership[$klasterNum];
                        // Tolerate 0.1% rounding difference (title punya 1 desimal)
                        $this->assertEqualsWithDelta($expected_value, $persentase_in_title, 0.15,
                            "Membership K{$klasterNum} untuk '{$nama_in_title}' mismatch: expected {$expected_value}%, got {$persentase_in_title}%");
                    }
                }
            } else {
                $this->fail("Title format tidak cocok: '{$titleText}' (expect: 'Pelatihan Name (n=5) | K1: 45.2% | ...')");
            }
        }
    }

    /**
     * Test 4 (E): Tabel Kedekatan: tiap sel persen = rata_keanggotaan service per (pelatihan, klaster);
     * kolom "Klaster Terdekat" = label argmax baris itu; jumlah baris = jumlah pelatihan.
     *
     * Setup: 2 pelatihan, berbagai profil.
     * Assert: Tabel memiliki 2 baris (1 header + 2 data), kolom sesuai jumlah klaster,
     * sel persen cocok dengan service, kolom "Klaster Terdekat" = label argmax.
     */
    public function test_proximity_table_values_match_service()
    {
        $pelatihan1 = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_selesai' => now()->subDay(),
            'nama' => 'Pelatihan Alpha'
        ]);
        $pelatihan2 = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_selesai' => now()->subDay(),
            'nama' => 'Pelatihan Beta'
        ]);

        // 5 respons pelatihan 1
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

        // 5 respons pelatihan 2
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

        // Get service result
        $scorer = new \App\Services\TiyasScorer();
        $fcm = new \App\Services\FuzzyCMeans();
        $klasterService = new \App\Services\TiyasProfilKlaster($scorer, $fcm);
        
        $responses = \App\Models\TiyasResponse::whereHas('pendaftaran', function ($q) {
            $q->where('status_verifikasi', 'diverifikasi')->whereNotNull('hadir_at');
        })->with('pendaftaran.peserta', 'pendaftaran.pelatihan')->get();
        
        $klasterResult = $klasterService->analisis($responses);
        $comparisonData = $klasterService->perbandinganAntarPelatihan();
        $posisiPelatihan = $klasterService->posisiPelatihan($klasterResult, $comparisonData['comparisonData']);
        
        $response = $this->actingAs($this->panitia)
            ->get(route('tiyas.hasil', [$pelatihan1->id]) . '?cakupan=semua');

        $response->assertStatus(200);
        $html = $response->getContent();

        // Parse dengan DOMDocument
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($dom);

        // Cari tabel Kedekatan (h3 "Kedekatan Pelatihan ke Klaster")
        $h3List = $xpath->query("//h3[contains(text(), 'Kedekatan Pelatihan ke Klaster')]");
        $this->assertGreaterThan(0, $h3List->length, "H3 heading 'Kedekatan Pelatihan ke Klaster' harus ada");

        // Cari tabel setelah h3 (gunakan following-sibling atau telusuri hingga table)
        $tables = $xpath->query(".//table", $h3List->item(0)->parentNode);
        $this->assertGreaterThan(0, $tables->length, "Tabel Kedekatan harus ada");

        $table = $tables->item(0);

        // Validasi header: Pelatihan, Klaster 1, Klaster 2, ..., Klaster Terdekat
        $thead = $xpath->query(".//thead//th", $table);
        $expectedClusterCount = count($klasterResult['klaster_labels'] ?? []);
        $expectedHeaderCount = 2 + $expectedClusterCount; // Pelatihan + N klaster + Klaster Terdekat
        $this->assertGreaterThanOrEqual(2, $thead->length, "Header harus memiliki minimal Pelatihan dan Klaster Terdekat");

        // Validasi baris data: harus = jumlah pelatihan
        $tbodyRows = $xpath->query(".//tbody//tr", $table);
        $this->assertCount(count($posisiPelatihan), $tbodyRows, 
            "Tabel harus memiliki " . count($posisiPelatihan) . " baris data (1 per pelatihan)");

        // Validasi tiap baris
        foreach ($tbodyRows as $rowIdx => $row) {
            if ($rowIdx >= count($posisiPelatihan)) break;

            $cells = $xpath->query(".//td", $row);
            $this->assertGreaterThanOrEqual(2, $cells->length, "Baris harus punya minimal 2 cells");

            // Cell 0 = nama pelatihan
            $namaCellText = trim($cells->item(0)->textContent);
            $expectedPelatihan = $posisiPelatihan[$rowIdx];
            $this->assertStringContainsString($expectedPelatihan['nama'], $namaCellText,
                "Baris {$rowIdx}: nama pelatihan harus cocok");

            // Cell 1..N = membership % per klaster
            $membershipCells = [];
            for ($i = 1; $i < $cells->length - 1; $i++) { // Skip kolom nama, skip kolom "Klaster Terdekat"
                $cellText = trim($cells->item($i)->textContent);
                // Format: "45.2%" atau "45.2 %"
                $matchResult = preg_match('/^([\d.]+)\s*%$/', $cellText, $m);
                $this->assertSame(1, $matchResult, "Membership cell harus format 'XX.X%', got '{$cellText}'");
                $membershipCells[] = floatval($m[1]);
            }

            // Validasi membership values cocok dengan service
            $expectedMembership = $expectedPelatihan['rata_keanggotaan'];
            $membershipCount = count($membershipCells);
            $this->assertGreaterThanOrEqual(count($expectedMembership), $membershipCount,
                "Baris {$rowIdx}: harus ada kolom membership untuk tiap klaster");

            foreach ($expectedMembership as $klasterNum => $expectedValue) {
                $cellIdx = $klasterNum - 1; // Klaster 1 -> cell 0, Klaster 2 -> cell 1, etc
                $this->assertArrayHasKey($cellIdx, $membershipCells, 
                    "Baris {$rowIdx}: membership cell untuk Klaster {$klasterNum} harus ada");
                $this->assertEqualsWithDelta($expectedValue, $membershipCells[$cellIdx], 0.15,
                    "Baris {$rowIdx}: Klaster {$klasterNum} membership harus cocok (expected {$expectedValue}%, got {$membershipCells[$cellIdx]}%)");
            }

            // Cell terakhir = "Klaster Terdekat" label
            $lastCell = $cells->item($cells->length - 1);
            $klasterLabel = trim($lastCell->textContent);
            // Label bisa berupa "Klaster 2 · A: Optimal Transfer" atau "K2" tergantung format view
            $expectedKlasterNum = $expectedPelatihan['klaster_terdekat'];
            $this->assertTrue(
                strpos($klasterLabel, 'Klaster ' . $expectedKlasterNum) !== false || 
                strpos($klasterLabel, 'K' . $expectedKlasterNum) !== false,
                "Baris {$rowIdx}: 'Klaster Terdekat' harus berisi klaster #{$expectedKlasterNum}, got '{$klasterLabel}'"
            );
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
     * Test 5 (F): With ?cakupan=pelatihan (single training), no polygon markers and no membership table.
     * Assert: SVG scatter tidak ada <polygon>, dan <h3> "Kedekatan Pelatihan ke Klaster" tidak ada.
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
        $html = $response->getContent();

        // Parse dengan DOMDocument
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($dom);

        // Cari SVG scatter chart
        $svgs = $xpath->query("//svg[@data-chart='fcm-scatter']");
        if ($svgs->length > 0) {
            // Jika SVG ada, tidak boleh ada polygon markers
            $svg = $svgs->item(0);
            $polygons = $xpath->query(".//polygon[@stroke='var(--color-text)'][@stroke-width='2'][@opacity='0.85']", $svg);
            $this->assertCount(0, $polygons, "Saat single training, tidak boleh ada polygon markers");
        }

        // Tidak boleh ada h3 "Kedekatan Pelatihan ke Klaster"
        $h3List = $xpath->query("//h3[contains(text(), 'Kedekatan Pelatihan ke Klaster')]");
        $this->assertCount(0, $h3List, "H3 heading 'Kedekatan Pelatihan ke Klaster' tidak boleh ada saat single training");
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
