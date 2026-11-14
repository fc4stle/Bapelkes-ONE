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
        $response->assertSee('Optimal');
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
}
