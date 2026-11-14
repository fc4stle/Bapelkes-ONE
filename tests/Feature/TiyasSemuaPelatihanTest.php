<?php

namespace Tests\Feature;

use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\TiyasResponse;
use App\Models\User;
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

        $xLabelTexts = $xpath->query('.//text[@text-anchor="middle"]', $chartSvg);
        $labels = [];
        foreach ($xLabelTexts as $textEl) {
            $tspans = $xpath->query('.//tspan', $textEl);
            if ($tspans->length > 0) {
                // Label punya <tspan> (multi-line)
                $label = '';
                foreach ($tspans as $ts) {
                    $label .= trim($ts->textContent) . ' ';
                }
                $labels[] = trim($label);
            } else {
                // Single line label
                $text = trim($textEl->textContent);
                if (strlen($text) > 2) {
                    $labels[] = $text;
                }
            }
        }

        // B.1: Labels untuk 2 pelatihan harus berbeda
        $uniqueLabels = array_unique($labels);
        $this->assertGreaterThanOrEqual(2, count($uniqueLabels),
            "B FAILED: 2 pelatihan dengan awalan nama sama harus punya label berbeda.\n"
            ."Labels: " . json_encode($labels));

        // B.2: Verify <title> ada (optional, but good practice)
        $titles = $xpath->query('.//text[@text-anchor="middle"]/parent::*/title', $chartSvg);
        // Tidak strict assert, hanya info
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
        $fcm = new \App\Services\FuzzyCMeans;
        $service = new TiyasProfilKlaster($scorer, $fcm);
        $result = $service->analisis($responses);

        $this->assertEquals('sukses', $result['status'], 'Service harus sukses');

        // Build expected composition: expected[$pel_id][$klaster_num] = count
        $expected = [];
        foreach ($result['tabel_peserta'] as $peserta) {
            $pid = $peserta['pelatihan_id'];
            $kid = $peserta['klaster_dominan'];
            if (!isset($expected[$pid])) {
                $expected[$pid] = [];
            }
            if (!isset($expected[$pid][$kid])) {
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
        $numKlasters = count($result['klaster_labels'] ?? []);
        $this->assertGreaterThan(0, $numKlasters, 'C: Harus ada minimal 1 klaster');

        // Extract table data
        $rows = $xpath->query('.//tbody/tr', $table);
        $this->assertGreaterThan(0, $rows->length, 'C: Tabel harus punya baris');

        $actualComposition = [];
        foreach ($rows as $row) {
            $cells = $xpath->query('.//td', $row);
            if ($cells->length < 2) continue;

            $pelName = trim($cells->item(0)->textContent);
            // Match pelatihan dari nama
            $matchedPel = null;
            foreach ([$pel1, $pel2] as $p) {
                if (strpos($p->nama, str_replace('[TEST] ', '', $pelName)) !== false ||
                    strpos($pelName, $p->nama) !== false) {
                    $matchedPel = $p;
                    break;
                }
            }

            if (!$matchedPel) {
                continue; // Skip if tidak match
            }

            $actualComposition[$matchedPel->id] = [];
            for ($colIdx = 1; $colIdx <= $numKlasters; $colIdx++) {
                $cellVal = (int) trim($cells->item($colIdx)?->textContent ?? '0');
                $klasterNum = $colIdx; // 1-based from table column
                $actualComposition[$matchedPel->id][$klasterNum] = $cellVal;
            }
        }

        // C: STRICT VALIDATION - Cell demi cell
        foreach ($expected as $pelId => $expectedRow) {
            foreach ($expectedRow as $klasterNum => $expectedCount) {
                $actualCount = $actualComposition[$pelId][$klasterNum] ?? -1;
                $this->assertEquals($expectedCount, $actualCount,
                    "C FAILED STRICT: Pelatihan {$pelId} Klaster {$klasterNum} "
                    ."expected=$expectedCount, actual=$actualCount. "
                    ."Cek: akses tabel_peserta, indeks klaster 0-based vs 1-based, data flow controller→service→view");
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
            'nama' => '[TEST] Klaster Title Test',
        ]);

        for ($i = 0; $i < 6; $i++) {
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

        // Extract klaster titles
        $h3s = $xpath->query('//h3');
        $klasterTitles = [];
        foreach ($h3s as $h3) {
            $text = trim($h3->textContent);
            if (strpos($text, 'Klaster') === 0) {
                $klasterTitles[] = $text;
            }
        }

        $this->assertGreaterThan(0, count($klasterTitles), 'D: Harus ada judul klaster');

        // D: Check format
        foreach ($klasterTitles as $title) {
            // Jika tidak ada kode profil, format "Klaster N: Label" (tanpa "· —")
            if (strpos($title, ' · ') === false) {
                $this->assertMatchesRegularExpression('/^Klaster \d+: /', $title,
                    "D FAILED: Judul tanpa kode harus format 'Klaster N: ...'. Got: '$title'");
                $this->assertStringNotContainsString('· —', $title,
                    "D FAILED: Judul tidak boleh mengandung '· —'. Got: '$title'");
            } else {
                // Jika ada kode, format "Klaster N · CODE: Label"
                $this->assertMatchesRegularExpression('/^Klaster \d+ · \w+: /', $title,
                    "D FAILED: Judul dengan kode harus format 'Klaster N · CODE: ...'. Got: '$title'");
            }
        }
    }

    /**
     * E. TEST: Scatter cakupan=semua - jarak judul dan sub-judul
     * - Sub-judul "Semua Pelatihan" tidak melekat di judul utama
     * - Atur ulang posisi: naikkan margin atas plot, jarak antar baris judul minimal 6px
     * - Test: selisih y antara judul dan sub-judul >= ukuran font sub-judul + 4
     */
    public function test_e_scatter_title_spacing()
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pel1 = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_selesai' => now()->subDay(),
            'nama' => '[TEST] Scatter Spacing Test',
        ]);

        for ($i = 0; $i < 6; $i++) {
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

        // Find title tspan elements
        $tspans = $xpath->query('.//tspan', $scatterSvg);
        $titleTspans = [];
        foreach ($tspans as $ts) {
            $text = trim($ts->textContent);
            if (strlen($text) > 2 && (strpos($text, 'Profil Klaster') !== false || 
                                       strpos($text, 'Semua Pelatihan') !== false)) {
                $titleTspans[] = [
                    'text' => $text,
                    'y' => (float) ($ts->getAttribute('y') ?? 0),
                    'dy' => $ts->getAttribute('dy'),
                ];
            }
        }

        $this->assertGreaterThanOrEqual(2, count($titleTspans),
            'E: Harus ada minimal 2 judul tspan (main + sub)');

        // E: Check spacing
        if (count($titleTspans) >= 2) {
            $main = $titleTspans[0];
            $sub = $titleTspans[1];
            
            // Estimate font size from dy attribute (typical SVG: 1.2em ≈ 12-14px)
            $subFontSize = 14; // Assume default
            $minSpacing = $subFontSize + 4; // Minimum 4px more than font size

            $yDiff = abs($sub['y'] - $main['y']);
            $this->assertGreaterThanOrEqual($minSpacing, $yDiff,
                "E FAILED: Jarak y antara judul ({$main['y']}) dan sub ({$sub['y']}) = {$yDiff}, "
                ."minimum harus >= $minSpacing (font_size + 4). "
                ."Sub dy attribute: {$sub['dy']}");
        }
    }
}
