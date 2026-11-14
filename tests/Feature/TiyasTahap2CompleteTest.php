<?php

namespace Tests\Feature;

use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\TiyasResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TiyasTahap2CompleteTest extends TestCase
{
    use RefreshDatabase;

    private Pelatihan $pelA;

    private Pelatihan $pelB;

    private User $panitia;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup pelatihans
        $this->pelA = Pelatihan::factory()->create(['nama' => 'Pelatihan A', 'status' => 'selesai']);
        $this->pelB = Pelatihan::factory()->create(['nama' => 'Pelatihan B', 'status' => 'selesai']);

        // Setup peserta terverifikasi untuk PelA (10 peserta)
        $pesertaA = [];
        for ($i = 1; $i <= 10; $i++) {
            $pesertaA[] = User::factory()->create(['role' => 'peserta']);
        }

        foreach ($pesertaA as $peserta) {
            Pendaftaran::factory()->hadir()->create([
                'pelatihan_id' => $this->pelA->id,
                'peserta_id' => $peserta->id,
                'status_verifikasi' => 'diverifikasi',
            ]);
        }

        // Setup peserta terverifikasi untuk PelB (10 peserta untuk comparison tests)
        $pesertaB = [];
        for ($i = 1; $i <= 10; $i++) {
            $pesertaB[] = User::factory()->create(['role' => 'peserta']);
        }

        foreach ($pesertaB as $peserta) {
            Pendaftaran::factory()->hadir()->create([
                'pelatihan_id' => $this->pelB->id,
                'peserta_id' => $peserta->id,
                'status_verifikasi' => 'diverifikasi',
            ]);
        }

        // Panitia yang bisa lihat
        $this->panitia = User::factory()->create(['role' => 'panitia']);
    }

    /**
     * Helper: buat responses dengan skor tertentu
     * Skor: array [TC, TE, ITT, YB]
     */
    private function createResponses(Pelatihan $pel, array $skorList): void
    {
        $config = config('tiyas');
        $pendaftarans = $pel->pendaftarans;
        $i = 0;
        foreach ($pendaftarans as $pendaftaran) {
            if ($i >= count($skorList)) {
                break;
            }

            // Build jawaban JSON dengan semua kode dari config tiyas
            $jawaban = [];
            $skorTarget = $skorList[$i];  // [TC_skor, TE_skor, ITT_skor, YB_skor]
            $j = 0;
            foreach ($config['konstruk'] as $kodeKonstruk => $konstruk) {
                $targetScore = $skorTarget[$j] ?? 3;
                foreach ($konstruk['dimensi'] as $dimensi) {
                    foreach ($dimensi as $kode => $text) {
                        $jawaban[$kode] = (int) $targetScore;
                    }
                }
                $j++;
            }

            TiyasResponse::factory()->create([
                'pendaftaran_id' => $pendaftaran->id,
                'jawaban' => $jawaban,
            ]);
            $i++;
        }
    }

    /**
     * T2.1: Default cakupan = 'pelatihan', hanya responses pelatihan itu
     */
    public function test_default_cakupan_pelatihan_single(): void
    {
        // PelA: 2 responses; PelB: 1 response
        $this->createResponses($this->pelA, [[4, 4, 4, 4], [4, 4, 4, 4]]);
        $this->createResponses($this->pelB, [[1, 1, 1, 1]]);

        $response = $this->actingAs($this->panitia)
            ->get(route('tiyas.hasil', $this->pelA));

        $response->assertOk();
        // Assert cakupan = pelatihan
        $response->assertViewHas('cakupan', 'pelatihan');
        // Assert totalResponses = 2 (only pelA)
        $response->assertViewHas('totalResponses', 2);
    }

    /**
     * T2.2: cakupan=semua aggregates all pelatihans
     */
    public function test_cakupan_semua_aggregate_all(): void
    {
        $this->createResponses($this->pelA, [[4, 4, 4, 4], [4, 4, 4, 4]]);
        $this->createResponses($this->pelB, [[1, 1, 1, 1]]);

        $response = $this->actingAs($this->panitia)
            ->get(route('tiyas.hasil', $this->pelA).'?cakupan=semua');

        $response->assertOk();
        $response->assertViewHas('cakupan', 'semua');
        // Total 3 responses from both pelatihans
        $response->assertViewHas('totalResponses', 3);
    }

    /**
     * T2.3: rerata skor di tabel "Rerata Konstruk" exact match
     */
    public function test_rerata_konstruk_table_exact_values(): void
    {
        // PelA: 2 responses dengan skor 4 semua
        $this->createResponses($this->pelA, [[4, 4, 4, 4], [4, 4, 4, 4]]);

        $response = $this->actingAs($this->panitia)
            ->get(route('tiyas.hasil', $this->pelA));

        $response->assertOk();
        $skorRataRata = $response->viewData('skorRataRata');

        // Assert rerata = 4.00 untuk semua konstruk
        $this->assertEquals(4.0, $skorRataRata['TC']['skor']);
        $this->assertEquals(4.0, $skorRataRata['TE']['skor']);
        $this->assertEquals(4.0, $skorRataRata['ITT']['skor']);
        $this->assertEquals(4.0, $skorRataRata['YB']['skor']);

        // Assert kategori = Tinggi
        $this->assertEquals('Tinggi', $skorRataRata['TC']['kategori']);
        $this->assertEquals('Tinggi', $skorRataRata['TE']['kategori']);
        $this->assertEquals('Tinggi', $skorRataRata['ITT']['kategori']);
        $this->assertEquals('Tinggi', $skorRataRata['YB']['kategori']);
    }

    /**
     * T2.4: rerata skor = 1.00 kategori Rendah
     */
    public function test_rerata_konstruk_skor_1_is_rendah(): void
    {
        $this->createResponses($this->pelA, [[1, 1, 1, 1], [1, 1, 1, 1]]);

        $response = $this->actingAs($this->panitia)
            ->get(route('tiyas.hasil', $this->pelA));

        $response->assertOk();
        $skorRataRata = $response->viewData('skorRataRata');

        $this->assertEquals(1.0, $skorRataRata['TC']['skor']);
        $this->assertEquals('Rendah', $skorRataRata['TC']['kategori']);
    }

    /**
     * T2.5: Komposisi klaster - total baris = N responses
     */
    public function test_komposisi_klaster_row_sum_equals_responses(): void
    {
        // PelA: 10 responses
        $skorList = array_fill(0, 10, [2, 2, 2, 2]);
        $this->createResponses($this->pelA, $skorList);

        $response = $this->actingAs($this->panitia)
            ->get(route('tiyas.hasil', $this->pelA));

        $response->assertOk();
        $klasterResult = $response->viewData('klasterResult');
        $tabelPeserta = $klasterResult['tabel_peserta'] ?? [];

        // Total rows must = 10 responses
        $this->assertCount(10, $tabelPeserta);
    }

    /**
     * T2.6: cakupan=semua comparison - multiple pelatihan dengan nama
     */
    public function test_cakupan_semua_has_comparison_data(): void
    {
        $this->createResponses($this->pelA, [[4, 4, 4, 4], [4, 4, 4, 4]]);
        $this->createResponses($this->pelB, [[1, 1, 1, 1]]);

        $response = $this->actingAs($this->panitia)
            ->get(route('tiyas.hasil', $this->pelA).'?cakupan=semua');

        $response->assertOk();
        $comparisonData = $response->viewData('comparisonData') ?? [];

        // Must have 2 pelatihans in comparison
        $this->assertCount(2, $comparisonData);

        // PelA entry: nama, count, rerata
        $this->assertEquals($this->pelA->nama, $comparisonData[$this->pelA->id]['nama']);
        $this->assertEquals(2, $comparisonData[$this->pelA->id]['count']);
        $this->assertEquals(4.0, $comparisonData[$this->pelA->id]['mean']['TC']);
        $this->assertEquals(4.0, $comparisonData[$this->pelA->id]['mean']['TE']);

        // PelB entry
        $this->assertEquals($this->pelB->nama, $comparisonData[$this->pelB->id]['nama']);
        $this->assertEquals(1, $comparisonData[$this->pelB->id]['count']);
        $this->assertEquals(1.0, $comparisonData[$this->pelB->id]['mean']['TC']);
    }

    /**
     * T2.7: < 8 responses show neutral message, NO diagram
     */
    public function test_less_than_8_responses_neutral_message(): void
    {
        $this->createResponses($this->pelA, [[2, 2, 2, 2], [2, 2, 2, 2]]);

        $response = $this->actingAs($this->panitia)
            ->get(route('tiyas.hasil', $this->pelA));

        $response->assertOk();
        $html = $response->getContent();

        // Must have neutral message
        $this->assertStringContainsString('minimal 8 peserta', $html);

        // Must NOT have scatter plot chart
        $this->assertStringNotContainsString('data-chart="fcm-scatter"', $html);
        $this->assertStringNotContainsString('Sebaran Peserta', $html);
    }

    /**
     * T2.8: >= 8 responses has form and scatter plot
     */
    public function test_8_or_more_responses_has_form_and_plot(): void
    {
        // Create exactly 8 responses
        $skorList = array_fill(0, 8, [2, 2, 2, 2]);

        // Need 8 peserta untuk pelA
        for ($i = 0; $i < 8; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            Pendaftaran::factory()->hadir()->create([
                'pelatihan_id' => $this->pelA->id,
                'peserta_id' => $peserta->id,
                'status_verifikasi' => 'diverifikasi',
            ]);
        }

        $this->createResponses($this->pelA, $skorList);

        $response = $this->actingAs($this->panitia)
            ->get(route('tiyas.hasil', $this->pelA));

        $response->assertOk();
        $html = $response->getContent();

        // Must have form (axis selector)
        $this->assertStringContainsString('name="x"', $html);
        $this->assertStringContainsString('name="y"', $html);

        // Must have scatter plot
        $this->assertStringContainsString('data-chart="fcm-scatter"', $html);
        $this->assertStringContainsString('Sebaran Peserta', $html);
    }

    /**
     * T2.9: cakupan=semua single pelatihan has no comparison
     */
    public function test_cakupan_semua_single_pelatihan_neutral(): void
    {
        // Only PelA has >= 8 responses untuk kualifikasi analisis
        $skorList = array_fill(0, 8, [2, 2, 2, 2]);
        $this->createResponses($this->pelA, $skorList);
        // PelB has no responses

        $response = $this->actingAs($this->panitia)
            ->get(route('tiyas.hasil', $this->pelA).'?cakupan=semua');

        $response->assertOk();
        $html = $response->getContent();

        // Must show neutral message (no comparison available - only 1 pelatihan dengan data)
        $this->assertStringContainsString('Perbandingan butuh minimal 2', $html);

        // Must NOT have comparison chart
        $this->assertStringNotContainsString('data-chart="antar-pelatihan-mean"', $html);
    }

    /**
     * T2.10: Peserta cannot view hasil
     */
    public function test_peserta_403_forbidden(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);

        $response = $this->actingAs($peserta)
            ->get(route('tiyas.hasil', $this->pelA));

        $response->assertForbidden();
    }

    /**
     * T2.11: Scatter plot title includes pelatihan name when cakupan=semua
     */
    public function test_scatter_plot_title_shows_pelatihan_name_semua(): void
    {
        // 8+ responses for scatter to render
        for ($i = 0; $i < 8; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            Pendaftaran::factory()->hadir()->create([
                'pelatihan_id' => $this->pelA->id,
                'peserta_id' => $peserta->id,
                'status_verifikasi' => 'diverifikasi',
            ]);
        }
        $this->createResponses($this->pelA, array_fill(0, 8, [2, 2, 2, 2]));

        $response = $this->actingAs($this->panitia)
            ->get(route('tiyas.hasil', $this->pelA).'?cakupan=semua');

        $response->assertOk();
        $html = $response->getContent();

        // Scatter title should include pelatihan name (when cakupan=semua)
        $this->assertStringContainsString($this->pelA->nama, $html);
    }

    /**
     * T2.12: No hardcoded colors, no PCA/Ellipse strings
     */
    public function test_no_hardcoded_colors_no_invalid_strings(): void
    {
        $this->createResponses($this->pelA, [[2, 2, 2, 2]]);

        $response = $this->actingAs($this->panitia)
            ->get(route('tiyas.hasil', $this->pelA));

        $response->assertOk();
        $html = $response->getContent();

        // NO hardcoded color hex codes
        $this->assertStringNotContainsString('#FF0000', $html);
        $this->assertStringNotContainsString('#00FF00', $html);
        $this->assertStringNotContainsString('#0000FF', $html);

        // NO "PCA" or "Ellipse" strings (unless in comment, which can't be tested)
        // This is best-effort; Blade comments are also rendered as HTML comments
        // So we check the actual view content
        $this->assertStringNotContainsString('PCA', $html);
        $this->assertStringNotContainsString('Ellipse', $html);
    }

    /**
     * T2.13: HTML Rendering - Comparison Table content verification (2 pelatihan)
     * Verifies: tabel "Perbandingan Antar Pelatihan" renders with means and categories
     * Simple string-based assertions without complex parsing
     */

    /**
     * T2.13: HTML Rendering - Comparison Table with DOMDocument/XPath
     * Uses DOMDocument + XPath to verify table structure and values
     */
    public function test_html_rendering_comparison_table_two_trainings(): void
    {
        // PelA: 10 responses dengan skor 4
        $this->createResponses($this->pelA, array_fill(0, 10, [4, 4, 4, 4]));

        // PelB: 10 responses dengan skor 1
        $this->createResponses($this->pelB, array_fill(0, 10, [1, 1, 1, 1]));

        // Debug: check DB has responses
        $countA = TiyasResponse::whereHas('pendaftaran', fn ($q) => $q->where('pelatihan_id', $this->pelA->id))->count();
        $countB = TiyasResponse::whereHas('pendaftaran', fn ($q) => $q->where('pelatihan_id', $this->pelB->id))->count();
        fwrite(STDERR, "DEBUG: PelA responses=$countA, PelB responses=$countB\n");

        $response = $this->actingAs($this->panitia)
            ->get(route('tiyas.hasil', $this->pelA).'?cakupan=semua');

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('Perbandingan Antar Pelatihan', $html, 'Comparison section should exist');
        $this->assertStringContainsString('Pelatihan A', $html, 'PelA should be in HTML');
        $this->assertStringContainsString('Pelatihan B', $html, 'PelB should be in HTML');

        // Parse HTML dengan DOMDocument
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($dom);

        // Find tabel dengan header "Perbandingan Antar Pelatihan"
        // Spesifik: cari div yang langsung setelah h2 "Perbandingan Antar Pelatihan",
        // lalu cari table di dalamnya
        // (Tidak gunakan ancestor karena akan catch multiple tables)
        $rows = $xpath->query("//h2[contains(text(), 'Perbandingan Antar Pelatihan')]/following-sibling::div[1]//table[1]//tr[td]");

        $foundPelA = false;
        $foundPelB = false;

        foreach ($rows as $row) {
            $cellText = '';
            $cells = $row->getElementsByTagName('td');

            // Aggregate text dari semua cells
            foreach ($cells as $cell) {
                $cellText .= ' '.$cell->textContent;
            }

            // Check untuk PelA
            if (strpos($cellText, 'Pelatihan A') !== false) {
                // PelA punya 10 respons, mean TC = 4.00, kategori Tinggi
                $this->assertStringContainsString('10', $cellText, 'PelA should show 10 responses');
                $this->assertStringContainsString('4.00', $cellText, 'PelA should show mean 4.00');
                $this->assertStringContainsString('Tinggi', $cellText, 'PelA should show Tinggi category');
                $foundPelA = true;
            }

            // Check untuk PelB
            if (strpos($cellText, 'Pelatihan B') !== false) {
                // PelB punya 10 respons, mean TC = 1.00, kategori Rendah
                $this->assertStringContainsString('10', $cellText, 'PelB should show 10 responses');
                $this->assertStringContainsString('1.00', $cellText, 'PelB should show mean 1.00');
                $this->assertStringContainsString('Rendah', $cellText, 'PelB should show Rendah category');
                $foundPelB = true;
            }
        }

        $this->assertTrue($foundPelA, 'Should find row with Pelatihan A data');
        $this->assertTrue($foundPelB, 'Should find row with Pelatihan B data');
    }

    /**
     * T2.14: HTML Rendering - Chart with DOMDocument/XPath
     * Verifies: SVG chart dengan data-chart="antar-pelatihan-mean" ada
     */
    public function test_html_rendering_chart_bar_groups_match_training_count(): void
    {
        // 2 pelatihan dengan 10 respons masing-masing
        $this->createResponses($this->pelA, array_fill(0, 10, [4, 4, 4, 4]));
        $this->createResponses($this->pelB, array_fill(0, 10, [1, 1, 1, 1]));

        $response = $this->actingAs($this->panitia)
            ->get(route('tiyas.hasil', $this->pelA).'?cakupan=semua');

        $response->assertOk();
        $html = $response->getContent();

        // Parse dengan DOMDocument
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($dom);

        // Find SVG element dengan data-chart="antar-pelatihan-mean"
        $svgs = $xpath->query('//svg[@data-chart="antar-pelatihan-mean"]');
        $this->assertGreaterThan(0, $svgs->length, 'Should have SVG chart with data-chart="antar-pelatihan-mean"');

        $svg = $svgs->item(0);

        // Count rect elements (bars) in SVG
        // Dengan 2 pelatihan dan 4 konstruk (TC, TE, ITT, YB), harus ada 8 rect (2*4)
        $rects = $xpath->query('.//rect', $svg);
        $this->assertGreaterThanOrEqual(8, $rects->length, 'Should have at least 8 rect elements (2 trainings * 4 constructs)');
    }

    /**
     * T2.15: HTML Rendering - Cluster composition row sums with DOMDocument/XPath
     */
    public function test_html_rendering_cluster_composition_row_sums(): void
    {
        // PelA: 10 responses, PelB: 1 response
        $this->createResponses($this->pelA, array_fill(0, 10, [4, 4, 4, 4]));
        $this->createResponses($this->pelB, [[1, 1, 1, 1]]);

        $response = $this->actingAs($this->panitia)
            ->get(route('tiyas.hasil', $this->pelA).'?cakupan=semua');

        $response->assertOk();
        $html = $response->getContent();

        // Parse dengan DOMDocument
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($dom);

        // Find tabel Komposisi Klaster - look for rows dengan pelatihan names
        // XPath: cari h3 dengan "Komposisi Klaster per Pelatihan", lalu table setelahnya
        $rows = $xpath->query("//h3[contains(text(), 'Komposisi Klaster per Pelatihan')]/ancestor::div[1]//table//tr[td]");

        $foundPelA = false;
        $foundPelB = false;

        foreach ($rows as $row) {
            $cells = $row->getElementsByTagName('td');
            if ($cells->length < 2) {
                continue;
            }

            $firstCellText = trim($cells->item(0)->textContent);

            if (strpos($firstCellText, 'Pelatihan A') !== false) {
                // Sum numeric cells (cluster counts), skip first cell (nama)
                $sumA = 0;
                for ($i = 1; $i < $cells->length; $i++) {
                    $cellText = trim($cells->item($i)->textContent);
                    if (is_numeric($cellText)) {
                        $sumA += (int) $cellText;
                    }
                }
                // PelA punya 10 responses
                $this->assertGreaterThanOrEqual(10, $sumA, 'Pelatihan A cluster composition should sum to at least 10');
                $foundPelA = true;
            }

            if (strpos($firstCellText, 'Pelatihan B') !== false) {
                // Sum numeric cells untuk PelB
                $sumB = 0;
                for ($i = 1; $i < $cells->length; $i++) {
                    $cellText = trim($cells->item($i)->textContent);
                    if (is_numeric($cellText)) {
                        $sumB += (int) $cellText;
                    }
                }
                // PelB punya 1 response
                $this->assertGreaterThanOrEqual(1, $sumB, 'Pelatihan B cluster composition should sum to at least 1');
                $foundPelB = true;
            }
        }

        $this->assertTrue($foundPelA, 'Should find cluster composition row for Pelatihan A');
        $this->assertTrue($foundPelB, 'Should find cluster composition row for Pelatihan B');
    }

    /**
     * T2.16: HTML Rendering - Single training neutral message, no chart
     */
    public function test_html_rendering_single_training_neutral_message(): void
    {
        // Only PelA with 10 responses
        $this->createResponses($this->pelA, array_fill(0, 10, [4, 4, 4, 4]));

        $response = $this->actingAs($this->panitia)
            ->get(route('tiyas.hasil', $this->pelA).'?cakupan=semua');

        $response->assertOk();
        $html = $response->getContent();

        // Parse dengan DOMDocument
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($dom);

        // Check 1: Neutral message exists
        $this->assertStringContainsString('Perbandingan butuh minimal', $html, 'Neutral message should exist for single training');

        // Check 2: SVG chart DOES NOT exist
        $svgs = $xpath->query('//svg[@data-chart="antar-pelatihan-mean"]');
        $this->assertEquals(0, $svgs->length, 'Chart SVG should NOT exist for single training case');
    }
}
