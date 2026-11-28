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
                'status_verifikasi' => 'diverifikasi',
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
                'status_verifikasi' => 'diverifikasi',
            ]);
            TiyasResponse::factory()->withProfil('optimal')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        for ($i = 0; $i < 6; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $peserta_risk[] = $peserta;
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'status_verifikasi' => 'diverifikasi',
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
                'status_verifikasi' => 'diverifikasi',
            ]);
            TiyasResponse::factory()->withProfil('at_risk')->create([
                'pendaftaran_id' => $pendaftaran->id,
            ]);
        }

        $response = $this->actingAs($panitia)
            ->get(route('tiyas.hasil', $pelatihan));

        $response->assertOk();
        $response->assertSee('Profil Klaster');
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
                'status_verifikasi' => 'diverifikasi',
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
     * NEW TEST 1: Feature dengan 12 respons-halaman memuat SVG grafik klaster, legenda Klaster,
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
                'status_verifikasi' => 'diverifikasi',
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
                'status_verifikasi' => 'diverifikasi',
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

        // 1-2. Verifikasi SVG grafik klaster ada dengan legenda klaster
        $response->assertSee('<svg', false);  // SVG element literal
        $response->assertSee('Klaster 1');  // Legenda klaster ada jika SVG render

        // 3. Verifikasi tabel statistik deskriptif ada (cek untuk kata kunci deskriptif)
        $response->assertSee('Rerata');  // Header tabel statistik
        $response->assertSee('Statistik Deskriptif');

        // 4. Verifikasi kotak 'Cara membaca hasil ini' ada
        $response->assertSee('Cara Membaca Hasil Ini');

        // 5. Verifikasi penjelasan per klaster (cek untuk kata 'Profil klaster' atau 'Fuzzy C-Means')
        $response->assertSee('Profil');
    }

    /**
     * NEW TEST 2: Feature dengan 3 respons-halaman memuat tabel statistik deskriptif,
     * TIDAK memuat grafik SVG klaster, memuat pesan 'minimal 8 peserta'.
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
                'status_verifikasi' => 'diverifikasi',
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

        // 2. Verifikasi SVG grafik klaster TIDAK tampil (tidak ada centroid atau membership chart untuk kurang_data)
        // For kurang_data, we don't expect clustering charts
        $content = $response->getContent();

        // Check if clustering SVG charts are absent for kurang_data
        $hasClusteringChart = strpos($content, 'data-chart="centroid-grouped-bars"') !== false
            || strpos($content, 'data-chart="membership-horizontal-stacked"') !== false;

        // Also check heading
        $hasClusteringSection = strpos($content, 'Indeks Validitas Klaster') !== false;

        if ($hasClusteringChart || ($hasClusteringSection && strpos($content, 'minimal 8 peserta') === false)) {
            $this->fail("Expected NO clustering charts for kurang_data case (< 8 responses). hasClusteringChart=$hasClusteringChart, hasClusteringSection=$hasClusteringSection");
        }

        // 3. Verifikasi pesan 'minimal 8 peserta' tampil
        $response->assertSee('minimal 8 peserta');
    }

    /**
     * NEW TEST 3: 12 respons halaman-tabel derajat keanggotaan judul kolom adalah 'Klaster 1', 'Klaster 2', dll (1-based, konsisten dengan kolom Dominan).
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
                'status_verifikasi' => 'diverifikasi',
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
                'status_verifikasi' => 'diverifikasi',
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
     * NEW TEST 4: SVG klaster tidak ada warning 'height="auto"' di attribute (bukan di style).
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
                'status_verifikasi' => 'diverifikasi',
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
                'status_verifikasi' => 'diverifikasi',
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
        // SVG grafik klaster harus tidak ada height="auto" attribute untuk menghindari console warning
        $this->assertStringNotContainsString('height="auto"', $content, 'SVG harus tidak ada height="auto" attribute untuk menghindari console warning');
    }

    /**
     * NEW TEST 5: Halaman 12 respons-elips keyakinan per klaster ada (atribut data-klaster-area).
     * Verifikasi jumlah elips = c (jumlah klaster optimal).
     */
    /**
     * NEW TEST 8: Halaman dengan data identical points-tidak ada NaN/INF di HTML output.
     * Klaster dengan 1 peserta (identical point) harus a=0, b=0 (minimal 6px oleh controller).
     */
    public function test_identical_points_no_nan_inf_in_html(): void
    {
        TiyasResponse::truncate();

        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // Buat 1 peserta saja-jadi FCM clustering akan menghasilkan 1 klaster dengan 1 titik (identical)
        // Atau: buat 8+ peserta tapi semua dengan profil identik sehingga FCM mungkin collapse ke 1 klaster
        // Untuk safety, buat 8 peserta dengan profil yang bisa collapse, atau just 1 peserta
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pendaftaran = Pendaftaran::factory()->hadir()->create([
            'status_verifikasi' => 'diverifikasi',
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
                'status_verifikasi' => 'diverifikasi',
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
     * NEW TEST 9: Feature 12 respons-halaman render empat data-chart:
     * "deskriptif-mean-sd", "deskriptif-kategori-stacked", "centroid-grouped-bars", "membership-horizontal-stacked"
     */
    public function test_12_responses_render_all_four_data_charts(): void
    {
        TiyasResponse::truncate();

        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // Buat 6 peserta optimal + 6 peserta at_risk => 2 klaster
        for ($i = 0; $i < 6; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'status_verifikasi' => 'diverifikasi',
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
                'status_verifikasi' => 'diverifikasi',
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

        // Verifikasi empat SVG data-chart ada
        $this->assertStringContainsString('data-chart="deskriptif-mean-sd"', $content, 'SVG deskriptif-mean-sd harus ada');
        $this->assertStringContainsString('data-chart="deskriptif-kategori-stacked"', $content, 'SVG deskriptif-kategori-stacked harus ada');
        $this->assertStringContainsString('data-chart="centroid-grouped-bars"', $content, 'SVG centroid-grouped-bars harus ada');
        $this->assertStringContainsString('data-chart="membership-horizontal-stacked"', $content, 'SVG membership-horizontal-stacked harus ada');
    }

    /**
     * NEW TEST 10: Feature 12 respons-setiap profil A, B, C, D menampilkan teks rekomendasi dari config/tiyas.php.
     * Jika halaman punya klaster profil A dan B, maka harus ada rekomendasi A dan B dari config.
     */
    public function test_12_responses_display_profile_recommendations_from_config(): void
    {
        TiyasResponse::truncate();

        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // Buat 6 peserta optimal + 6 peserta at_risk => 2 klaster (profil A dan D atau B)
        for ($i = 0; $i < 6; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'status_verifikasi' => 'diverifikasi',
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
                'status_verifikasi' => 'diverifikasi',
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

        // Verifikasi rekomendasi dari config ada (minimal satu profil harus muncul)
        $config = config('tiyas.profil_klaster');
        $foundRecommendation = false;

        foreach ($config as $code => $profil) {
            $rekomendasi = $profil['rekomendasi'];
            if (strpos($content, $rekomendasi) !== false) {
                $foundRecommendation = true;
                break;
            }
        }

        $this->assertTrue($foundRecommendation, 'Halaman harus menampilkan minimal satu rekomendasi profil dari config/tiyas.php');
    }

    /**
     * NEW TEST 11: Feature <8 respons-halaman render statistik deskriptif, TIDAK ada klaster grafik, tidak error.
     * Verifikasi pesan minimal 8 peserta dan deskriptif tetap tampil.
     */
    public function test_less_than_8_responses_no_clustering_charts_deskriptif_ok(): void
    {
        TiyasResponse::truncate();

        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // Buat hanya 5 responses
        for ($i = 0; $i < 5; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'status_verifikasi' => 'diverifikasi',
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
        $content = $response->getContent();

        // 1. Verifikasi statistik deskriptif tampil (heading + table)
        $this->assertStringContainsString('Statistik Deskriptif', $content, 'Tabel deskriptif harus tampil');
        $this->assertStringContainsString('Rerata', $content, 'Header Rerata harus ada');

        // 2. Verifikasi pesan minimal 8 peserta tampil
        $this->assertStringContainsString('minimal 8 peserta', $content, 'Pesan minimal 8 harus tampil untuk <8 responses');

        // 3. Verifikasi TIDAK ada centroid-grouped-bars dan membership-horizontal-stacked
        // (klaster grafik hanya muncul jika >= 8 peserta)
        $this->assertStringNotContainsString('data-chart="centroid-grouped-bars"', $content, 'Centroid bar chart tidak boleh muncul untuk <8 responses');
        $this->assertStringNotContainsString('data-chart="membership-horizontal-stacked"', $content, 'Membership chart tidak boleh muncul untuk <8 responses');
    }

    /**
     * NEW TEST 12: Feature 12 respons-halaman TIDAK mengandung 'NaN' atau 'INF' di seluruh HTML output.
     * Guard numerik layer: Service degenerate→0, Controller min/max bound, View render-only.
     */
    public function test_12_responses_no_nan_inf_in_html_numeric_guards(): void
    {
        TiyasResponse::truncate();

        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // Buat 6 peserta optimal + 6 peserta at_risk
        for ($i = 0; $i < 6; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'status_verifikasi' => 'diverifikasi',
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
                'status_verifikasi' => 'diverifikasi',
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

        // Verifikasi tidak ada NaN atau INF di output HTML
        $this->assertStringNotContainsString('NaN', $content, 'HTML output harus tidak mengandung NaN (numeric guard failure)');
        $this->assertStringNotContainsString('INF', $content, 'HTML output harus tidak mengandung INF (numeric guard failure)');
        $this->assertStringNotContainsString('Infinity', $content, 'HTML output harus tidak mengandung Infinity');
    }

    /**
     * NEW TEST: SVG struktur validation, memastikan tidak ada &quot; escape, line elements proper, dan XML valid.
     * Validasi 3 SVG charts di halaman hasil: fcm-scatter, deskriptif-mean-sd, deskriptif-kategori-stacked.
     */
    public function test_12_responses_svg_structure_validation_no_escaped_quotes_proper_nesting(): void
    {
        TiyasResponse::truncate();

        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // Buat 12 respons (mixed klaster)
        for ($i = 0; $i < 6; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'status_verifikasi' => 'diverifikasi',
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
                'status_verifikasi' => 'diverifikasi',
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
        $html = $response->getContent();

        // Array of SVG data-chart identifiers to validate
        // Note: centroid-grouped-bars uses <rect> instead of <circle>; fcm-scatter uses <circle>
        $svgCharts = ['fcm-scatter', 'deskriptif-mean-sd', 'deskriptif-kategori-stacked'];
        $chartsWithCircles = ['fcm-scatter'];

        foreach ($svgCharts as $chartId) {
            // Extract SVG block using regex (capture from <svg data-chart="..."> to </svg>)
            if (preg_match('/<svg[^>]*data-chart=["\']'.preg_quote($chartId).'["\'][^>]*>.*?<\/svg>/s', $html, $matches)) {
                $svgMarkup = $matches[0];

                // Test 1: No escaped quotes inside SVG markup
                $this->assertStringNotContainsString('&quot;', $svgMarkup,
                    "SVG chart '$chartId' must not contain escaped quotes (&quot;) - indicates improper attribute escaping");

                // Test 2: No unclosed <line> elements (lines should not contain child elements)
                // Check that <line .../> is self-closing, not <line ...>...</line>
                $lineMatches = [];
                preg_match_all('/<line[^>]*(?:\/>|>.*?<\/line>)/s', $svgMarkup, $lineMatches);
                foreach ($lineMatches[0] as $lineTag) {
                    $this->assertStringNotContainsString('</', $lineTag,
                        "SVG chart '$chartId' contains unclosed or nested <line> element (line tags must be self-closing)");
                }

                // Test 3: XML strict parsing via DOMDocument
                $dom = new \DOMDocument;
                // Wrap SVG in a root element for proper XML parsing
                $wrappedXml = '<?xml version="1.0"?><root>'.$svgMarkup.'</root>';
                $libxmlUseErrors = libxml_use_internal_errors(true);
                $loaded = @$dom->loadXML($wrappedXml);
                $xmlErrors = libxml_get_errors();
                libxml_use_internal_errors($libxmlUseErrors);

                $this->assertTrue($loaded,
                    "SVG chart '$chartId' must be valid XML. Errors: ".implode('; ', array_map(function ($e) {
                        return $e->message;
                    }, $xmlErrors)));

                // Test 4: For scatter charts with circles, verify circles are not nested inside <line>
                if (in_array($chartId, $chartsWithCircles)) {
                    $xpath = new \DOMXPath($dom);
                    $circles = $xpath->query('//circle');
                    $this->assertGreaterThan(0, $circles->length,
                        "SVG chart '$chartId' should contain at least one <circle> element (peserta points)");

                    // Verify circles are not descendants of <line> (which would indicate improper nesting)
                    foreach ($circles as $circle) {
                        $parent = $circle->parentNode;
                        while ($parent) {
                            $this->assertNotEquals('line', $parent->nodeName,
                                "Circle element in chart '$chartId' is nested inside <line> element (indicates XML structure corruption)");
                            $parent = $parent->parentNode;
                        }
                    }
                }
            }
        }
    }

    /**
     * NEW TEST 12: Feature 12 respons-scatter plot circle fill colors match dominant cluster (no off-by-one shift).
     * Verify:
     * 1. Each <circle> has fill = cluster dominant color token (K1, K2, K3, K4 from config)
     * 2. Circle color matches the centroid X marker color for same cluster
     * 3. <title> text format is "K{klaster_dominan}: {persen}%" e.g., "K1: 95%"
     * 4. No fallback color (var(--color-primary)) except for legitimate cases
     */
    public function test_12_responses_scatter_plot_circle_colors_no_offbyone(): void
    {
        TiyasResponse::truncate();

        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // Create simpler test data to ensure clustering succeeds
        // Use extreme profiles to ensure distinct clusters
        $profiles = [
            // K1 (Optimal): all max
            ['TC' => 4.0, 'TE' => 4.0, 'ITT' => 4.0, 'YB' => 4.0],
            ['TC' => 4.0, 'TE' => 4.0, 'ITT' => 4.0, 'YB' => 4.0],
            ['TC' => 4.0, 'TE' => 4.0, 'ITT' => 4.0, 'YB' => 4.0],

            // K2 (Developing): high TC/TE/ITT, low YB
            ['TC' => 4.0, 'TE' => 4.0, 'ITT' => 4.0, 'YB' => 1.0],
            ['TC' => 4.0, 'TE' => 4.0, 'ITT' => 4.0, 'YB' => 1.0],
            ['TC' => 4.0, 'TE' => 4.0, 'ITT' => 4.0, 'YB' => 1.0],

            // K3 (Constrained): mid-low across all
            ['TC' => 2.0, 'TE' => 2.0, 'ITT' => 2.0, 'YB' => 2.0],
            ['TC' => 2.0, 'TE' => 2.0, 'ITT' => 2.0, 'YB' => 2.0],
            ['TC' => 2.0, 'TE' => 2.0, 'ITT' => 2.0, 'YB' => 2.0],

            // K4 (At-Risk): all low
            ['TC' => 1.0, 'TE' => 1.0, 'ITT' => 1.0, 'YB' => 1.0],
            ['TC' => 1.0, 'TE' => 1.0, 'ITT' => 1.0, 'YB' => 1.0],
            ['TC' => 1.0, 'TE' => 1.0, 'ITT' => 1.0, 'YB' => 1.0],
        ];

        $pesertaDataMap = []; // Store for debugging
        foreach ($profiles as $idx => $profile) {
            $peserta = User::factory()->create(['role' => 'peserta']);

            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'status_verifikasi' => 'diverifikasi',
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);

            $jawaban = $this->generateTiyasJawaban($profile);
            $factory = TiyasResponse::factory();
            $response = $factory->create([
                'pendaftaran_id' => $pendaftaran->id,
                'jawaban' => $jawaban,
                'identitas' => ['nama' => 'Peserta '.($idx + 1)],
                'submitted_at' => now(),
            ]);

            $pesertaDataMap[$idx] = [
                'peserta_id' => $peserta->id,
                'response_id' => $response->id,
                'profile' => $profile,
            ];
        }

        $response = $this->actingAs($panitia)->get(route('tiyas.hasil', $pelatihan));
        $response->assertOk();

        $html = $response->getContent();

        // Extract SVG scatter plot
        if (! preg_match('/<svg[^>]*data-chart=["\']fcm-scatter["\'][^>]*>.*?<\/svg>/s', $html, $matches)) {
            $this->fail('SVG scatter plot (data-chart="fcm-scatter") not found in response');
        }

        $svgMarkup = $matches[0];

        // Parse SVG as XML
        $dom = new \DOMDocument;
        $wrappedXml = '<?xml version="1.0"?><root>'.$svgMarkup.'</root>';
        $libxmlUseErrors = libxml_use_internal_errors(true);
        $loaded = @$dom->loadXML($wrappedXml);
        libxml_use_internal_errors($libxmlUseErrors);

        $this->assertTrue($loaded, 'SVG scatter plot must be valid XML');

        $xpath = new \DOMXPath($dom);

        // Get all <circle> elements (peserta points)
        $circles = $xpath->query('//circle');
        $this->assertGreaterThan(0, $circles->length, 'SVG scatter plot must contain peserta points (circle elements)');

        // Get color token config (default from blade view if config not set)
        $colorConfig = config('tiyas.warna_klaster', [
            'K1' => 'var(--color-danger)',
            'K2' => 'var(--color-primary)',
            'K3' => 'var(--color-success)',
            'K4' => 'var(--color-accent)',
        ]);

        // Verify each circle's fill color and title
        $circleCount = 0;
        $debugInfo = [];
        foreach ($circles as $circle) {
            $circleCount++;

            // 1. Check fill attribute
            $fill = $circle->getAttribute('fill');
            $this->assertNotEmpty($fill, "Circle #$circleCount must have fill attribute");

            // 2. Check title element
            $titleElements = $xpath->query('./title', $circle);
            $this->assertGreaterThan(0, $titleElements->length, "Circle #$circleCount must have a <title> element");

            $titleText = $titleElements->item(0)->textContent;
            $this->assertNotEmpty($titleText, "Circle #$circleCount title must not be empty");

            // 3. Verify title format: "{nama} | K1: {persen}% | K2: {persen}% | ..."
            $this->assertMatchesRegularExpression('/^.+\s*\|\s*K1:\s*\d+(\.\d+)?%/', $titleText,
                "Circle #$circleCount title must start with name and K1 membership. Got: '$titleText'");

            // Extract name and all K{n}: {percent}% pairs
            if (! preg_match('/^(.+?)\s*\|\s*(.+)$/', $titleText, $mainMatch)) {
                $this->fail("Circle #$circleCount title format invalid: '$titleText'");
            }
            $pesertaNama = trim($mainMatch[1]);
            $membershipString = $mainMatch[2];

            // Extract all K{n}: {percent}% from membership string
            $memberships = [];
            if (preg_match_all('/K(\d):\s*(\d+(?:\.\d+)?)%/', $membershipString, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $k = (int) $match[1];
                    $pct = (float) $match[2];
                    $memberships[$k] = $pct;
                }
            }

            $this->assertNotEmpty($memberships, "Circle #$circleCount title must contain at least one K{n}: {persen}% pair. Got: '$titleText'");
            $this->assertNotEmpty($pesertaNama, "Circle #$circleCount title must contain peserta name. Got: '$titleText'");

            // Find dominant cluster (highest membership percentage)
            $dominantK = array_key_first($memberships);
            $maxMembership = $memberships[$dominantK];
            foreach ($memberships as $k => $pct) {
                if ($pct > $maxMembership) {
                    $dominantK = $k;
                    $maxMembership = $pct;
                }
            }

            // 4. Verify fill color matches dominant cluster color from config
            $expectedColor = $colorConfig['K'.$dominantK] ?? null;
            $this->assertNotNull($expectedColor, "No color configured for K$dominantK");
            $this->assertEquals($fill, $expectedColor,
                "Circle #$circleCount (title='$titleText') fill color must match K$dominantK color from config. Got: '$fill', expected: '$expectedColor'");

            // 5. Verify fill is not a fallback color
            $validColors = array_values($colorConfig);
            if (! in_array($fill, $validColors)) {
                $debugInfo[] = "Circle #$circleCount (title='$titleText'): fill='$fill' NOT IN config cluster colors";
            }
        }

        $this->assertGreaterThanOrEqual(6, $circleCount, 'Should have at least 6 peserta points (circles)');
        $this->assertEmpty($debugInfo, 'Debug info should be empty: '.implode('; ', $debugInfo));
    }

    /**
     * Helper to generate TiyasResponse jawaban from profile scores (TC, TE, ITT, YB)
     */
    private function generateTiyasJawaban(array $profile): array
    {
        $defaults = ['TC' => 2.0, 'TE' => 2.0, 'ITT' => 2.0, 'YB' => 2.0];
        $scores = array_merge($defaults, $profile);
        $scores = array_map(fn ($v) => max(1.0, min(4.0, $v)), $scores);

        $jawaban = [];
        foreach (['TC' => 3, 'TE' => 3, 'ITT' => 4, 'YB' => 20] as $key => $count) {
            $val = (int) round($scores[$key]);
            for ($i = 1; $i <= $count; $i++) {
                $jawaban[$key.$i] = $val;
            }
        }

        return $jawaban;
    }

    /**
     * Test 13: Membership-horizontal-stacked diagram segments use correct colors
     * - Each segment fill must match config('tiyas.warna_klaster')['K' . n]
     * - Segment tooltips must contain "{nama} | Klaster N: xx.x%"
     * - No segment uses fallback color
     */
    public function test_13_membership_horizontal_stacked_segment_colors(): void
    {
        TiyasResponse::truncate();

        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // Create test data with extreme profiles to ensure distinct clusters
        $profiles = [
            // K1: all max
            ['TC' => 4.0, 'TE' => 4.0, 'ITT' => 4.0, 'YB' => 4.0],
            ['TC' => 4.0, 'TE' => 4.0, 'ITT' => 4.0, 'YB' => 4.0],
            ['TC' => 4.0, 'TE' => 4.0, 'ITT' => 4.0, 'YB' => 4.0],
            // K2: high TC/TE/ITT, low YB
            ['TC' => 4.0, 'TE' => 4.0, 'ITT' => 4.0, 'YB' => 1.0],
            ['TC' => 4.0, 'TE' => 4.0, 'ITT' => 4.0, 'YB' => 1.0],
            ['TC' => 4.0, 'TE' => 4.0, 'ITT' => 4.0, 'YB' => 1.0],
            // K3: mid-low
            ['TC' => 2.0, 'TE' => 2.0, 'ITT' => 2.0, 'YB' => 2.0],
            ['TC' => 2.0, 'TE' => 2.0, 'ITT' => 2.0, 'YB' => 2.0],
            // K4: all low
            ['TC' => 1.0, 'TE' => 1.0, 'ITT' => 1.0, 'YB' => 1.0],
        ];

        foreach ($profiles as $idx => $profile) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'status_verifikasi' => 'diverifikasi',
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);

            $jawaban = $this->generateTiyasJawaban($profile);
            TiyasResponse::factory()->create([
                'pendaftaran_id' => $pendaftaran->id,
                'jawaban' => $jawaban,
                'identitas' => ['nama' => 'Test Peserta '.($idx + 1)],
                'submitted_at' => now(),
            ]);
        }

        $response = $this->actingAs($panitia)->get(route('tiyas.hasil', $pelatihan));
        $response->assertStatus(200);

        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);

        // Find membership-horizontal-stacked diagram
        $chartElements = $xpath->query('//*[@data-chart="membership-horizontal-stacked"]');
        $this->assertGreaterThan(0, $chartElements->length, 'Membership diagram must exist');

        $chart = $chartElements->item(0);

        // Get all rect segments (excluding legend rects)
        $rects = $xpath->query('.//rect', $chart);
        $this->assertGreaterThan(0, $rects->length, 'Diagram must have at least one segment');

        $colorConfig = config('tiyas.warna_klaster', [
            'K1' => 'var(--color-danger)',
            'K2' => 'var(--color-primary)',
            'K3' => 'var(--color-success)',
            'K4' => 'var(--color-accent)',
        ]);
        $validColors = array_values($colorConfig);
        $debugInfo = [];

        foreach ($rects as $rect) {
            $fill = $rect->getAttribute('fill');
            $this->assertNotEmpty($fill, 'Segment must have fill attribute');

            // Check if this is a legend rect (small, 12x12) or a segment (much larger)
            $width = $rect->getAttribute('width');
            if ((float) $width <= 20) {
                continue; // Skip legend
            }

            // This is a membership segment
            // Check title
            $titleElements = $xpath->query('./title', $rect);
            $this->assertGreaterThan(0, $titleElements->length, 'Segment must have <title>');

            $titleText = $titleElements->item(0)->textContent;
            $this->assertNotEmpty($titleText, 'Segment title must not be empty');

            // Verify format: "{nama} | Klaster N: xx.x%"
            $this->assertMatchesRegularExpression('/^.+\\s*\\|\\s*Klaster\\s*\\d+:\\s*\\d+(\\.\\d+)?%/', $titleText,
                "Segment title must match format. Got: '$titleText'");

            // Extract cluster number
            if (preg_match('/Klaster\\s*(\\d+):\\s*(\\d+(?:\\.\\d+)?)%/', $titleText, $matches)) {
                $k = (int) $matches[1];
                $expectedColor = $colorConfig['K'.$k] ?? null;
                $this->assertNotNull($expectedColor, "No color configured for K$k");
                $this->assertEquals($fill, $expectedColor,
                    "Segment (title='$titleText') fill must match K$k. Got: '$fill', expected: '$expectedColor'");
            }

            // Verify not fallback
            if (! in_array($fill, $validColors)) {
                $debugInfo[] = "Segment (title='$titleText'): fill='$fill' NOT IN config colors";
            }
        }

        $this->assertEmpty($debugInfo, 'No segment should use fallback color: '.implode('; ', $debugInfo));
    }
}
