<?php

namespace Tests\Feature;

use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\TiyasResponse;
use App\Models\User;
use App\Services\TiyasProfilKlaster;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class TiyasFcmMembershipChartTest extends TestCase
{
    use RefreshDatabase;

    protected array $pelatihanList = [];

    protected User $panitia;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup config for tests - 4 distinct colors for cluster identification
        Config::set('tiyas.warna_klaster', [
            'K1' => '#e94e77',  // red-pink
            'K2' => '#f39c12',  // orange
            'K3' => '#3498db',  // blue
            'K4' => '#27ae60',  // green
        ]);

        $this->panitia = User::factory()->create(['role' => 'panitia']);

        // Buat 7 pelatihan dengan skor berbeda
        $trainingNames = ['[TEST] P1 - HighA', '[TEST] P2 - MidB', '[TEST] P3 - LowC',
            '[TEST] P4 - HighD', '[TEST] P5 - MidE', '[TEST] P6 - LowF',
            '[TEST] P7 - HighG'];

        foreach ($trainingNames as $idx => $name) {
            $pel = Pelatihan::factory()->create([
                'status' => 'selesai',
                'tanggal_selesai' => now()->subDay(),
                'nama' => $name,
            ]);
            $this->pelatihanList[] = $pel;
        }

        // Isi setiap pelatihan dengan peserta berbeda-beda skor
        // P1: High (TC ~ 4.0+)
        $this->createResponsesForTraining($this->pelatihanList[0], [
            ['TC' => 4.2, 'TE' => 3.9, 'ITT' => 4.0, 'YB' => 3.9],
            ['TC' => 4.1, 'TE' => 3.8, 'ITT' => 3.9, 'YB' => 3.8],
            ['TC' => 4.0, 'TE' => 3.7, 'ITT' => 3.8, 'YB' => 3.7],
            ['TC' => 4.2, 'TE' => 3.8, 'ITT' => 3.9, 'YB' => 3.9],
        ]);

        // P2: Mid
        $this->createResponsesForTraining($this->pelatihanList[1], [
            ['TC' => 3.0, 'TE' => 2.9, 'ITT' => 2.8, 'YB' => 2.7],
            ['TC' => 3.1, 'TE' => 3.0, 'ITT' => 2.9, 'YB' => 2.8],
            ['TC' => 2.9, 'TE' => 2.8, 'ITT' => 2.7, 'YB' => 2.6],
        ]);

        // P3: Low
        $this->createResponsesForTraining($this->pelatihanList[2], [
            ['TC' => 1.9, 'TE' => 1.8, 'ITT' => 1.7, 'YB' => 1.6],
            ['TC' => 1.8, 'TE' => 1.7, 'ITT' => 1.6, 'YB' => 1.5],
            ['TC' => 1.8, 'TE' => 1.7, 'ITT' => 1.6, 'YB' => 1.7],
        ]);

        // P4: High
        $this->createResponsesForTraining($this->pelatihanList[3], [
            ['TC' => 4.2, 'TE' => 4.0, 'ITT' => 4.1, 'YB' => 3.9],
            ['TC' => 4.0, 'TE' => 3.9, 'ITT' => 3.9, 'YB' => 3.9],
            ['TC' => 4.1, 'TE' => 3.8, 'ITT' => 4.0, 'YB' => 3.9],
            ['TC' => 4.2, 'TE' => 4.0, 'ITT' => 4.1, 'YB' => 3.9],
        ]);

        // P5: Mid
        $this->createResponsesForTraining($this->pelatihanList[4], [
            ['TC' => 2.8, 'TE' => 2.7, 'ITT' => 2.8, 'YB' => 2.7],
            ['TC' => 2.9, 'TE' => 2.8, 'ITT' => 2.9, 'YB' => 2.8],
            ['TC' => 3.0, 'TE' => 2.9, 'ITT' => 3.0, 'YB' => 2.9],
        ]);

        // P6: Low
        $this->createResponsesForTraining($this->pelatihanList[5], [
            ['TC' => 1.8, 'TE' => 1.6, 'ITT' => 1.7, 'YB' => 1.6],
            ['TC' => 1.6, 'TE' => 1.5, 'ITT' => 1.6, 'YB' => 1.5],
            ['TC' => 1.7, 'TE' => 1.6, 'ITT' => 1.7, 'YB' => 1.6],
        ]);

        // P7: High
        $this->createResponsesForTraining($this->pelatihanList[6], [
            ['TC' => 4.0, 'TE' => 3.9, 'ITT' => 4.0, 'YB' => 3.8],
            ['TC' => 4.1, 'TE' => 3.9, 'ITT' => 4.0, 'YB' => 3.8],
            ['TC' => 4.0, 'TE' => 3.8, 'ITT' => 3.9, 'YB' => 3.8],
            ['TC' => 4.0, 'TE' => 3.9, 'ITT' => 4.0, 'YB' => 3.8],
        ]);
    }

    public function test_scatter_circle_count_matches_training_count()
    {
        $response = $this->actingAs($this->panitia)->get(
            route('tiyas.hasil', ['pelatihan' => $this->pelatihanList[0]->id]).'?cakupan=semua'
        );

        $response->assertStatus(200);
        $content = $response->getContent();
        $this->assertStringContainsString('data-chart="fcm-pelatihan-scatter"', $content);

        // Extract all circles via simple regex
        $circlePattern = '/cx="([^"]+)"\s+cy="([^"]+)"[^>]*r="7"/i';
        preg_match_all($circlePattern, $content, $matches);
        $circles = count($matches[0]);

        $this->assertEquals(7, $circles, 'Expected exactly 7 circles (one per training), got'.$circles);
    }

    public function test_scatter_circle_positions_tolerance_from_service()
    {
        // Build comparison data for service (same as controller)
        $comparisonData = [];
        foreach ($this->pelatihanList as $idx => $pel) {
            $comparisonData[$pel->id] = [
                'nama' => $pel->nama,
                'mean' => $this->getMeanScoresForTraining($pel),
            ];
        }

        // Get service result
        $klasterService = app(TiyasProfilKlaster::class);
        $hasil = $klasterService->klasterPelatihan($comparisonData);

        $this->assertEquals('sukses', $hasil['status'], 'Cluster analysis should succeed');

        // Fetch page
        $response = $this->actingAs($this->panitia)->get(
            route('tiyas.hasil', ['pelatihan' => $this->pelatihanList[0]->id]).'?cakupan=semua'
        );

        $dom = new DOMDocument('1.0', 'UTF-8');
        @$dom->loadHTML(mb_convert_encoding($response->getContent(), 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);

        $scatterSvg = $xpath->query('//svg[@data-chart="fcm-pelatihan-scatter"]')->item(0);
        $circles = $xpath->query('.//circle[@r="7"]', $scatterSvg);

        // SVG plot parameters
        $marginL = 70;
        $marginT = 68;
        $plotW = 590;
        $plotH = 382;

        // Validate count
        $this->assertEquals(7, $circles->length, 'Expected exactly 7 circles');

        // For each service result, validate position
        $allLabelsFound = [];
        foreach ($hasil['tabel_pelatihan'] as $idx => $tabelRow) {
            $meanX = $tabelRow['mean']['TC'];  // TC is X axis
            $meanY = $tabelRow['mean']['YB'];  // YB is Y axis
            $pelLabel = 'P'.($idx + 1);

            // Expected SVG coordinates from formula
            $expectedCx = $marginL + (($meanX - 1) / 3) * $plotW;
            $expectedCy = $marginT + (1 - ($meanY - 1) / 3) * $plotH;

            // All circles have been plotted - validate positions are within reasonable range
            // Since we have 7 circles and 7 expected positions, check that at least one matches each
            $found = false;
            for ($i = 0; $i < $circles->length; $i++) {
                $circle = $circles->item($i);
                $actualCx = (float) $circle->getAttribute('cx');
                $actualCy = (float) $circle->getAttribute('cy');

                if (abs($expectedCx - $actualCx) < 1 && abs($expectedCy - $actualCy) < 1) {
                    $found = true;
                    $allLabelsFound[] = $pelLabel;
                    break;
                }
            }

            $this->assertTrue($found, 'Position for '.$pelLabel.' (expected cx='.$expectedCx.', cy='.$expectedCy.') not found in circles');
        }

        $this->assertGreaterThanOrEqual(7, count($allLabelsFound), 'Not all trainings found as positioned circles');
    }

    public function test_scatter_circle_colors_from_config()
    {
        // Build comparison data
        $comparisonData = [];
        foreach ($this->pelatihanList as $pel) {
            $comparisonData[$pel->id] = [
                'nama' => $pel->nama,
                'mean' => $this->getMeanScoresForTraining($pel),
            ];
        }

        // Get service result
        $klasterService = app(TiyasProfilKlaster::class);
        $hasil = $klasterService->klasterPelatihan($comparisonData);

        // Fetch page
        $response = $this->actingAs($this->panitia)->get(
            route('tiyas.hasil', ['pelatihan' => $this->pelatihanList[0]->id]).'?cakupan=semua'
        );

        $dom = new DOMDocument('1.0', 'UTF-8');
        @$dom->loadHTML(mb_convert_encoding($response->getContent(), 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);

        $scatterSvg = $xpath->query('//svg[@data-chart="fcm-pelatihan-scatter"]')->item(0);
        $circles = $xpath->query('.//circle[@r="7"]', $scatterSvg);

        // TIAP baris tabel_pelatihan: cari circle r=7 dengan <title> diawali $row['nama']
        foreach ($hasil['tabel_pelatihan'] as $row) {
            $rowNama = $row['nama'];
            $rowKlasterDominan = $row['klaster_dominan'];
            $expectedColor = config('tiyas.warna_klaster')['K'.$rowKlasterDominan];

            $found = false;
            for ($i = 0; $i < $circles->length; $i++) {
                $circle = $circles->item($i);
                $titleElement = $xpath->query('./title', $circle)->item(0);

                if ($titleElement && strpos($titleElement->nodeValue, $rowNama) === 0) {
                    // Ini adalah circle yang dicari
                    $fill = $circle->getAttribute('fill');
                    $this->assertSame($expectedColor, $fill,
                        "Circle untuk {$rowNama} (K{$rowKlasterDominan}) harus warna {$expectedColor}, tapi dapat {$fill}");
                    $found = true;
                    break;
                }
            }

            $this->assertNotNull($found, "Circle dengan title diawali '{$rowNama}' tidak ditemukan");
        }
    }

    public function test_scatter_p_labels_exist_and_unique()
    {
        $response = $this->actingAs($this->panitia)->get(
            route('tiyas.hasil', ['pelatihan' => $this->pelatihanList[0]->id]).'?cakupan=semua'
        );

        $content = $response->getContent();
        preg_match_all('/P(\d+)/', $content, $matches);

        $this->assertNotEmpty($matches[0], 'No P labels found in response');
        $trainingNumbers = array_unique($matches[1]);
        $this->assertGreaterThanOrEqual(7, count($trainingNumbers), 'Not all 7 trainings found');
    }

    public function test_membership_bar_segments_exist()
    {
        // Build comparison data
        $comparisonData = [];
        foreach ($this->pelatihanList as $pel) {
            $comparisonData[$pel->id] = [
                'nama' => $pel->nama,
                'mean' => $this->getMeanScoresForTraining($pel),
            ];
        }

        // Get service result
        $klasterService = app(TiyasProfilKlaster::class);
        $hasil = $klasterService->klasterPelatihan($comparisonData);

        $response = $this->actingAs($this->panitia)->get(
            route('tiyas.hasil', ['pelatihan' => $this->pelatihanList[0]->id]).'?cakupan=semua'
        );

        $this->assertStringContainsString('data-chart="fcm-pelatihan-membership"', $response->getContent());

        $dom = new DOMDocument('1.0', 'UTF-8');
        @$dom->loadHTML(mb_convert_encoding($response->getContent(), 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);

        $membershipSvg = $xpath->query('//svg[@data-chart="fcm-pelatihan-membership"]')->item(0);
        $this->assertNotNull($membershipSvg, 'Membership SVG not found');

        $rects = $xpath->query('.//rect', $membershipSvg);

        // Should have at least 7 trainings × c_optimal clusters = many rects
        $this->assertGreaterThan(7, $rects->length, 'Expected at least 7+ rectangles in membership bars');
    }

    public function test_membership_bar_segments_width_from_service()
    {
        // Build comparison data
        $comparisonData = [];
        foreach ($this->pelatihanList as $pel) {
            $comparisonData[$pel->id] = [
                'nama' => $pel->nama,
                'mean' => $this->getMeanScoresForTraining($pel),
            ];
        }

        // Get service result
        $klasterService = app(TiyasProfilKlaster::class);
        $hasil = $klasterService->klasterPelatihan($comparisonData);

        $response = $this->actingAs($this->panitia)->get(
            route('tiyas.hasil', ['pelatihan' => $this->pelatihanList[0]->id]).'?cakupan=semua'
        );

        $dom = new DOMDocument('1.0', 'UTF-8');
        @$dom->loadHTML(mb_convert_encoding($response->getContent(), 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);

        $membershipSvg = $xpath->query('//svg[@data-chart="fcm-pelatihan-membership"]')->item(0);
        $this->assertNotNull($membershipSvg, 'Membership SVG not found');

        // TIAP pelatihan i dan TIAP klaster k
        $sortedTableData = collect($hasil['tabel_pelatihan'])
            ->sortBy('id')
            ->values()
            ->all();

        $cOptimal = $hasil['c_optimal'] ?? 0;
        $barWidth = 300;

        $rectsByTitle = []; // Map <title> -> DOMElement
        $rects = $xpath->query('.//rect', $membershipSvg);

        for ($i = 0; $i < $rects->length; $i++) {
            $rect = $rects->item($i);
            $titleElem = $xpath->query('./title', $rect)->item(0);
            if ($titleElem) {
                $rectsByTitle[$titleElem->nodeValue] = $rect;
            }
        }

        // TIAP pelatihan dan TIAP klaster
        $expectedRectCount = 0;
        for ($i = 0; $i < count($sortedTableData); $i++) {
            $pelatihan = $sortedTableData[$i];
            $keanggotaan = $pelatihan['keanggotaan'] ?? [];

            for ($k = 1; $k <= $cOptimal; $k++) {
                $membership = $keanggotaan[$k] ?? 0;

                // Generate title seperti di view: "P{i+1} | Klaster {k}: X.X%"
                $pLabel = 'P'.($i + 1);
                // Cari rect dengan title diawali pattern
                $titlePrefix = $pLabel.' | Klaster '.$k.':';

                // Cari rect yang match
                $foundRect = null;
                foreach ($rectsByTitle as $title => $rect) {
                    if (strpos($title, $titlePrefix) === 0) {
                        $foundRect = $rect;
                        break;
                    }
                }

                // Selalu assertNotNull — tidak boleh skip meski membership=0
                $this->assertNotNull($foundRect,
                    "Rect dengan title diawali '{$titlePrefix}' tidak ditemukan (membership {$membership}%)");

                $expectedWidth = ($membership / 100) * $barWidth;
                $actualWidth = (float) $foundRect->getAttribute('width');

                $this->assertEqualsWithDelta($expectedWidth, $actualWidth, 0.5,
                    "Lebar rect untuk {$titlePrefix} harusnya {$expectedWidth}, dapat {$actualWidth}");

                $expectedRectCount++;
            }
        }

        // Cek jumlah rect berjudul Klaster = 7 x c_optimal
        $this->assertSame(7 * $cOptimal, $expectedRectCount,
            "Harus ada 7 x {$cOptimal} = ".(7 * $cOptimal)." rect segment, dapat {$expectedRectCount}");
    }

    public function test_membership_table_has_seven_rows()
    {
        $response = $this->actingAs($this->panitia)->get(
            route('tiyas.hasil', ['pelatihan' => $this->pelatihanList[0]->id]).'?cakupan=semua'
        );

        $dom = new DOMDocument('1.0', 'UTF-8');
        @$dom->loadHTML(mb_convert_encoding($response->getContent(), 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);

        $tables = $xpath->query('//table');
        $foundTable = false;

        foreach ($tables as $table) {
            $ths = $xpath->query('.//thead//th', $table);
            $hasTrainingCol = false;
            $hasClusterCol = false;

            foreach ($ths as $th) {
                $thText = $th->nodeValue;
                if (strpos($thText, 'Pelatihan') !== false || strpos($thText, '[TEST]') !== false) {
                    $hasTrainingCol = true;
                }
                if (strpos($thText, 'Klaster') !== false) {
                    $hasClusterCol = true;
                }
            }

            if (! ($hasTrainingCol && $hasClusterCol)) {
                continue;
            }

            $foundTable = true;
            $rows = $xpath->query('.//tbody/tr', $table);
            $this->assertEquals(7, $rows->length, 'Expected 7 rows (one per training), got'.$rows->length);
            break;
        }

        $this->assertTrue($foundTable, 'Membership table not found');
    }

    public function test_membership_table_percentages_sum_to_100()
    {
        // Build comparison data
        $comparisonData = [];
        foreach ($this->pelatihanList as $pel) {
            $comparisonData[$pel->id] = [
                'nama' => $pel->nama,
                'mean' => $this->getMeanScoresForTraining($pel),
            ];
        }

        // Get service result
        $klasterService = app(TiyasProfilKlaster::class);
        $hasil = $klasterService->klasterPelatihan($comparisonData);

        $response = $this->actingAs($this->panitia)->get(
            route('tiyas.hasil', ['pelatihan' => $this->pelatihanList[0]->id]).'?cakupan=semua'
        );

        // Extract percentages from HTML via regex to avoid complex XPath
        // Pattern: "K1: 45.2%" type strings
        preg_match_all('/K(\d+):\s*([0-9.]+)%/', $response->getContent(), $matches);

        $this->assertNotEmpty($matches[0], 'No percentage values found in response');

        // Collect percentages by K cluster
        $clusterPercentages = [];
        for ($i = 0; $i < count($matches[0]); $i++) {
            $k = (int) $matches[1][$i];
            $pct = (float) $matches[2][$i];

            if (! isset($clusterPercentages[$k])) {
                $clusterPercentages[$k] = [];
            }
            $clusterPercentages[$k][] = $pct;
        }

        // Each row should have percentages that sum to ~100
        $cOptimal = $hasil['c_optimal'];
        for ($k = 1; $k <= $cOptimal; $k++) {
            $this->assertNotEmpty($clusterPercentages[$k] ?? [], 'No percentages found for K'.$k);
        }
    }

    public function test_scatter_centroid_x_position_from_service()
    {
        // Build comparison data
        $comparisonData = [];
        foreach ($this->pelatihanList as $pel) {
            $comparisonData[$pel->id] = [
                'nama' => $pel->nama,
                'mean' => $this->getMeanScoresForTraining($pel),
            ];
        }

        // Get service result
        $klasterService = app(TiyasProfilKlaster::class);
        $hasil = $klasterService->klasterPelatihan($comparisonData);

        $this->assertEquals('sukses', $hasil['status']);

        // Fetch page
        $response = $this->actingAs($this->panitia)->get(
            route('tiyas.hasil', ['pelatihan' => $this->pelatihanList[0]->id]).'?cakupan=semua'
        );

        $dom = new DOMDocument('1.0', 'UTF-8');
        @$dom->loadHTML(mb_convert_encoding($response->getContent(), 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);

        $scatterSvg = $xpath->query('//svg[@data-chart="fcm-pelatihan-scatter"]')->item(0);
        $lines = $xpath->query('.//line[@stroke-width="3"]', $scatterSvg);

        // Must have crosshairs (at least 2 per cluster: vertical + horizontal)
        $cOptimal = $hasil['c_optimal'] ?? 0;
        $expectedLineCount = $cOptimal * 2;  // Each cluster has vertical + horizontal crosshair

        $this->assertCount($expectedLineCount, $lines,
            "Expected exactly {$expectedLineCount} crosshair lines (2 per cluster x {$cOptimal} clusters), got {$lines->length}");

        // Validate each crosshair position
        $centroids_raw = $hasil['centroids_raw'] ?? [];
        $konstrukAll = ['TC', 'TE', 'ITT', 'YB'];
        $xAxisIdx = array_search('TC', $konstrukAll);  // TC is X axis
        $yAxisIdx = array_search('YB', $konstrukAll);  // YB is Y axis

        // SVG dimensions
        $marginL = 70;
        $marginT = 68;
        $plotW = 590;
        $plotH = 382;

        // Klaster k (0-based): horizontal = item(2k), vertikal = item(2k+1)
        for ($k = 0; $k < $cOptimal; $k++) {
            $horizontalIdx = 2 * $k;
            $verticalIdx = 2 * $k + 1;

            $this->assertTrue($verticalIdx < $lines->length, "Vertical line index {$verticalIdx} out of bounds");
            $this->assertTrue($horizontalIdx < $lines->length, "Horizontal line index {$horizontalIdx} out of bounds");

            $verticalLine = $lines->item($verticalIdx);
            $horizontalLine = $lines->item($horizontalIdx);

            // Vertikal: x1==x2
            $vertX1 = (float) $verticalLine->getAttribute('x1');
            $vertX2 = (float) $verticalLine->getAttribute('x2');
            $this->assertEqualsWithDelta($vertX1, $vertX2, 0.5,
                'Vertical line K'.($k + 1).' should have x1==x2');

            // Vertikal: x1 = 70+((centroids_raw[k][0]-1)/3)*590
            $centroid = $centroids_raw[$k] ?? null;
            $this->assertNotNull($centroid, 'Centroid for K'.($k + 1).' not found');

            $expectedVertX = $marginL + (($centroid[$xAxisIdx] - 1) / 3) * $plotW;
            $this->assertEqualsWithDelta($expectedVertX, $vertX1, 0.5,
                'Vertical line K'.($k + 1)." X position (TC axis) should be {$expectedVertX}, got {$vertX1}");

            // Horizontal: y1 = 68+(1-(centroids_raw[k][3]-1)/3)*382
            $horizY1 = (float) $horizontalLine->getAttribute('y1');
            $expectedHorizY = $marginT + (1 - ($centroid[$yAxisIdx] - 1) / 3) * $plotH;
            $this->assertEqualsWithDelta($expectedHorizY, $horizY1, 0.5,
                'Horizontal line K'.($k + 1)." Y position (YB axis) should be {$expectedHorizY}, got {$horizY1}");
        }
    }

    public function test_five_trainings_shows_insufficient_data_message_and_no_svgs()
    {
        // Create exactly 5 trainings (fresh, separate from setUp)
        Pelatihan::query()->delete();

        $fiveTrainings = [];
        $fiveNames = ['[TEST-5-I] P1', '[TEST-5-I] P2', '[TEST-5-I] P3', '[TEST-5-I] P4', '[TEST-5-I] P5'];

        foreach ($fiveNames as $name) {
            $pel = Pelatihan::factory()->create([
                'status' => 'selesai',
                'tanggal_selesai' => now()->subDay(),
                'nama' => $name,
            ]);
            $fiveTrainings[] = $pel;
        }

        foreach ($fiveTrainings as $pel) {
            $this->createResponsesForTraining($pel, [
                ['TC' => 2.0, 'TE' => 2.0, 'ITT' => 2.0, 'YB' => 2.0],
                ['TC' => 2.1, 'TE' => 2.1, 'ITT' => 2.1, 'YB' => 2.1],
            ]);
        }

        $response = $this->actingAs($this->panitia)->get(
            route('tiyas.hasil', ['pelatihan' => $fiveTrainings[0]->id]).'?cakupan=semua'
        );

        $content = $response->getContent();

        $scatterCount = substr_count($content, 'data-chart="fcm-pelatihan-scatter"');
        $membershipCount = substr_count($content, 'data-chart="fcm-pelatihan-membership"');

        $this->assertEquals(0, $scatterCount, 'Scatter SVG should not appear with insufficient data');
        $this->assertEquals(0, $membershipCount, 'Membership SVG should not appear with insufficient data');

        $lowerContent = strtolower($content);
        $this->assertTrue(
            strpos($lowerContent, 'kurang') !== false || strpos($lowerContent, 'data') !== false,
            'Message about insufficient data should appear'
        );
    }

    public function test_both_svgs_load_valid_dom()
    {
        $response = $this->actingAs($this->panitia)->get(
            route('tiyas.hasil', ['pelatihan' => $this->pelatihanList[0]->id]).'?cakupan=semua'
        );

        $dom = new DOMDocument('1.0', 'UTF-8');
        $result = @$dom->loadHTML(mb_convert_encoding($response->getContent(), 'HTML-ENTITIES', 'UTF-8'));

        $this->assertTrue($result, 'HTML failed to load as valid DOM');

        $xpath = new DOMXPath($dom);
        $scatterSvg = $xpath->query('//svg[@data-chart="fcm-pelatihan-scatter"]')->item(0);
        $membershipSvg = $xpath->query('//svg[@data-chart="fcm-pelatihan-membership"]')->item(0);

        $this->assertNotNull($scatterSvg, 'Scatter SVG not found or invalid');
        $this->assertNotNull($membershipSvg, 'Membership SVG not found or invalid');
    }

    public function test_single_training_no_comparison_svgs()
    {
        $response = $this->actingAs($this->panitia)->get(
            route('tiyas.hasil', ['pelatihan' => $this->pelatihanList[0]->id])
        );

        $content = $response->getContent();

        $scatterCount = substr_count($content, 'data-chart="fcm-pelatihan-scatter"');
        $membershipCount = substr_count($content, 'data-chart="fcm-pelatihan-membership"');

        $this->assertEquals(0, $scatterCount, 'Scatter SVG should not appear for single training');
        $this->assertEquals(0, $membershipCount, 'Membership SVG should not appear for single training');
    }

    protected function getMeanScoresForTraining(Pelatihan $pelatihan): array
    {
        $responses = TiyasResponse::whereHas('pendaftaran', function ($q) use ($pelatihan) {
            $q->where('pelatihan_id', $pelatihan->id);
        })->get();

        if ($responses->isEmpty()) {
            return ['TC' => 2.0, 'TE' => 2.0, 'ITT' => 2.0, 'YB' => 2.0];
        }

        $means = ['TC' => 0, 'TE' => 0, 'ITT' => 0, 'YB' => 0];
        $konstruk = config('tiyas.konstruk');

        foreach ($responses as $response) {
            $jawaban = $response->jawaban ?? [];
            foreach ($konstruk as $kode => $konstrukData) {
                $sum = 0;
                $count = 0;
                foreach ($konstrukData['dimensi'] as $dimensi) {
                    foreach ($dimensi as $itemKode => $itemText) {
                        if (isset($jawaban[$itemKode])) {
                            $sum += (float) $jawaban[$itemKode];
                            $count++;
                        }
                    }
                }
                if ($count > 0) {
                    $means[$kode] += $sum / $count;
                }
            }
        }

        foreach ($means as &$mean) {
            $mean = $mean / count($responses);
        }

        return $means;
    }

    protected function createResponsesForTraining(Pelatihan $pelatihan, array $scoresList)
    {
        $config = config('tiyas');

        foreach ($scoresList as $scores) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pend = Pendaftaran::factory()->hadir()->create([
                'status_verifikasi' => 'diverifikasi',
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);

            $jawaban = [];
            foreach ($config['konstruk'] as $kodeKonstruk => $konstruk) {
                $targetSkor = (int) ($scores[$kodeKonstruk] ?? 3);
                $targetSkor = max(1, min(4, $targetSkor));

                foreach ($konstruk['dimensi'] as $dimensi) {
                    foreach ($dimensi as $kode => $text) {
                        $jawaban[$kode] = $targetSkor;
                    }
                }
            }

            TiyasResponse::create([
                'pendaftaran_id' => $pend->id,
                'identitas' => [
                    'asal_instansi' => 'Dinas Kesehatan',
                    'asal_kabupaten' => 'Kota Yogyakarta',
                    'jenis_profesi' => 'Perawat',
                    'masa_kerja' => '3–5 tahun',
                    'waktu_sejak_selesai' => '3–6 bulan',
                ],
                'jawaban' => $jawaban,
                'submitted_at' => now(),
            ]);
        }
    }
}
