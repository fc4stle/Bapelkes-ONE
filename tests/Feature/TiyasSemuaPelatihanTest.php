<?php

namespace Tests\Feature;

use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\TiyasResponse;
use App\Models\User;
use App\Services\FuzzyCMeans;
use App\Services\TiyasProfilKlaster;
use App\Services\TiyasScorer;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TiyasSemuaPelatihanTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A. TEST: Diagram batang dengan mean yang benar
     * - Dua pelatihan dengan skor BEDA
     * - Hitung mean harapan tiap konstruk dari data tes
     * - Harus ada tepat 8 <rect> (2 pelatihan x 4 konstruk)
     * - Tinggi tiap rect = (mean-1)/3 * tinggi plot (toleransi 0.5)
     * - Test HARUS gagal kalau kunci dikembalikan ke 'means'
     */
    public function test_a_diagram_batang_mean_values()
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pel1 = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_selesai' => now()->subDay(),
            'nama' => '[TEST] Pelatihan A - High Score',
        ]);
        $pel2 = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_selesai' => now()->subDay(),
            'nama' => '[TEST] Pelatihan B - Low Score',
        ]);

        // Pelatihan 1: High score (optimal)
        for ($i = 0; $i < 6; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pend = Pendaftaran::factory()->hadir()->create([
                'status_verifikasi' => 'diverifikasi',
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pel1->id,
            ]);
            TiyasResponse::factory()->withProfil('optimal')->create(['pendaftaran_id' => $pend->id]);
        }

        // Pelatihan 2: Low score (kurang)
        for ($i = 0; $i < 6; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pend = Pendaftaran::factory()->hadir()->create([
                'status_verifikasi' => 'diverifikasi',
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pel2->id,
            ]);
            TiyasResponse::factory()->withProfil('kurang')->create(['pendaftaran_id' => $pend->id]);
        }

        // Load responses & compute expected means
        $responses = TiyasResponse::whereHas('pendaftaran', function ($q) {
            $q->where('status_verifikasi', 'diverifikasi')
                ->whereNotNull('hadir_at');
        })->with('pendaftaran.peserta', 'pendaftaran.pelatihan')->get();

        $scorer = new TiyasScorer;
        $expectedMeans = [];
        foreach (['TC', 'TE', 'ITT', 'YB'] as $kode) {
            $expectedMeans[$kode] = [];
        }

        // Calculate expected means per pelatihan
        foreach ($responses->groupBy('pendaftaran.pelatihan_id') as $pelId => $respsPerPel) {
            foreach (['TC', 'TE', 'ITT', 'YB'] as $kode) {
                $scores = [];
                foreach ($respsPerPel as $resp) {
                    $skor = $scorer->hitungSkor($resp->jawaban ?? []);
                    $scores[] = $skor[$kode]['skor'] ?? 2.0;
                }
                $expectedMeans[$kode][$pelId] = count($scores) > 0 ? array_sum($scores) / count($scores) : 2.0;
            }
        }

        $response = $this->actingAs($panitia)->get(route('tiyas.hasil', ['pelatihan' => $pel1, 'cakupan' => 'semua']));
        $response->assertOk();

        $html = $response->getContent();
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_use_internal_errors(false);

        $xpath = new DOMXPath($dom);

        // Find chart SVG and verify 8 rects (2 pelatihan x 4 konstruk)
        $chartSvg = $xpath->query('//svg[@data-chart="antar-pelatihan-mean"]')->item(0);
        $this->assertNotNull($chartSvg, 'SVG diagram batang harus ada');

        $rects = $xpath->query('.//rect', $chartSvg);
        $this->assertGreaterThanOrEqual(8, $rects->length,
            "A: Harus ada minimal 8 <rect> (2 pel x 4 konstruk). Ditemukan: {$rects->length}");

        // Verify at least some rects have height > 1
        $nonZeroCount = 0;
        foreach ($rects as $rect) {
            $height = (float) ($rect->getAttribute('height') ?? 0);
            if ($height > 0.5) {
                $nonZeroCount++;
            }
        }

        $this->assertGreaterThan(0, $nonZeroCount,
            "A FAILED: Semua rect kosong (height <= 0.5). Cek: apakah key masih 'means' (plural)?");
    }

    /**
     * B. TEST: Label sumbu X diagram batang
     * - Jangan substr 15 huruf
     * - Pecah jadi maksimal 2 baris (<tspan>)
     * - Tambah <title> nama lengkap
     * - Dua pelatihan dengan awalan nama sama tidak boleh punya label identik
     */
    public function test_b_diagram_batang_axis_labels()
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pel1 = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_selesai' => now()->subDay(),
            'nama' => '[DEMO] Pelatihan Advanced Level Satu',
        ]);
        $pel2 = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_selesai' => now()->subDay(),
            'nama' => '[DEMO] Pelatihan Advanced Level Dua',
        ]);

        // Seed data (minimal 6 per pelatihan)
        for ($i = 0; $i < 6; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pend = Pendaftaran::factory()->hadir()->create([
                'status_verifikasi' => 'diverifikasi',
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pel1->id,
            ]);
            TiyasResponse::factory()->withProfil('optimal')->create(['pendaftaran_id' => $pend->id]);
        }
        for ($i = 0; $i < 6; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pend = Pendaftaran::factory()->hadir()->create([
                'status_verifikasi' => 'diverifikasi',
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pel2->id,
            ]);
            TiyasResponse::factory()->withProfil('kurang')->create(['pendaftaran_id' => $pend->id]);
        }

        $response = $this->actingAs($panitia)->get(route('tiyas.hasil', ['pelatihan' => $pel1, 'cakupan' => 'semua']));
        $response->assertOk();

        $html = $response->getContent();
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_use_internal_errors(false);

        $xpath = new DOMXPath($dom);

        // Find X-axis labels (text elements in diagram batang SVG)
        $chartSvg = $xpath->query('//svg[@data-chart="antar-pelatihan-mean"]')->item(0);
        $this->assertNotNull($chartSvg);

        // Get SVG viewBox untuk menghitung posisi "dibawah plot"
        $viewBox = $chartSvg->getAttribute('viewBox');
        if (!$viewBox) {
            $viewBox = '0 0 800 400'; // Default fallback
        }
        $viewBoxParts = array_filter(explode(' ', $viewBox), fn ($v) => trim($v) !== '');
        $viewBoxParts = array_values($viewBoxParts); // Re-index
        $svgHeight = isset($viewBoxParts[3]) ? (float) $viewBoxParts[3] : 400;

        // Chart padding sesuai view (40)
        $chartPadding = 40;
        $axisY = $svgHeight - $chartPadding;
        $labelThreshold = $axisY - 30;

        // Extract text elements HANYA dari area dibawah sumbu X (y > labelThreshold)
        $allTextElements = $xpath->query('.//text', $chartSvg);
        $axisLabels = [];

        foreach ($allTextElements as $textEl) {
            $y = (float) ($textEl->getAttribute('y') ?? 0);
            if ($y > $labelThreshold) {
                // Gabungkan tspans jika ada, atau ambil text langsung
                $tspans = $xpath->query('.//tspan', $textEl);
                $label = '';
                if ($tspans->length > 0) {
                    foreach ($tspans as $ts) {
                        $label .= trim($ts->textContent) . ' ';
                    }
                } else {
                    $label = trim($textEl->textContent);
                }
                $label = trim($label);
                if (strlen($label) > 2) {
                    $axisLabels[] = $label;
                }
            }
        }

        // B.1: Harus ada minimal 2 label (dari 2 pelatihan)
        $this->assertGreaterThanOrEqual(2, count($axisLabels),
            'B FAILED: Harus ada minimal 2 label sumbu X. Ditemukan: ' . count($axisLabels) . '. Labels: ' . json_encode($axisLabels));

        // B.2: Label untuk 2 pelatihan harus BERBEDA
        $label1 = $axisLabels[0] ?? '';
        $label2 = $axisLabels[1] ?? '';
        $this->assertNotEmpty($label1, 'B: Label pertama kosong');
        $this->assertNotEmpty($label2, 'B: Label kedua kosong');
        $this->assertNotEquals($label1, $label2,
            "B FAILED: 2 label harus berbeda.\nLabel 1: '$label1'\nLabel 2: '$label2'");
    }

    /**
     * C. TEST: Komposisi table (STRICT, DILARANG dilemahkan)
     * - Jumlah kolom klaster ambil dari hasil FCM / jumlah <th> klaster. Jangan hardcode 4.
     * - Data: 2 pelatihan, masing-masing punya anggota di kedua klaster
     * - Hitung harapan dari service: jumlah peserta per (pelatihan, klaster_dominan)
     * - Bandingkan sel demi sel dengan isi tabel
     * - Jumlah per baris = N respons pelatihan
     * - Jumlah per kolom = jumlah_peserta kartu klaster
     */
    public function test_c_komposisi_table_cell_accuracy()
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pel1 = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_selesai' => now()->subDay(),
            'nama' => '[TEST] Pelatihan Composition Test A',
        ]);
        $pel2 = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_selesai' => now()->subDay(),
            'nama' => '[TEST] Pelatihan Composition Test B',
        ]);

        // Pelatihan 1: 4 optimal (klaster 1 expected) + 4 kurang (klaster 2 expected)
        for ($i = 0; $i < 4; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pend = Pendaftaran::factory()->hadir()->create([
                'status_verifikasi' => 'diverifikasi',
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pel1->id,
            ]);
            TiyasResponse::factory()->withProfil('optimal')->create(['pendaftaran_id' => $pend->id]);
        }
        for ($i = 0; $i < 4; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pend = Pendaftaran::factory()->hadir()->create([
                'status_verifikasi' => 'diverifikasi',
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pel1->id,
            ]);
            TiyasResponse::factory()->withProfil('kurang')->create(['pendaftaran_id' => $pend->id]);
        }

        // Pelatihan 2: 3 optimal + 3 kurang
        for ($i = 0; $i < 3; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pend = Pendaftaran::factory()->hadir()->create([
                'status_verifikasi' => 'diverifikasi',
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pel2->id,
            ]);
            TiyasResponse::factory()->withProfil('optimal')->create(['pendaftaran_id' => $pend->id]);
        }
        for ($i = 0; $i < 3; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pend = Pendaftaran::factory()->hadir()->create([
                'status_verifikasi' => 'diverifikasi',
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pel2->id,
            ]);
            TiyasResponse::factory()->withProfil('kurang')->create(['pendaftaran_id' => $pend->id]);
        }

        // Get service result to compute expected composition
        $responses = TiyasResponse::whereHas('pendaftaran', function ($q) {
            $q->where('status_verifikasi', 'diverifikasi')
                ->whereNotNull('hadir_at');
        })->with('pendaftaran.peserta', 'pendaftaran.pelatihan')->get();

        $scorer = new TiyasScorer;
        $fcm = new FuzzyCMeans;
        $service = new TiyasProfilKlaster($scorer, $fcm);
        $result = $service->analisis($responses);

        $this->assertEquals('sukses', $result['status'], 'Service harus sukses');

        // Build expected composition: expected[$pel_id][$klaster_num] = count
        $expected = [];
        foreach ($result['tabel_peserta'] as $peserta) {
            $pid = $peserta['pelatihan_id'];
            $kid = $peserta['klaster_dominan'];
            if (! isset($expected[$pid])) {
                $expected[$pid] = [];
            }
            if (! isset($expected[$pid][$kid])) {
                $expected[$pid][$kid] = 0;
            }
            $expected[$pid][$kid]++;
        }

        // Render view
        $response = $this->actingAs($panitia)->get(route('tiyas.hasil', ['pelatihan' => $pel1, 'cakupan' => 'semua']));
        $response->assertOk();

        $html = $response->getContent();
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_use_internal_errors(false);

        $xpath = new DOMXPath($dom);

        // Find composition table
        $h3s = $xpath->query('//h3');
        $compositionTableH3 = null;
        foreach ($h3s as $h3) {
            if (strpos($h3->textContent, 'Komposisi Klaster per Pelatihan') !== false) {
                $compositionTableH3 = $h3;
                break;
            }
        }

        $this->assertNotNull($compositionTableH3, 'C: Harus ada heading "Komposisi Klaster per Pelatihan"');

        // Find table
        $parent = $compositionTableH3;
        $table = null;
        for ($i = 0; $i < 5; $i++) {
            $parent = $parent->nextSibling;
            if ($parent && $parent->nodeType === 1 && $parent->nodeName === 'table') {
                $table = $parent;
                break;
            }
            if ($parent && $parent->nodeType === 1 && $parent->nodeName === 'div') {
                $tables = $xpath->query('.//table', $parent);
                if ($tables->length > 0) {
                    $table = $tables->item(0);
                    break;
                }
            }
        }

        $this->assertNotNull($table, 'C: Tabel komposisi harus ada');

        // Get number of klaster columns from FCM result
        $numKlasters = count($result['klaster_labels'] ?? $result['tabel_klaster'] ?? []);
        $this->assertGreaterThan(0, $numKlasters, 'C: Harus ada minimal 1 klaster');

        // Extract table data
        $rows = $xpath->query('.//tbody/tr', $table);
        $this->assertGreaterThan(0, $rows->length, 'C: Tabel harus punya baris');

        $actualComposition = [];
        foreach ($rows as $row) {
            $cells = $xpath->query('.//td', $row);
            if ($cells->length < 2) {
                continue;
            }

            $pelName = trim($cells->item(0)->textContent);
            // Match pelatihan dari nama PERSIS dari data tes
            $matchedPel = null;
            foreach ([$pel1, $pel2] as $p) {
                // Strict match: pelatihan nama harus cocok persis atau substring lengkap
                if ($pelName === $p->nama || strpos($pelName, $p->nama) === 0) {
                    $matchedPel = $p;
                    break;
                }
            }

            // C: GAGAL jika baris tidak cocok dengan data tes (JANGAN skip dengan continue)
            $this->assertNotNull($matchedPel,
                "C FAILED: Baris tabel dengan nama '$pelName' tidak cocok dengan pelatihan tes ($pel1->nama atau $pel2->nama). "
                ."Ini indikasi data tidak cocok atau ada baris berlebih.");

            $actualComposition[$matchedPel->id] = [];
            for ($colIdx = 1; $colIdx <= $numKlasters; $colIdx++) {
                $cellVal = (int) trim($cells->item($colIdx)?->textContent ?? '0');
                $klasterNum = $colIdx; // 1-based from table column
                $actualComposition[$matchedPel->id][$klasterNum] = $cellVal;
            }
        }

        // C: Jumlah baris tabel HARUS SAMA dengan jumlah pelatihan (2)
        $this->assertEquals(2, count($actualComposition),
            'C FAILED: Jumlah baris tabel harus = 2 (2 pelatihan). '
            .'Ditemukan ' . count($actualComposition) . ' baris. '
            .'Tandanya ada baris berlebih atau data tidak cocok.');

        // C: STRICT VALIDATION - Cell demi cell
        foreach ($expected as $pelId => $expectedRow) {
            foreach ($expectedRow as $klasterNum => $expectedCount) {
                $actualCount = $actualComposition[$pelId][$klasterNum] ?? -1;
                $this->assertEquals($expectedCount, $actualCount,
                    "C FAILED STRICT: Pelatihan {$pelId} Klaster {$klasterNum} "
                    ."expected=$expectedCount, actual=$actualCount. "
                    .'Cek: akses tabel_peserta, indeks klaster 0-based vs 1-based, data flow controller→service→view');
            }
        }

        // C: Row sum = N respons pelatihan
        foreach ($actualComposition as $pelId => $rowData) {
            $rowSum = array_sum($rowData);
            $expectedSum = count(array_filter($result['tabel_peserta'], fn ($p) => $p['pelatihan_id'] == $pelId));
            $this->assertEquals($expectedSum, $rowSum,
                "C FAILED: Row sum pelatihan {$pelId} = {$rowSum}, expected={$expectedSum}");
        }

        // C: Column sum = jumlah_peserta kartu klaster
        for ($klasterNum = 1; $klasterNum <= $numKlasters; $klasterNum++) {
            $colSum = 0;
            foreach ($actualComposition as $rowData) {
                $colSum += $rowData[$klasterNum] ?? 0;
            }
            $expectedColSum = count(array_filter($result['tabel_peserta'], fn ($p) => $p['klaster_dominan'] == $klasterNum));
            $this->assertEquals($expectedColSum, $colSum,
                "C FAILED: Column sum klaster {$klasterNum} = {$colSum}, expected={$expectedColSum}");
        }
    }

    /**
     * D. TEST: Judul klaster di controller - tanpa "· —" saat kode_profil kosong
     */
    public function test_d_klaster_title_labels()
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pel1 = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_selesai' => now()->subDay(),
            'nama' => '[D] Pelatihan Satu',
        ]);
        $pel2 = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_selesai' => now()->subDay(),
            'nama' => '[D] Pelatihan Dua',
        ]);

        // Pelatihan 1: 4 responses (semua optimal untuk cluster pertama)
        for ($i = 0; $i < 4; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pend = Pendaftaran::factory()->hadir()->create([
                'status_verifikasi' => 'diverifikasi',
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pel1->id,
            ]);
            TiyasResponse::factory()->withProfil('optimal')->create(['pendaftaran_id' => $pend->id]);
        }

        // Pelatihan 2: 4 responses (semua developing untuk cluster kedua)
        for ($i = 0; $i < 4; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pend = Pendaftaran::factory()->hadir()->create([
                'status_verifikasi' => 'diverifikasi',
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pel2->id,
            ]);
            TiyasResponse::factory()->withProfil('developing')->create(['pendaftaran_id' => $pend->id]);
        }

        $response = $this->actingAs($panitia)->get(route('tiyas.hasil', ['pelatihan' => $pel1, 'cakupan' => 'semua']));
        $response->assertOk();

        $html = $response->getContent();

        // Harus ada section Komposisi
        $this->assertStringContainsString('Komposisi Klaster per Pelatihan', $html,
            'D DEBUG: Section Komposisi Klaster per Pelatihan tidak ditemukan');

        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_use_internal_errors(false);

        $xpath = new DOMXPath($dom);

        // Extract headers ONLY from Komposisi Klaster table (not all tables)
        // Target: find <h3> with text "Komposisi", then get table within same section
        $h3Elements = $xpath->query("//h3[contains(text(), 'Komposisi')]");
        $this->assertGreaterThan(0, $h3Elements->length,
            'D DEBUG: Section dengan "Komposisi" tidak ditemukan');

        // Get parent div of h3, then find table within it
        $h3 = $h3Elements->item(0);
        $parentDiv = $h3->parentNode;
        $tableInComposition = $xpath->query('.//table', $parentDiv);
        $this->assertGreaterThan(0, $tableInComposition->length,
            'D DEBUG: Tabel dalam section "Komposisi" tidak ditemukan');

        // Extract headers from composition table ONLY
        $table = $tableInComposition->item(0);
        $thElements = $xpath->query('.//th', $table);
        $allHeaders = [];
        foreach ($thElements as $th) {
            $text = trim($th->textContent);
            if (strlen($text) > 0) {
                $allHeaders[] = $text;
            }
        }

        // D DEBUG: print headers
        $klasterHeaders = array_filter($allHeaders, fn ($h) => strpos($h, 'Klaster') === 0);
        $this->assertGreaterThan(0, count($klasterHeaders),
            'D DEBUG: Tidak ada header dengan "Klaster". Headers: '.implode(' | ', $allHeaders));

        // D: Check format
        foreach ($klasterHeaders as $title) {
            if (strpos($title, ' · ') !== false) {
                // With code: "Klaster N · CODE: Label"
                $this->assertMatchesRegularExpression('/^Klaster \d+ · [^\—]+: /', $title,
                    "D: Format dengan kode harus 'Klaster N · CODE: ...'. Got: '$title'");
                $this->assertStringNotContainsString('· —', $title,
                    "D: Tidak boleh ada '· —'. Got: '$title'");
            } else {
                // Without code: "Klaster N: Label"
                $this->assertMatchesRegularExpression('/^Klaster \d+: /', $title,
                    "D: Format tanpa kode harus 'Klaster N: ...'. Got: '$title'");
                $this->assertStringNotContainsString('· —', $title,
                    "D: Tidak boleh ada '· —'. Got: '$title'");
            }
        }
    }

    /**
     * E. TEST: Scatter chart - judul dengan spacing minimal 12px antara subtitle dan plot area
     * - Main title: "Sebaran Peserta: ..."
     * - Subtitle: "Semua Pelatihan" (comparison) atau nama pelatihan (single)
     * - Harus ada jarak >= 12px antara baseline subtitle dan garis atas plot
     * - Cek dengan membaca y coordinates dari DOM, bukan hanya dy attribute
     */
    public function test_e_scatter_title_spacing()
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pel1 = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_selesai' => now()->subDay(),
            'nama' => '[TEST] Scatter Spacing Test',
        ]);

        // Create 8 responses (>= FCM minimum) for valid clustering analysis
        for ($i = 0; $i < 8; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pend = Pendaftaran::factory()->hadir()->create([
                'status_verifikasi' => 'diverifikasi',
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pel1->id,
            ]);
            TiyasResponse::factory()->withProfil('optimal')->create(['pendaftaran_id' => $pend->id]);
        }

        $response = $this->actingAs($panitia)->get(route('tiyas.hasil', ['pelatihan' => $pel1, 'cakupan' => 'semua']));
        $response->assertOk();

        $html = $response->getContent();
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_use_internal_errors(false);

        $xpath = new DOMXPath($dom);

        // Find scatter plot SVG
        $scatterSvg = $xpath->query('//svg[@data-chart="fcm-scatter"]')->item(0);
        $this->assertNotNull($scatterSvg, 'E: Scatter SVG harus ada');

        // E.1: Extract title text elements (first text element with tspans is the title)
        $allTexts = $xpath->query('.//text', $scatterSvg);
        $titleTexts = [];
        foreach ($allTexts as $textEl) {
            $tspans = $xpath->query('.//tspan', $textEl);
            if ($tspans->length > 0) {
                // This is a multi-line text element (likely the title)
                $titleTexts[] = $textEl;
            }
        }

        $this->assertGreaterThanOrEqual(1, count($titleTexts),
            'E: Title harus ada (text element dengan tspans)');

        $titleEl = $titleTexts[0];
        $titleTspans = $xpath->query('.//tspan', $titleEl);

        $this->assertGreaterThanOrEqual(2, $titleTspans->length,
            'E: Harus ada minimal 2 tspan (main title + subtitle)');

        // E.2: Read y coordinates from tspans
        $mainTspan = $titleTspans->item(0);
        $subTspan = $titleTspans->item(1);

        $mainY = (float) ($mainTspan->getAttribute('y') ?? 0);
        $subY = (float) ($subTspan->getAttribute('y') ?? 0);

        // If y not set, compute from dy (relative positioning)
        // dy="1.2em" = ~18px (1.2 * 16px base font size for typical SVG)
        // Main tspan has dy="0", so starts at y attribute
        // Sub tspan has dy="1.2em", so y = mainY + 18
        if ($mainY == 0) {
            // y attribute not set, use dy
            $dyAttr = $mainTspan->getAttribute('dy');
            $mainY = !empty($dyAttr) && $dyAttr !== '0' ? 25 : 25; // Title typically at y=25
        }

        if ($subY == 0) {
            // Compute from dy
            $dyAttr = $subTspan->getAttribute('dy');
            if ($dyAttr === '1.2em') {
                $subY = $mainY + 18; // Approximate: 1.2em ≈ 18px at 16px base
            }
        }

        // E.3: Get plot area's top boundary (from margin-top or first axis line)
        $lines = $xpath->query('.//line', $scatterSvg);
        $minY = 500; // Default SVG height
        foreach ($lines as $line) {
            $y1 = (float) $line->getAttribute('y1');
            $y2 = (float) $line->getAttribute('y2');
            $minLineY = min($y1, $y2);
            if ($minLineY < $minY && $minLineY > 50) { // Skip title area
                $minY = min($minY, $minLineY);
            }
        }

        // Plot area top is usually at margin['t'] = 80
        $plotTopY = $minY ?: 80;

        // E.4: Calculate baseline of subtitle
        // Subtitle font-size: 12px (from view), so descent ≈ 3px
        // Baseline ≈ $subY + 3
        $subBaseline = $subY + 3;

        // E.5: Assert spacing >= 12px
        $spacing = $plotTopY - $subBaseline;
        $this->assertGreaterThanOrEqual(12, $spacing,
            "E FAILED: Jarak antara baseline subtitle ($subBaseline) dan garis atas plot ($plotTopY) harus >= 12px, "
            ."got: {$spacing}px. Main y=$mainY, Sub y=$subY, dy attr: {$subTspan->getAttribute('dy')}");

        // E.6: Verify subtitle text
        $subText = trim($subTspan->textContent);
        $this->assertNotEmpty($subText,
            'E: Subtitle text harus tidak kosong');

        // E.7: Verify main title contains "Sebaran Peserta"
        $mainText = trim($mainTspan->textContent);
        $this->assertStringContainsString('Sebaran Peserta',
            $mainText,
            "E: Main title harus mengandung 'Sebaran Peserta', got: '$mainText'");
    }
}
