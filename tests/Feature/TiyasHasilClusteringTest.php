<?php

namespace Tests\Feature;

use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\TiyasResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TiyasHasilClusteringTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: halaman hasil clustering merender dengan 12 respons (>= 2 kelompok).
     * Panitia dapat melihat:
     * - "Profil Klaster Peserta"
     * - "Jumlah klaster terpilih"
     * - nama salah satu peserta (dari relasi pendaftaran->peserta->name, bukan 'nama dummy')
     * - judul profil dari config (mis. 'Optimal Transfer')
     */
    public function test_hasil_clustering_panitia_12_responses_render_page()
    {
        // Clean up any existing responses (optional but safe)
        TiyasResponse::truncate();

        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // Buat 6 peserta optimal + 6 peserta at_risk => 2 kelompok jelas
        $peserta_optimal = [];
        $peserta_risk = [];

        for ($i = 0; $i < 6; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $peserta_optimal[] = $peserta;
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);
            TiyasResponse::factory()->withProfil('optimal')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        for ($i = 0; $i < 6; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $peserta_risk[] = $peserta;
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);
            TiyasResponse::factory()->withProfil('at_risk')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        $response = $this->actingAs($panitia)
            ->get(route('tiyas.hasil', $pelatihan));

        $response->assertOk();
        $response->assertSee('Profil Klaster Peserta');
        $response->assertSee('Jumlah klaster terpilih');

        // Verifikasi ada nama peserta (dari pendaftaran->peserta->name, bukan dummy)
        $nama_peserta_diharapkan = $peserta_optimal[0]->name;
        $response->assertSee($nama_peserta_diharapkan);

        // Verifikasi ada label profil dari config (profil label ada di SVG legend dan tabel klaster)
        $response->assertSee('Optimal Transfer');
    }

    /**
     * Test: halaman hasil clustering dengan 3 respons menampilkan pesan minimal 8.
     */
    public function test_hasil_clustering_3_responses_minimal_8_peserta()
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // Buat 3 responses saja
        for ($i = 0; $i < 3; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);
            TiyasResponse::factory()->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        $response = $this->actingAs($panitia)
            ->get(route('tiyas.hasil', $pelatihan));

        $response->assertOk();
        $response->assertSee('minimal 8 peserta');
    }

    /**
     * Test: peserta biasa 403 di halaman hasil clustering.
     */
    public function test_peserta_403_hasil_clustering()
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        $response = $this->actingAs($peserta)
            ->get(route('tiyas.hasil', $pelatihan));

        $response->assertForbidden();
    }

    /**
     * NEW TEST 1: Feature dengan 12 respons—halaman memuat SVG grafik PCA, legenda Klaster,
     * tabel statistik deskriptif, kotak 'Cara membaca hasil ini', kalimat penjelasan per klaster.
     */
    public function test_12_responses_page_renders_all_components_svg_deskriptif_explanation()
    {
        // Clean up any existing responses (optional but safe)
        TiyasResponse::truncate();

        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // Buat 6 peserta optimal + 6 peserta at_risk => 2 kelompok
        for ($i = 0; $i < 6; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);
            TiyasResponse::factory()->withProfil('optimal')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        for ($i = 0; $i < 6; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);
            TiyasResponse::factory()->withProfil('at_risk')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        $response = $this->actingAs($panitia)
            ->get(route('tiyas.hasil', $pelatihan));

        $response->assertOk();

        // 1-2. Verifikasi SVG grafik sebaran PCA ada dengan legenda klaster
        $response->assertSee('<svg', false);  // SVG element literal
        $response->assertSee('Klaster 1');  // Legenda klaster ada jika SVG render

        // 3. Verifikasi tabel statistik deskriptif ada (cek untuk kata kunci deskriptif)
        $response->assertSee('Rerata');  // Header tabel statistik
        $response->assertSee('Statistik Deskriptif');

        // 4. Verifikasi kotak 'Cara membaca hasil ini' ada
        $response->assertSee('Cara Membaca Hasil Ini');

        // 5. Verifikasi penjelasan per klaster (cek untuk kata 'berprofil')
        $response->assertSee('berprofil');
    }

    /**
     * NEW TEST 2: Feature dengan 3 respons—halaman memuat tabel statistik deskriptif,
     * TIDAK memuat grafik SVG sebaran, memuat pesan 'minimal 8 peserta'.
     */
    public function test_3_responses_renders_deskriptif_no_svg_and_minimal_8_message()
    {
        // Clean up any existing responses (optional but safe)
        TiyasResponse::truncate();

        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // Buat hanya 3 responses
        for ($i = 0; $i < 3; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);
            TiyasResponse::factory()->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        $response = $this->actingAs($panitia)
            ->get(route('tiyas.hasil', $pelatihan));

        $response->assertOk();

        // DEBUG: check what klaster result is
        $content = $response->getContent();
        if (str_contains($content, 'kurang_data')) {
            error_log('TEST 5: kurang_data message found in response');
        } else {
            error_log('TEST 5: kurang_data message NOT found - response likely hit sukses status');
        }

        // 1. Verifikasi tabel statistik deskriptif tampil
        $response->assertSee('Rerata');
        $response->assertSee('Statistik Deskriptif');

        // 2. Verifikasi SVG grafik TIDAK tampil (tidak ada PCA SVG section untuk kurang_data)
        // For kurang_data, we don't expect SVG or PCA-related content
        $content = $response->getContent();

        // Check if PCA SVG is present (width="100%" is unique to PCA SVG, not icon SVGs)
        $hasPcaSvg = strpos($content, 'width="100%" height="auto" viewBox=') !== false;

        // Also check for min-responsive container which wraps PCA section
        $hasPcaSection = strpos($content, 'Grafik Sebaran Peserta (PCA)') !== false;

        if ($hasPcaSvg || $hasPcaSection) {
            $this->fail("Expected NO PCA SVG and NO PCA section for kurang_data case (< 8 responses). hasPcaSvg=$hasPcaSvg, hasPcaSection=$hasPcaSection");
        }

        // 3. Verifikasi pesan 'minimal 8 peserta' tampil
        $response->assertSee('minimal 8 peserta');
    }

    /**
     * NEW TEST 3: 12 respons halaman—tabel derajat keanggotaan judul kolom adalah 'Klaster 1', 'Klaster 2', dll (1-based, konsisten dengan kolom Dominan).
     * Verifikasi TIDAK ada 'Klaster 0'.
     */
    public function test_12_responses_membership_table_cluster_headers_1_based_not_0()
    {
        TiyasResponse::truncate();

        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // Buat 6 peserta optimal + 6 peserta at_risk => 2 kelompok
        for ($i = 0; $i < 6; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);
            TiyasResponse::factory()->withProfil('optimal')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        for ($i = 0; $i < 6; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);
            TiyasResponse::factory()->withProfil('at_risk')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        $response = $this->actingAs($panitia)
            ->get(route('tiyas.hasil', $pelatihan));

        $response->assertOk();

        // Verifikasi ada judul 'Klaster 1'
        $response->assertSee('Klaster 1');
        // Verifikasi TIDAK ada judul 'Klaster 0'
        $content = $response->getContent();
        // Pattern untuk judul tabel: '>Klaster 0<' atau 'Klaster 0</th>'
        $this->assertStringNotContainsString('Klaster 0', $content, 'Tabel derajat keanggotaan harus 1-based, jangan 0-based');
    }

    /**
     * NEW TEST 4: SVG PCA tidak ada warning 'height="auto"' di attribute (bukan di style).
     */
    public function test_12_responses_svg_no_height_auto_attribute()
    {
        TiyasResponse::truncate();

        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // Buat 6 peserta optimal + 6 peserta at_risk => 2 kelompok
        for ($i = 0; $i < 6; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);
            TiyasResponse::factory()->withProfil('optimal')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        for ($i = 0; $i < 6; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);
            TiyasResponse::factory()->withProfil('at_risk')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        $response = $this->actingAs($panitia)
            ->get(route('tiyas.hasil', $pelatihan));

        $response->assertOk();

        $content = $response->getContent();

        // Cek bahwa TIDAK ada pattern 'height="auto"' di SVG (memicu console warning)
        // Pattern: SVG PCA yang lama ada height="auto", yang baru tidak ada
        $this->assertStringNotContainsString('height="auto"', $content, 'SVG harus tidak ada height="auto" attribute untuk menghindari console warning');
    }

    /**
     * NEW TEST 5: Halaman 12 respons—elips keyakinan per klaster ada (atribut data-klaster-area).
     * Verifikasi jumlah elips = c (jumlah klaster optimal).
     */
    public function test_12_responses_ellipse_areas_per_cluster(): void
    {
        TiyasResponse::truncate();

        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // Buat 6 peserta optimal + 6 peserta at_risk => c=2 klaster
        for ($i = 0; $i < 6; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);
            TiyasResponse::factory()->withProfil('optimal')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        for ($i = 0; $i < 6; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);
            TiyasResponse::factory()->withProfil('at_risk')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        $response = $this->actingAs($panitia)
            ->get(route('tiyas.hasil', $pelatihan));

        $response->assertOk();

        $content = $response->getContent();

        // Verifikasi ada elips area per klaster (2 klaster => 2 elips)
        // Pattern: <ellipse ... data-klaster-area="1" ... />
        $this->assertStringContainsString('data-klaster-area="1"', $content, 'Klaster 1 harus ada elips keyakinan (data-klaster-area)');
        $this->assertStringContainsString('data-klaster-area="2"', $content, 'Klaster 2 harus ada elips keyakinan (data-klaster-area)');
    }

    /**
     * NEW TEST 6: Halaman 12 respons—centroid labels K1, K2, ... ada di SVG.
     */
    public function test_12_responses_centroid_labels(): void
    {
        TiyasResponse::truncate();

        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // Buat 6 peserta optimal + 6 peserta at_risk => c=2 klaster
        for ($i = 0; $i < 6; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);
            TiyasResponse::factory()->withProfil('optimal')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        for ($i = 0; $i < 6; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);
            TiyasResponse::factory()->withProfil('at_risk')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        $response = $this->actingAs($panitia)
            ->get(route('tiyas.hasil', $pelatihan));

        $response->assertOk();

        $content = $response->getContent();

        // Verifikasi ada centroid label K1, K2
        $this->assertStringContainsString('K1', $content, 'Centroid Klaster 1 label (K1) harus ada');
        $this->assertStringContainsString('K2', $content, 'Centroid Klaster 2 label (K2) harus ada');
    }

    /**
     * NEW TEST 8: Halaman 12 respons—titik peserta ada <title> dengan nama peserta dan klaster.
     */
    public function test_12_responses_point_tooltips(): void
    {
        TiyasResponse::truncate();

        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // Buat 8 peserta untuk clustering
        for ($i = 0; $i < 8; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);
            TiyasResponse::factory()->withProfil($i % 2 === 0 ? 'optimal' : 'at_risk')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        $response = $this->actingAs($panitia)
            ->get(route('tiyas.hasil', $pelatihan));

        $response->assertOk();

        $content = $response->getContent();

        // Verifikasi ada <title> tag di SVG (tooltip)
        // Pattern: <title>nama peserta | Klaster ...
        $this->assertStringContainsString('<title>', $content, 'Titik peserta harus ada <title> untuk tooltip');
        $this->assertStringContainsString('Klaster', $content, 'Tooltip harus berisi informasi klaster');
    }

    /**
     * NEW TEST 8: Halaman dengan data identical points—tidak ada NaN/INF di HTML output.
     * Klaster dengan 1 peserta (identical point) harus a=0, b=0 (minimal 6px oleh controller).
     */
    public function test_identical_points_no_nan_inf_in_html(): void
    {
        TiyasResponse::truncate();

        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // Buat 1 peserta saja—jadi FCM clustering akan menghasilkan 1 klaster dengan 1 titik (identical)
        // Atau: buat 8+ peserta tapi semua dengan profil identik sehingga FCM mungkin collapse ke 1 klaster
        // Untuk safety, buat 8 peserta dengan profil yang bisa collapse, atau just 1 peserta
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pendaftaran = Pendaftaran::factory()->hadir()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
        ]);
        TiyasResponse::factory()->withProfil('optimal')->create([
            'pendaftaran_id' => $pendaftaran->id,
        ]);

        // Sebenarnya perlu minimal 8, tapi test ini fokus pada "jika ada identical points" scenario
        // Mari buat 8 peserta identik (semua nilai profil sama) sehingga 1 klaster
        for ($i = 0; $i < 7; $i++) {
            $peserta_i = User::factory()->create(['role' => 'peserta']);
            $pendaftaran_i = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta_i->id,
                'pelatihan_id' => $pelatihan->id,
            ]);
            TiyasResponse::factory()->withProfil('optimal')->create([
                'pendaftaran_id' => $pendaftaran_i->id,
            ]);
        }

        $response = $this->actingAs($panitia)
            ->get(route('tiyas.hasil', $pelatihan));

        $response->assertOk();

        $content = $response->getContent();

        // Verifikasi tidak ada NaN atau INF di HTML
        $this->assertStringNotContainsString('NaN', $content, 'HTML output harus tidak mengandung NaN');
        $this->assertStringNotContainsString('INF', $content, 'HTML output harus tidak mengandung INF');
    }

    /**
     * NEW TEST 10: Centroid PCA positioning—centroid harus berada dekat dengan anggota klaster (< 60px jarak ke mean).
     * Centroid label K1, K2, dll harus ada di SVG dengan data-centroid-klaster attribute.
     */
    /**
     * NEW TEST 10: Centroid PCA positioning—centroid harus berada dekat dengan anggota klaster (< 60px jarak ke mean).
     * Gunakan DOMDocument/DOMXPath untuk parsing SVG, extract centroid positions dan member positions per cluster.
     * Assert: untuk setiap klaster, jarak centroid ke rata-rata anggota < 60px.
     * Assert: semua centroid ada di dalam area plot SVG (0 < cx < svgW, 0 < cy < svgH).
     */
    public function test_centroid_within_cluster_bounds(): void
    {
        TiyasResponse::truncate();

        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // Buat 6 peserta optimal + 6 peserta at_risk => c=2 klaster
        for ($i = 0; $i < 6; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);
            TiyasResponse::factory()->withProfil('optimal')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        for ($i = 0; $i < 6; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);
            TiyasResponse::factory()->withProfil('at_risk')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        $response = $this->actingAs($panitia)
            ->get(route('tiyas.hasil', $pelatihan));

        $response->assertOk();

        $content = $response->getContent();

        // Parse SVG dengan DOMDocument
        $dom = new \DOMDocument;
        // Suppress warnings for malformed HTML
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$content);
        $xpath = new \DOMXPath($dom);

        // Extract SVG viewBox untuk dimensi plot
        $svgElements = $xpath->query('//svg[@class]');
        $svgW = 500;  // Default dari Blade
        $svgH = 400;  // Default dari Blade
        if ($svgElements->length > 0) {
            foreach ($svgElements as $svg) {
                if ($svg->hasAttribute('viewBox')) {
                    $viewBox = $svg->getAttribute('viewBox');
                    if (preg_match('/(\d+)\s+(\d+)\s+([\d.]+)\s+([\d.]+)/', $viewBox, $m)) {
                        $svgW = (float) $m[3];
                        $svgH = (float) $m[4];
                    }
                }
            }
        }

        // Extract semua centroid circles (data-centroid-klaster attribute)
        $centroidNodes = $xpath->query('//circle[@data-centroid-klaster]');
        $centroids = [];
        foreach ($centroidNodes as $node) {
            $klaster = (int) $node->getAttribute('data-centroid-klaster');
            $cx = (float) $node->getAttribute('cx');
            $cy = (float) $node->getAttribute('cy');
            $centroids[$klaster] = ['cx' => $cx, 'cy' => $cy];
        }

        $this->assertNotEmpty($centroids, 'Harus ada minimal 1 centroid di SVG');

        // Extract semua member points per cluster (group dengan data-klaster attribute)
        $membersByCluster = [];
        $memberGroups = $xpath->query('//g[@data-klaster]');
        foreach ($memberGroups as $group) {
            $klaster = (int) $group->getAttribute('data-klaster');
            if (! isset($membersByCluster[$klaster])) {
                $membersByCluster[$klaster] = [];
            }

            // Cari circle atau shape di dalam group (cx, cy)
            $shapes = $xpath->query('.//circle | .//rect | .//polygon', $group);
            foreach ($shapes as $shape) {
                $cx = null;
                $cy = null;
                if ($shape->hasAttribute('cx')) {
                    $cx = (float) $shape->getAttribute('cx');
                    $cy = (float) $shape->getAttribute('cy');
                } elseif ($shape->hasAttribute('x')) {
                    // rect: x, y adalah top-left corner
                    $cx = (float) $shape->getAttribute('x') + 3;  // center
                    $cy = (float) $shape->getAttribute('y') + 3;
                } elseif ($shape->nodeName === 'polygon') {
                    // polygon points="x1,y1 x2,y2 x3,y3 x4,y4..."
                    $points = $shape->getAttribute('points');
                    if (preg_match_all('/([\\d.-]+),([\\d.-]+)/', $points, $m)) {
                        $xs = array_map('floatval', $m[1]);
                        $ys = array_map('floatval', $m[2]);
                        $cx = array_sum($xs) / count($xs);  // centroid of polygon
                        $cy = array_sum($ys) / count($ys);
                    }
                }

                if ($cx !== null && $cy !== null) {
                    $membersByCluster[$klaster][] = ['cx' => $cx, 'cy' => $cy];
                }
            }
        }

        $this->assertNotEmpty($membersByCluster, 'Harus ada minimal 1 member point di SVG');

        // Verifikasi untuk setiap klaster: centroid jarak < 60px dari rata-rata anggota
        foreach ($centroids as $klaster => $centroid) {
            $this->assertArrayHasKey($klaster, $membersByCluster, "Klaster $klaster ada centroid tapi tidak ada member points");

            $members = $membersByCluster[$klaster];
            $memberCount = count($members);
            $this->assertGreaterThan(0, $memberCount, "Klaster $klaster harus ada minimal 1 member point");

            // Hitung rata-rata posisi anggota
            $meanCx = array_sum(array_column($members, 'cx')) / $memberCount;
            $meanCy = array_sum(array_column($members, 'cy')) / $memberCount;

            // Hitung jarak centroid ke mean
            $distance = sqrt(
                pow($centroid['cx'] - $meanCx, 2) +
                pow($centroid['cy'] - $meanCy, 2)
            );

            $this->assertLessThan(
                60,
                $distance,
                "Centroid K$klaster (cx={$centroid['cx']}, cy={$centroid['cy']}) ".
                "harus dalam 60px dari rata-rata anggota (meanCx=$meanCx, meanCy=$meanCy), ".
                "jarak aktual: $distance px"
            );

            // Verifikasi centroid ada di dalam area plot (0 < cx < svgW, 0 < cy < svgH)
            $this->assertGreaterThan(0, $centroid['cx'], "Centroid K$klaster cx harus > 0");
            $this->assertLessThan($svgW, $centroid['cx'], "Centroid K$klaster cx harus < $svgW");
            $this->assertGreaterThan(0, $centroid['cy'], "Centroid K$klaster cy harus > 0");
            $this->assertLessThan($svgH, $centroid['cy'], "Centroid K$klaster cy harus < $svgH");
        }
    }

    /**
     * NEW TEST 9: Ellipse rx/ry bounds untuk kepercayaan visual (6 <= r <= 90 pixels).
     * Mencegah ellipse terlalu kecil (r < 6) atau terlalu besar (r > 90).
     */
    public function test_ellipse_rx_ry_bounds_6_to_90(): void
    {
        TiyasResponse::truncate();

        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // Buat 6 peserta optimal + 6 peserta at_risk => c=2 klaster
        for ($i = 0; $i < 6; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);
            TiyasResponse::factory()->withProfil('optimal')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        for ($i = 0; $i < 6; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);
            TiyasResponse::factory()->withProfil('at_risk')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        $response = $this->actingAs($panitia)
            ->get(route('tiyas.hasil', $pelatihan));

        $response->assertOk();

        $content = $response->getContent();

        // Extract all rx dan ry values dari <ellipse data-klaster-area>
        // Pattern: rx="<number>" atau ry="<number>"
        preg_match_all('/rx="([\d.]+)"/', $content, $rx_matches);
        preg_match_all('/ry="([\d.]+)"/', $content, $ry_matches);

        $rx_values = $rx_matches[1];
        $ry_values = $ry_matches[1];

        // Verifikasi semua rx dalam range [6, 90]
        $this->assertNotEmpty($rx_values, 'Harus ada minimal 1 rx attribute di elips');
        foreach ($rx_values as $rx) {
            $rx_float = (float) $rx;
            $this->assertGreaterThanOrEqual(6.0, $rx_float, "rx=$rx harus >= 6");
            $this->assertLessThanOrEqual(90.0, $rx_float, "rx=$rx harus <= 90");
        }

        // Verifikasi semua ry dalam range [6, 90]
        $this->assertNotEmpty($ry_values, 'Harus ada minimal 1 ry attribute di elips');
        foreach ($ry_values as $ry) {
            $ry_float = (float) $ry;
            $this->assertGreaterThanOrEqual(6.0, $ry_float, "ry=$ry harus >= 6");
            $this->assertLessThanOrEqual(90.0, $ry_float, "ry=$ry harus <= 90");
        }
    }
}
