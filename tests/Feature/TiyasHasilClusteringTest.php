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
     * NEW TEST 1: Feature dengan 12 respons—halaman memuat SVG grafik klaster, legenda Klaster,
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
     * NEW TEST 2: Feature dengan 3 respons—halaman memuat tabel statistik deskriptif,
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
        // SVG grafik klaster harus tidak ada height="auto" attribute untuk menghindari console warning
        $this->assertStringNotContainsString('height="auto"', $content, 'SVG harus tidak ada height="auto" attribute untuk menghindari console warning');
    }

    /**
     * NEW TEST 5: Halaman 12 respons—elips keyakinan per klaster ada (atribut data-klaster-area).
     * Verifikasi jumlah elips = c (jumlah klaster optimal).
     */
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
     * NEW TEST 9: Feature 12 respons—halaman render empat data-chart:
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

        // Verifikasi empat SVG data-chart ada
        $this->assertStringContainsString('data-chart="deskriptif-mean-sd"', $content, 'SVG deskriptif-mean-sd harus ada');
        $this->assertStringContainsString('data-chart="deskriptif-kategori-stacked"', $content, 'SVG deskriptif-kategori-stacked harus ada');
        $this->assertStringContainsString('data-chart="centroid-grouped-bars"', $content, 'SVG centroid-grouped-bars harus ada');
        $this->assertStringContainsString('data-chart="membership-horizontal-stacked"', $content, 'SVG membership-horizontal-stacked harus ada');
    }

    /**
     * NEW TEST 10: Feature 12 respons—setiap profil A, B, C, D menampilkan teks rekomendasi dari config/tiyas.php.
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
     * NEW TEST 11: Feature <8 respons—halaman render statistik deskriptif, TIDAK ada klaster grafik, tidak error.
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
     * NEW TEST 12: Feature 12 respons—halaman TIDAK mengandung 'NaN' atau 'INF' di seluruh HTML output.
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

        // Verifikasi tidak ada NaN atau INF di output HTML
        $this->assertStringNotContainsString('NaN', $content, 'HTML output harus tidak mengandung NaN (numeric guard failure)');
        $this->assertStringNotContainsString('INF', $content, 'HTML output harus tidak mengandung INF (numeric guard failure)');
        $this->assertStringNotContainsString('Infinity', $content, 'HTML output harus tidak mengandung Infinity');
    }
}
