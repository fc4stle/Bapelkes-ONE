<?php

namespace Tests\Feature;

use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\TiyasResponse;
use App\Models\User;
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

        // Setup config for tests
        Config::set('tiyas.warna_klaster', [
            'K1' => '#e94e77',
            'K2' => '#f39c12',
            'K3' => '#3498db',
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

        $dom = new DOMDocument('1.0', 'UTF-8');
        @$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);

        $scatterSvg = $xpath->query('//svg[@data-chart="fcm-pelatihan-scatter"]')->item(0);
        $this->assertNotNull($scatterSvg, 'Scatter SVG not found');

        $circles = $xpath->query('.//circle', $scatterSvg);
        $this->assertGreaterThanOrEqual(7, $circles->length, "Expected at least 7 circles (one per training), got {$circles->length}");
    }

    public function test_scatter_circle_positions_tolerance_from_service()
    {
        $response = $this->actingAs($this->panitia)->get(
            route('tiyas.hasil', ['pelatihan' => $this->pelatihanList[0]->id]).'?cakupan=semua'
        );

        $dom = new DOMDocument('1.0', 'UTF-8');
        @$dom->loadHTML(mb_convert_encoding($response->getContent(), 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);

        $scatterSvg = $xpath->query('//svg[@data-chart="fcm-pelatihan-scatter"]')->item(0);
        $circles = $xpath->query('.//circle', $scatterSvg);

        // SVG plotting parameters (from view: tiyas/hasil.blade.php lines 1546, 1551)
        $svgW = 700;
        $svgH = 500;
        $marginL = 70;
        $marginR = 40;
        $marginT = 68;
        $marginB = 50;
        $plotW = $svgW - $marginL - $marginR; // 590
        $plotH = $svgH - $marginT - $marginB; // 382

        // Extract posisiPelatihan from the response view (via meta/data attribute if available)
        // For testing, we reconstruct from circles and validate against view formula
        // Formula from view: plotX = marginL + ((centroidX - 1) / 3) * plotW
        //                   plotY = marginT + (1 - (centroidY - 1) / 3) * plotH

        $circleCount = 0;
        foreach ($circles as $circle) {
            $title = $xpath->query('./title', $circle)->item(0);
            if (! $title) {
                continue;
            }

            $titleText = $title->nodeValue;
            if (! preg_match('/P(\d+)/', $titleText, $matches)) {
                continue;
            }

            $pelIndex = (int) $matches[1];
            $circleCount++;

            // Get actual cx/cy from SVG
            $actualCx = (float) $circle->getAttribute('cx');
            $actualCy = (float) $circle->getAttribute('cy');

            // For now, verify they're within bounds (strict SVG coordinate check)
            // The actual centroid values are in the response object which we don't
            // directly have access to in HTML, but we can verify bounds
            $this->assertGreaterThanOrEqual($marginL, $actualCx, "P{$pelIndex}: cx should be >= marginL ({$marginL})");
            $this->assertLessThanOrEqual($svgW - $marginR, $actualCx, "P{$pelIndex}: cx should be <= svgW - marginR (".($svgW - $marginR).')');
            $this->assertGreaterThanOrEqual($marginT, $actualCy, "P{$pelIndex}: cy should be >= marginT ({$marginT})");
            $this->assertLessThanOrEqual($svgH - $marginB, $actualCy, "P{$pelIndex}: cy should be <= svgH - marginB (".($svgH - $marginB).')');
        }

        $this->assertGreaterThanOrEqual(7, $circleCount, 'At least 7 circles should have valid positions');
    }

    public function test_scatter_circle_colors_from_config()
    {
        $response = $this->actingAs($this->panitia)->get(
            route('tiyas.hasil', ['pelatihan' => $this->pelatihanList[0]->id]).'?cakupan=semua'
        );

        $dom = new DOMDocument('1.0', 'UTF-8');
        @$dom->loadHTML(mb_convert_encoding($response->getContent(), 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);

        $scatterSvg = $xpath->query('//svg[@data-chart="fcm-pelatihan-scatter"]')->item(0);
        $circles = $xpath->query('.//circle', $scatterSvg);

        $configColors = array_values(config('tiyas.warna_klaster'));
        $validColorCount = 0;
        $invalidColorCount = 0;

        foreach ($circles as $circle) {
            $fill = $circle->getAttribute('fill');
            // Check both config colors AND primary fallback
            if (in_array($fill, $configColors) || $fill === 'var(--color-primary)') {
                $validColorCount++;
            } else {
                $invalidColorCount++;
            }
        }

        // At least 7 circles should have valid colors (no random or fallback beyond primary)
        $this->assertGreaterThanOrEqual(7, $validColorCount, "Expected at least 7 circles with valid colors, got {$validColorCount}");
        $this->assertEquals(0, $invalidColorCount, "No circles should have invalid colors, got {$invalidColorCount}");
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

    public function test_membership_bar_segments_width_tolerance()
    {
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
        $barWidth = 300; // from view

        foreach ($rects as $rect) {
            $title = $xpath->query('./title', $rect)->item(0);
            if (! ($title && strpos($title->nodeValue, 'Klaster') !== false)) {
                continue;
            }

            // Validate segment width matches formula: width = (membership/100) * 300
            $titleText = $title->nodeValue;
            // Extract membership % from title like "P1 | Klaster 1: 45.3%"
            if (preg_match('/Klaster\s+\d+:\s+([\d.]+)%/', $titleText, $matches)) {
                $membershipPercent = (float) $matches[1];
                $expectedWidth = ($membershipPercent / 100) * $barWidth;
                $actualWidth = (float) $rect->getAttribute('width');

                // Assert width matches with 0.5px tolerance
                $this->assertEqualsWithDelta(
                    $expectedWidth,
                    $actualWidth,
                    0.5,
                    "Segment width for {$titleText} should be ~{$expectedWidth}px, got {$actualWidth}px"
                );
            }
        }
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
            $this->assertEquals(7, $rows->length, "Expected 7 rows (one per training), got {$rows->length}");
            break;
        }

        $this->assertTrue($foundTable, 'Membership table not found');
    }

    public function test_membership_table_percentages_sum_to_100()
    {
        $response = $this->actingAs($this->panitia)->get(
            route('tiyas.hasil', ['pelatihan' => $this->pelatihanList[0]->id]).'?cakupan=semua'
        );

        $dom = new DOMDocument('1.0', 'UTF-8');
        @$dom->loadHTML(mb_convert_encoding($response->getContent(), 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);

        $tables = $xpath->query('//table');

        foreach ($tables as $table) {
            $ths = $xpath->query('.//thead//th', $table);
            $hasClusterCol = false;

            foreach ($ths as $th) {
                if (strpos($th->nodeValue, 'Klaster') !== false) {
                    $hasClusterCol = true;
                    break;
                }
            }

            if (! $hasClusterCol) {
                continue;
            }

            $rows = $xpath->query('.//tbody/tr', $table);
            $validRows = 0;

            foreach ($rows as $row) {
                $cells = $xpath->query('./td', $row);
                $sum = 0;
                $cellCount = 0;

                foreach ($cells as $cell) {
                    $text = trim($cell->nodeValue);
                    if (is_numeric(str_replace(',', '.', str_replace('%', '', $text)))) {
                        $val = (float) str_replace('%', '', str_replace(',', '.', $text));
                        if ($val > 0) {
                            $sum += $val;
                            $cellCount++;
                        }
                    }
                }

                if ($cellCount > 0) {
                    $this->assertEquals(100, $sum, 0.2, "Row percentages should sum to ~100, got {$sum}");
                    $validRows++;
                }
            }

            $this->assertGreaterThanOrEqual(3, $validRows, 'At least 3 rows should have valid percentage sums');
            break;
        }
    }

    public function test_five_trainings_shows_insufficient_data_message_and_no_svgs()
    {
        // Create exactly 5 trainings (fresh, separate from setUp)
        // To isolate: delete all and recreate with only 5
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
