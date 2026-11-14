<?php

namespace Tests\Feature;

use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\TiyasResponse;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TiyasHasilTahap1ValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1.1 Unit: Jumlah peserta per klaster dengan 12 respons
     * Semua klaster dijumlahkan = 12 (total peserta)
     * Tiap klaster = jumlah baris tabel_peserta dengan klaster_dominan sesuai
     */
    public function test_peserta_count_per_cluster_sum_equals_total_12_responses()
    {
        TiyasResponse::truncate();

        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // Buat 12 peserta dengan profil yang berbeda (untuk mendapat klaster berbeda)
        // 3 optimal, 3 at_risk, 3 sedentary, 3 committed
        $peserta_array = [];
        for ($i = 0; $i < 3; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $peserta_array[] = $peserta;
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);
            TiyasResponse::factory()->withProfil('optimal')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        for ($i = 0; $i < 3; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $peserta_array[] = $peserta;
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);
            TiyasResponse::factory()->withProfil('at_risk')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        for ($i = 0; $i < 3; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $peserta_array[] = $peserta;
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);
            TiyasResponse::factory()->withProfil('sedentary')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        for ($i = 0; $i < 3; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $peserta_array[] = $peserta;
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);
            TiyasResponse::factory()->withProfil('committed')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        // Akses halaman hasil
        $response = $this->actingAs($panitia)->get(route('tiyas.hasil', $pelatihan->id));
        $response->assertStatus(200);

        // Parse HTML
        $html = $response->getContent();
        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        // Cari semua card klaster (section 4 "Profil Klaster")
        // Cari p atau strong yang berisi teks "Jumlah Peserta:"
        $jumlahPesertaNodes = $xpath->query("//p[contains(., 'Jumlah Peserta:')]");
        if ($jumlahPesertaNodes->length === 0) {
            $jumlahPesertaNodes = $xpath->query("//strong[contains(., 'Jumlah Peserta:')]/..");
        }
        $this->assertGreaterThan(0, $jumlahPesertaNodes->length, 'Tidak menemukan "Jumlah Peserta:" di halaman');

        $totalCountFromCards = 0;
        foreach ($jumlahPesertaNodes as $node) {
            // Node berisi "Jumlah Peserta: {jumlah}"
            // Ambil teks lengkap dan parse
            $text = $node->textContent;
            preg_match('/Jumlah Peserta:\s*(\d+)/', $text, $matches);
            if (isset($matches[1])) {
                $totalCountFromCards += (int) $matches[1];
            }
        }

        // Total harus 12
        $this->assertEquals(12, $totalCountFromCards, "Total jumlah peserta dari semua kartu klaster harus 12, dapat: $totalCountFromCards");
    }

    /**
     * 1.2 Feature: Peserta tanpa membership > 0.5 di klaster mana pun
     * Total peserta tetap konsisten
     */
    public function test_peserta_count_consistent_with_no_dominant_membership()
    {
        TiyasResponse::truncate();

        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // Buat 8 peserta dengan response "netral" (kemungkinan tidak ada membership > 0.5)
        for ($i = 0; $i < 8; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);
            // Buat response dengan nilai tengah (nilai 3 di skala 1-5)
            TiyasResponse::factory()->create([
                'pendaftaran_id' => $pendaftaran->id,
                // Factory akan generate skor netral
            ]);
        }

        $response = $this->actingAs($panitia)->get(route('tiyas.hasil', $pelatihan->id));
        $response->assertStatus(200);

        // Parse HTML
        $html = $response->getContent();
        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        // Cari semua jumlah peserta dari kartu klaster
        $jumlahPesertaNodes = $xpath->query("//p[contains(., 'Jumlah Peserta:')]");
        if ($jumlahPesertaNodes->length === 0) {
            $jumlahPesertaNodes = $xpath->query("//strong[contains(., 'Jumlah Peserta:')]/..");
        }
        $totalCountFromCards = 0;
        foreach ($jumlahPesertaNodes as $node) {
            $text = $node->textContent;
            preg_match('/Jumlah Peserta:\s*(\d+)/', $text, $matches);
            if (isset($matches[1])) {
                $totalCountFromCards += (int) $matches[1];
            }
        }

        // Total harus sama dengan jumlah peserta (8)
        $this->assertEquals(8, $totalCountFromCards, "Total jumlah peserta harus 8, dapat: $totalCountFromCards");
    }

    /**
     * 2.1 Feature scatter: Render default (X=TC, Y=YB) memunculkan scatter
     */
    public function test_scatter_plot_renders_default_tc_yb()
    {
        TiyasResponse::truncate();

        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // Buat 10 peserta dengan profil berbeda
        for ($i = 0; $i < 5; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);
            TiyasResponse::factory()->withProfil('optimal')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        for ($i = 0; $i < 5; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);
            TiyasResponse::factory()->withProfil('at_risk')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        $response = $this->actingAs($panitia)->get(route('tiyas.hasil', $pelatihan->id));
        $response->assertStatus(200);

        // Cari data-chart="fcm-scatter"
        $this->assertStringContainsString('data-chart="fcm-scatter"', $response->getContent());

        // Cari label sumbu default (TC dan YB dari config)
        $this->assertStringContainsString('Iklim Transfer', $response->getContent()); // TC judul
        $this->assertStringContainsString('Perilaku Pascapelatihan', $response->getContent()); // YB judul
    }

    /**
     * 2.2 Feature scatter: ?x=YB&y=TC memunculkan scatter dan opsi terpilih sesuai
     */
    public function test_scatter_plot_axis_parameter_yb_tc()
    {
        TiyasResponse::truncate();

        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // Buat 10 peserta
        for ($i = 0; $i < 10; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);
            TiyasResponse::factory()->withProfil($i < 5 ? 'optimal' : 'at_risk')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        $response = $this->actingAs($panitia)->get(route('tiyas.hasil', $pelatihan->id).'?x=YB&y=TC');
        $response->assertStatus(200);

        // Scatter harus render
        $this->assertStringContainsString('data-chart="fcm-scatter"', $response->getContent());

        // Parse HTML untuk cek selected option
        $html = $response->getContent();
        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        // Cari select dengan name="x" yang punya option value="YB" selected
        $xSelectOptions = $xpath->query("//select[@name='x']/option[@selected]");
        $this->assertGreaterThan(0, $xSelectOptions->length, 'Tidak menemukan option yang selected untuk sumbu X');
        $this->assertEquals('YB', $xSelectOptions->item(0)->getAttribute('value'), 'Sumbu X harus YB');

        // Cari select dengan name="y" yang punya option value="TC" selected
        $ySelectOptions = $xpath->query("//select[@name='y']/option[@selected]");
        $this->assertGreaterThan(0, $ySelectOptions->length, 'Tidak menemukan option yang selected untuk sumbu Y');
        $this->assertEquals('TC', $ySelectOptions->item(0)->getAttribute('value'), 'Sumbu Y harus TC');
    }

    /**
     * 2.3 Feature scatter: Param tidak valid (?x=ABC&y=YB) kembali ke TC dan YB, status 200
     */
    public function test_scatter_plot_invalid_param_fallback_to_default()
    {
        TiyasResponse::truncate();

        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // Buat 10 peserta
        for ($i = 0; $i < 10; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);
            TiyasResponse::factory()->withProfil($i < 5 ? 'optimal' : 'at_risk')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        $response = $this->actingAs($panitia)->get(route('tiyas.hasil', $pelatihan->id).'?x=ABC&y=YB');
        $response->assertStatus(200);

        // Scatter harus render dengan default TC (dari fallback)
        $this->assertStringContainsString('data-chart="fcm-scatter"', $response->getContent());

        // Parse HTML untuk cek bahwa default TC dipilih
        $html = $response->getContent();
        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        $xSelectOptions = $xpath->query("//select[@name='x']/option[@selected]");
        $this->assertGreaterThan(0, $xSelectOptions->length);
        // Harus fallback ke TC (default x)
        $this->assertEquals('TC', $xSelectOptions->item(0)->getAttribute('value'), 'Invalid param x=ABC harus fallback ke TC');
    }

    /**
     * 2.4 Feature scatter: x=y (?x=TC&y=TC) kembali ke default TC dan YB, status 200
     */
    public function test_scatter_plot_x_equals_y_fallback_to_default()
    {
        TiyasResponse::truncate();

        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // Buat 10 peserta
        for ($i = 0; $i < 10; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);
            TiyasResponse::factory()->withProfil($i < 5 ? 'optimal' : 'at_risk')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        $response = $this->actingAs($panitia)->get(route('tiyas.hasil', $pelatihan->id).'?x=TC&y=TC');
        $response->assertStatus(200);

        // Scatter harus render
        $this->assertStringContainsString('data-chart="fcm-scatter"', $response->getContent());

        // Parse HTML untuk cek bahwa default TC dan YB dipilih
        $html = $response->getContent();
        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        $xSelectOptions = $xpath->query("//select[@name='x']/option[@selected]");
        $this->assertGreaterThan(0, $xSelectOptions->length);
        $this->assertEquals('TC', $xSelectOptions->item(0)->getAttribute('value'), 'x=y fallback harus TC');

        $ySelectOptions = $xpath->query("//select[@name='y']/option[@selected]");
        $this->assertGreaterThan(0, $ySelectOptions->length);
        $this->assertEquals('YB', $ySelectOptions->item(0)->getAttribute('value'), 'x=y fallback harus YB');
    }

    /**
     * 2.5 Feature scatter: Ada tanda pusat klaster berlabel K1 sampai Kn
     * dan posisinya di dalam area plot (1-4 range)
     */
    public function test_scatter_plot_centroid_marker_with_labels_in_range()
    {
        TiyasResponse::truncate();

        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // Buat 10 peserta
        for ($i = 0; $i < 10; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);
            TiyasResponse::factory()->withProfil($i < 5 ? 'optimal' : 'at_risk')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        $response = $this->actingAs($panitia)->get(route('tiyas.hasil', $pelatihan->id));
        $response->assertStatus(200);

        $html = $response->getContent();
        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        // Cari SVG text elements dengan label K1, K2, K3, K4 (centroid labels)
        // Centroid teks dalam format <text>K1</text> di SVG namespace
        $centroidLabels = $xpath->query("//svg/text[contains(., 'K')]");
        $foundCentroidLabels = [];
        foreach ($centroidLabels as $node) {
            $text = trim($node->textContent);
            if (preg_match('/^K\d+$/', $text)) {
                $foundCentroidLabels[] = $text;
            }
        }

        $this->assertGreaterThan(0, count($foundCentroidLabels), 'Tidak menemukan centroid labels K1, K2, dst. dalam SVG');

        // Verifikasi centroid ada dalam plot area (coordinate validasi di SVG attributes)
        // SVG viewBox="0 0 700 500", plot area: x 70-590, y 40-450 (margin saja)
        // Scale 1-4 pada axis: x=(1-4)→(70,590), y=(4-1)→(450,40)
        // Tolerance: 40-460 untuk coordinate check
        $lines = $xpath->query('//svg/line[count(@x1|@x2|@y1|@y2) > 0]');
        // Cek apakah ada line elements (centroid crosshair)
        $centroidLines = 0;
        foreach ($lines as $line) {
            $x1 = (float) $line->getAttribute('x1');
            $x2 = (float) $line->getAttribute('x2');
            $y1 = (float) $line->getAttribute('y1');
            $y2 = (float) $line->getAttribute('y2');

            // Centroid crosshair di plot area: x dalam 50-610, y dalam 30-460
            if (($x1 >= 40 && $x1 <= 650) || ($x2 >= 40 && $x2 <= 650)) {
                if (($y1 >= 30 && $y1 <= 470) || ($y2 >= 30 && $y2 <= 470)) {
                    $centroidLines++;
                }
            }
        }
        $this->assertGreaterThan(0, $centroidLines, 'Tidak menemukan centroid line elements (crosshair)');
    }

    /**
     * 2.6 Feature scatter: Jumlah titik peserta di scatter = jumlah peserta
     */
    public function test_scatter_plot_point_count_equals_peserta_count()
    {
        TiyasResponse::truncate();

        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // Buat 12 peserta
        $pesertaCount = 12;
        for ($i = 0; $i < $pesertaCount; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);
            TiyasResponse::factory()->withProfil($i % 2 === 0 ? 'optimal' : 'at_risk')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        $response = $this->actingAs($panitia)->get(route('tiyas.hasil', $pelatihan->id));
        $response->assertStatus(200);

        $html = $response->getContent();
        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        // Cari SVG circle elements (titik peserta) dalam SVG fcm-scatter
        // Circle elements dalam SVG: <circle cx="..." cy="..." r="..." />
        $circles = $xpath->query("//svg[@data-chart='fcm-scatter']//circle[not(ancestor::defs)]");
        $pointCount = $circles->length;

        // Jumlah titik harus sama dengan jumlah peserta (12)
        $this->assertEquals($pesertaCount, $pointCount, "Jumlah titik di scatter ($pointCount) harus sama dengan peserta ($pesertaCount)");
    }

    /**
     * 2.7 Feature scatter: HTML tidak mengandung NaN atau INF
     */
    public function test_scatter_plot_no_nan_or_inf_in_html()
    {
        TiyasResponse::truncate();

        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // Buat 10 peserta
        for ($i = 0; $i < 10; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);
            TiyasResponse::factory()->withProfil($i < 5 ? 'optimal' : 'at_risk')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        $response = $this->actingAs($panitia)->get(route('tiyas.hasil', $pelatihan->id));
        $response->assertStatus(200);

        $html = $response->getContent();

        // Cari NaN atau INF dalam HTML (case-insensitive)
        $this->assertStringNotContainsString('NaN', $html, 'HTML tidak boleh mengandung NaN');
        $this->assertStringNotContainsString('INF', $html, 'HTML tidak boleh mengandung INF');
        $this->assertStringNotContainsString('Infinity', $html, 'HTML tidak boleh mengandung Infinity');
    }

    /**
     * 2.8 Feature scatter: Kasus <8 respons scatter tidak tampil, halaman tidak error
     */
    public function test_scatter_plot_hidden_with_less_than_8_responses()
    {
        TiyasResponse::truncate();

        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // Buat hanya 5 peserta (< 8)
        for ($i = 0; $i < 5; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);
            TiyasResponse::factory()->withProfil($i % 2 === 0 ? 'optimal' : 'at_risk')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        $response = $this->actingAs($panitia)->get(route('tiyas.hasil', $pelatihan->id));
        $response->assertStatus(200);

        $html = $response->getContent();

        // Scatter tidak boleh tampil
        $this->assertStringNotContainsString('data-chart="fcm-scatter"', $html, 'Scatter harus hidden untuk <8 responses');

        // Halaman masih bisa render bagian lain (misalnya Statistik Deskriptif)
        $this->assertStringContainsString('Statistik Deskriptif', $html);
    }

    /**
     * 3.1 Feature tabel keanggotaan: Teks "Klaster N · {kode}: {label}" muncul
     */
    public function test_membership_table_cluster_label_format()
    {
        TiyasResponse::truncate();

        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // Buat 10 peserta
        for ($i = 0; $i < 10; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);
            TiyasResponse::factory()->withProfil($i < 5 ? 'optimal' : 'at_risk')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        $response = $this->actingAs($panitia)->get(route('tiyas.hasil', $pelatihan->id));
        $response->assertStatus(200);

        $html = $response->getContent();

        // Cari format "Klaster N · {kode}: {label}"
        // Minimal harus ada pattern Klaster, ·, dan : untuk menunjukkan format yang benar
        preg_match_all('/Klaster\s+\d+\s+·\s+[A-D]:\s+/', $html, $matches);
        $this->assertGreaterThan(0, count($matches[0]), 'Harus ada minimal satu "Klaster N · kode: label" pattern');

        // Juga cek tidak ada duplikat kode (mis: "A: A:")
        $this->assertStringNotContainsString('A: A:', $html);
        $this->assertStringNotContainsString('B: B:', $html);
        $this->assertStringNotContainsString('C: C:', $html);
        $this->assertStringNotContainsString('D: D:', $html);
    }

    /**
     * 3.2 Feature kartu klaster: Tidak ada duplikat "Profil:" row
     */
    public function test_cluster_card_no_duplicate_profil_row()
    {
        TiyasResponse::truncate();

        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // Buat 10 peserta
        for ($i = 0; $i < 10; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);
            TiyasResponse::factory()->withProfil($i < 5 ? 'optimal' : 'at_risk')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        $response = $this->actingAs($panitia)->get(route('tiyas.hasil', $pelatihan->id));
        $response->assertStatus(200);

        $html = $response->getContent();

        // Cari section 4 "Profil Klaster" dan hitung jumlah "Profil:" dalam bagian itu
        // Split HTML by section marker jika mungkin, atau gunakan regex
        // Profil Klaster section biasanya antara h2 "4. Profil Klaster" dan h2 berikutnya

        // Simple approach: count "Profil:" - harus muncul 1x per klaster (bukan 2x atau lebih per klaster)
        $profileRowPattern = '/Profil:\s*<\/\w+>\s*<\w+[^>]*>/';
        $profileMatches = [];
        preg_match_all($profileRowPattern, $html, $profileMatches);

        // Harus ada beberapa (1-4 per klaster), tapi tidak berlebihan
        // Asumsi ada 2-4 klaster, jadi harus ada 2-4 "Profil:" rows
        $this->assertLessThanOrEqual(5, count($profileMatches[0]), 'Tidak boleh terlalu banyak duplikat "Profil:" rows');

        // Double-check: cari pattern duplikat dalam section 4
        // Misal: section berisi "Profil:" lebih dari sekali tanpa pemisah klaster
        $section4Pattern = '/4\.\s*Profil Klaster.*?(?=\d\.\s*|$)/s';
        preg_match($section4Pattern, $html, $section4Matches);
        if (! empty($section4Matches)) {
            $section4Html = $section4Matches[0];
            // Dalam section 4, tidak boleh ada "Profil: Profil:" (duplikat dalam 1 klaster)
            $this->assertStringNotContainsString('Profil: Profil:', $section4Html, 'Tidak boleh ada duplikat "Profil:" dalam satu klaster');
        }
    }

    /**
     * 4.1 Feature nama konstruk: Tabel deskriptif harus dari config dan sesuai
     */
    public function test_deskriptif_konstruk_nama_from_config()
    {
        TiyasResponse::truncate();

        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // Buat 1 peserta
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pendaftaran = Pendaftaran::factory()->hadir()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
        ]);
        TiyasResponse::factory()->withProfil('optimal')->create([
            'pendaftaran_id' => $pendaftaran->id,
        ]);

        $response = $this->actingAs($panitia)->get(route('tiyas.hasil', $pelatihan->id));
        $response->assertStatus(200);

        $html = $response->getContent();

        // Cek bahwa nama konstruk dari config['tiyas'] ditampilkan
        // config['tiyas']['konstruk']['TC']['judul'] = 'Iklim Transfer'
        // config['tiyas']['konstruk']['TE']['judul'] = 'Lingkungan Transfer'
        // config['tiyas']['konstruk']['ITT']['judul'] = 'Niat Transfer'
        // config['tiyas']['konstruk']['YB']['judul'] = 'Perilaku Pascapelatihan'

        $this->assertStringContainsString('Iklim Transfer', $html, 'Harus menampilkan nama TC dari config');
        $this->assertStringContainsString('Lingkungan Transfer', $html, 'Harus menampilkan nama TE dari config');
        $this->assertStringContainsString('Niat Transfer', $html, 'Harus menampilkan nama ITT dari config');
        $this->assertStringContainsString('Perilaku Pascapelatihan', $html, 'Harus menampilkan nama YB dari config');
    }

    /**
     * 4.2 Feature: Tidak ada hardcode text "Teamwork", "Technical Excellence", "Innovation", "Yayasan"
     */
    public function test_no_hardcoded_construct_names_in_view()
    {
        TiyasResponse::truncate();

        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // Buat 1 peserta
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pendaftaran = Pendaftaran::factory()->hadir()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
        ]);
        TiyasResponse::factory()->withProfil('optimal')->create([
            'pendaftaran_id' => $pendaftaran->id,
        ]);

        $response = $this->actingAs($panitia)->get(route('tiyas.hasil', $pelatihan->id));
        $response->assertStatus(200);

        $html = $response->getContent();

        // Hardcoded construct names yang tidak boleh ada
        $this->assertStringNotContainsString('Teamwork', $html, 'Tidak boleh ada hardcoded "Teamwork"');
        $this->assertStringNotContainsString('Technical Excellence', $html, 'Tidak boleh ada hardcoded "Technical Excellence"');
        $this->assertStringNotContainsString('Innovation', $html, 'Tidak boleh ada hardcoded "Innovation"');
        $this->assertStringNotContainsString('Yayasan', $html, 'Tidak boleh ada hardcoded "Yayasan"');
    }
}
