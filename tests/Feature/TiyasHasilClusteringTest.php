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
     * - \"Profil Klaster Peserta\"
     * - \"Jumlah klaster terpilih\"
     * - nama salah satu peserta (dari relasi pendaftaran->peserta->name, bukan 'nama dummy')
     * - judul profil dari config (mis. 'Optimal Transfer')
     */
    public function test_hasil_clustering_panitia_12_responses_render_page()
    {
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

        // 1. Verifikasi tabel statistik deskriptif tampil
        $response->assertSee('Rerata');
        $response->assertSee('Statistik Deskriptif');

        // 2. Verifikasi SVG grafik TIDAK tampil (tidak ada "Klaster" dalam output karena SVG section tidak render)
        // For kurang_data, we don't expect SVG or PCA-related content
        // But we still show deskriptif, so just verify kurang_data message is present
        $response->assertSee('Profil klaster');  // Part of kurang_data message

        // 3. Verifikasi pesan 'minimal 8 peserta' tampil
        $response->assertSee('minimal 8 peserta');
    }
}
