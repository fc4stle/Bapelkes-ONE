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
     * NEW TEST 7: Halaman 12 respons—titik peserta ada <title> dengan nama peserta dan klaster.
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
}
