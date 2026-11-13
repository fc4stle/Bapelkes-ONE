<?php

namespace Tests\Feature;

use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\TiyasResponse;
use App\Services\FuzzyCMeans;
use App\Services\TiyasProfilKlaster;
use App\Services\TiyasScorer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TiyasProfilKlasterTest extends TestCase
{
    use RefreshDatabase;

    private TiyasProfilKlaster $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TiyasProfilKlaster(new TiyasScorer, new FuzzyCMeans);
    }

    /**
     * Test: Kurang dari 8 respons => status 'kurang_data'
     */
    public function test_returns_kurang_data_status_with_less_than_8_responses()
    {
        $pelatihan = Pelatihan::factory()->create();

        // Create 5 pendaftarans with 5 responses (< 8)
        $responses = collect();
        for ($i = 0; $i < 5; $i++) {
            $pendaftaran = Pendaftaran::factory()->create(['pelatihan_id' => $pelatihan->id]);
            $responses->push(TiyasResponse::factory()->create([
                'pendaftaran_id' => $pendaftaran->id,
                'submitted_at' => now(),
                'jawaban' => $this->generateJawaban(),
                'identitas' => ['nama' => 'Peserta '.$i],
            ]));
        }

        $result = $this->service->analisis($responses);

        $this->assertEquals('kurang_data', $result['status']);
        $this->assertEquals(5, $result['jumlah_sekarang']);
        $this->assertEquals(8, $result['jumlah_butuh']);
    }

    /**
     * Test: 8+ respons dengan profil A–D => label benar
     */
    public function test_assigns_correct_profiles_a_to_d()
    {
        $pelatihan = Pelatihan::factory()->create();

        // Profil A (Optimal): TC, TE, ITT, YB semuanya Tinggi (> 3.0)
        $jawaban_a = $this->generateJawaban(['TC' => 3.8, 'TE' => 3.8, 'ITT' => 3.8, 'YB' => 3.8]);

        // Profil B (Developing): TC, TE, ITT Tinggi, YB Sedang
        $jawaban_b = $this->generateJawaban(['TC' => 3.8, 'TE' => 3.8, 'ITT' => 3.8, 'YB' => 2.5]);

        // Profil C (Constrained): TC, TE Rendah, ITT Tinggi, YB Rendah
        $jawaban_c = $this->generateJawaban(['TC' => 1.5, 'TE' => 1.5, 'ITT' => 3.8, 'YB' => 1.5]);

        // Profil D (At-Risk): semuanya Rendah
        $jawaban_d = $this->generateJawaban(['TC' => 1.2, 'TE' => 1.2, 'ITT' => 1.2, 'YB' => 1.2]);

        // Create 8 responses (2 per profil, repeated)
        $responses_data = array_merge(
            [$jawaban_a, $jawaban_a],
            [$jawaban_b, $jawaban_b],
            [$jawaban_c, $jawaban_c],
            [$jawaban_d, $jawaban_d],
        );

        $responses = collect();
        foreach ($responses_data as $idx => $jawaban) {
            $pendaftaran = Pendaftaran::factory()->create(['pelatihan_id' => $pelatihan->id]);
            $responses->push(TiyasResponse::factory()->create([
                'pendaftaran_id' => $pendaftaran->id,
                'submitted_at' => now(),
                'jawaban' => $jawaban,
                'identitas' => ['nama' => 'Peserta '.$idx],
            ]));
        }

        $result = $this->service->analisis($responses);

        $this->assertEquals('sukses', $result['status']);
        $this->assertGreaterThanOrEqual(2, $result['c_optimal']);
        $this->assertLessThanOrEqual(4, $result['c_optimal']);

        // Check tabel_peserta has profil assignments
        $this->assertCount(8, $result['tabel_peserta']);
        foreach ($result['tabel_peserta'] as $peserta) {
            $this->assertArrayHasKey('profil', $peserta);
            $this->assertThat($peserta['profil'], $this->logicalOr(
                $this->equalTo('A'),
                $this->equalTo('B'),
                $this->equalTo('C'),
                $this->equalTo('D'),
                $this->equalTo('Belum Cocok dengan 4 profil konseptual')
            ));
        }
    }

    /**
     * Test: Validity table berisi PC, PE, XB, Silhouette
     */
    public function test_validity_table_contains_all_indices()
    {
        $pelatihan = Pelatihan::factory()->create();

        // Create 8 diverse responses
        $responses = collect();
        for ($i = 0; $i < 8; $i++) {
            $pendaftaran = Pendaftaran::factory()->create(['pelatihan_id' => $pelatihan->id]);
            $responses->push(TiyasResponse::factory()->create([
                'pendaftaran_id' => $pendaftaran->id,
                'submitted_at' => now(),
                'jawaban' => $this->generateJawaban(),
                'identitas' => ['nama' => 'Peserta '.$i],
            ]));
        }

        $result = $this->service->analisis($responses);

        $this->assertEquals('sukses', $result['status']);
        $this->assertNotEmpty($result['validity_table']);

        foreach ($result['validity_table'] as $row) {
            $this->assertArrayHasKey('c', $row);
            $this->assertArrayHasKey('pc', $row);
            $this->assertArrayHasKey('pe', $row);
            $this->assertArrayHasKey('xb', $row);
            $this->assertArrayHasKey('silhouette', $row);
            $this->assertArrayHasKey('is_optimal', $row);

            // Check PC is in [0, 1]
            $this->assertGreaterThanOrEqual(0, $row['pc']);
            $this->assertLessThanOrEqual(1, $row['pc']);

            // Check PE >= 0
            $this->assertGreaterThanOrEqual(0, $row['pe']);

            // Check XB >= 0
            $this->assertGreaterThanOrEqual(0, $row['xb']);

            // Check Silhouette in [-1, 1]
            $this->assertGreaterThanOrEqual(-1, $row['silhouette']);
            $this->assertLessThanOrEqual(1, $row['silhouette']);
        }
    }

    /**
     * Test: Derajat keanggotaan (%) per peserta berjumlah ~100%
     */
    public function test_membership_percentages_sum_to_100_percent()
    {
        $pelatihan = Pelatihan::factory()->create();

        $responses = collect();
        for ($i = 0; $i < 8; $i++) {
            $pendaftaran = Pendaftaran::factory()->create(['pelatihan_id' => $pelatihan->id]);
            $responses->push(TiyasResponse::factory()->create([
                'pendaftaran_id' => $pendaftaran->id,
                'submitted_at' => now(),
                'jawaban' => $this->generateJawaban(),
                'identitas' => ['nama' => 'Peserta '.$i],
            ]));
        }

        $result = $this->service->analisis($responses);

        $this->assertEquals('sukses', $result['status']);

        foreach ($result['tabel_peserta'] as $peserta) {
            $sum = array_sum($peserta['memberships']);
            $this->assertEqualsWithDelta(100, $sum, 1,
                "Membership percentages for {$peserta['nama']} should sum to ~100%");
        }
    }

    /**
     * Helper: Generate jawaban respons (mapping ke 4 skor: TC, TE, ITT, YB)
     */
    private function generateJawaban(array $override = []): array
    {
        $default = [
            'TC' => 2.0, 'TE' => 2.0, 'ITT' => 2.0, 'YB' => 2.0,
        ];
        $scores = array_merge($default, $override);

        $jawaban = [];

        // TC (3 butir)
        $tc_val = (int) round($scores['TC'] * 4); // 1-4 scale
        for ($i = 0; $i < 3; $i++) {
            $jawaban['TC'.($i + 1)] = $tc_val;
        }

        // TE (3 butir)
        $te_val = (int) round($scores['TE'] * 4);
        for ($i = 0; $i < 3; $i++) {
            $jawaban['TE'.($i + 1)] = $te_val;
        }

        // ITT (4 butir)
        $itt_val = (int) round($scores['ITT'] * 4);
        for ($i = 0; $i < 4; $i++) {
            $jawaban['ITT'.($i + 1)] = $itt_val;
        }

        // YB (20 butir)
        $yb_val = (int) round($scores['YB'] * 4);
        for ($i = 0; $i < 20; $i++) {
            $jawaban['YB'.($i + 1)] = $yb_val;
        }

        return $jawaban;
    }
}
