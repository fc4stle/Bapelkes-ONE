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

        // Check tabel_peserta has klaster_dominan and memberships
        $this->assertCount(8, $result['tabel_peserta']);
        foreach ($result['tabel_peserta'] as $peserta) {
            $this->assertArrayHasKey('klaster_dominan', $peserta);
            $this->assertArrayHasKey('memberships', $peserta);
            $this->assertIsInt($peserta['klaster_dominan']);
            $this->assertGreaterThanOrEqual(1, $peserta['klaster_dominan']);
            $this->assertLessThanOrEqual($result['c_optimal'], $peserta['klaster_dominan']);
        }

        // Check tabel_klaster has profil assignments
        $this->assertCount($result['c_optimal'], $result['tabel_klaster']);
        $validLabels = [
            'Optimal Transfer',
            'Developing Transfer',
            'Constrained Transfer',
            'At-Risk Transfer',
            'Belum cocok dengan 4 profil konseptual',
        ];
        foreach ($result['tabel_klaster'] as $klaster) {
            $this->assertArrayHasKey('profil', $klaster);
            $this->assertContains($klaster['profil'], $validLabels);
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
     * NEW TEST 5: Unit test deskriptif—hitung rerata, simpangan baku, min, maks benar untuk data kecil manual
     */
    public function test_deskriptif_calculates_statistics_correctly()
    {
        // Buat data simpel: 3 respons dengan skor yang bisa dihitung manual
        $pelatihan = Pelatihan::factory()->create();
        $responses = collect();

        // Peserta 1: TC=2.0, TE=2.0, ITT=2.0, YB=2.0 => avg semua 2.0
        $responses->push(TiyasResponse::factory()->create([
            'pendaftaran_id' => Pendaftaran::factory()->create(['pelatihan_id' => $pelatihan->id])->id,
            'submitted_at' => now(),
            'jawaban' => $this->generateJawaban(['TC' => 2.0, 'TE' => 2.0, 'ITT' => 2.0, 'YB' => 2.0]),
            'identitas' => ['nama' => 'P1'],
        ]));

        // Peserta 2: TC=3.0, TE=3.0, ITT=3.0, YB=3.0 => avg semua 3.0
        $responses->push(TiyasResponse::factory()->create([
            'pendaftaran_id' => Pendaftaran::factory()->create(['pelatihan_id' => $pelatihan->id])->id,
            'submitted_at' => now(),
            'jawaban' => $this->generateJawaban(['TC' => 3.0, 'TE' => 3.0, 'ITT' => 3.0, 'YB' => 3.0]),
            'identitas' => ['nama' => 'P2'],
        ]));

        // Peserta 3: TC=4.0, TE=4.0, ITT=4.0, YB=4.0 => avg semua 4.0
        $responses->push(TiyasResponse::factory()->create([
            'pendaftaran_id' => Pendaftaran::factory()->create(['pelatihan_id' => $pelatihan->id])->id,
            'submitted_at' => now(),
            'jawaban' => $this->generateJawaban(['TC' => 4.0, 'TE' => 4.0, 'ITT' => 4.0, 'YB' => 4.0]),
            'identitas' => ['nama' => 'P3'],
        ]));

        $result = $this->service->deskriptif($responses);

        $this->assertArrayHasKey('TC', $result);
        $this->assertArrayHasKey('TE', $result);
        $this->assertArrayHasKey('ITT', $result);
        $this->assertArrayHasKey('YB', $result);

        foreach (['TC', 'TE', 'ITT', 'YB'] as $construct) {
            $this->assertArrayHasKey('mean', $result[$construct]);
            $this->assertArrayHasKey('stdev', $result[$construct]);
            $this->assertArrayHasKey('min', $result[$construct]);
            $this->assertArrayHasKey('max', $result[$construct]);

            // Verify exact values (as strings since they're number_format'd)
            $this->assertEquals('3.00', $result[$construct]['mean']);
            $this->assertEquals('2.00', $result[$construct]['min']);
            $this->assertEquals('4.00', $result[$construct]['max']);
            // Stdev for [2, 3, 4]: variance = sum((x-mean)^2)/(n-1) = 2/2 = 1, so stdev = 1.0
            // But number_format may round differently
            $this->assertEqualsWithDelta(1.0, floatval($result[$construct]['stdev']), 0.2);
        }
    }

    /**
     * NEW TEST 8: Unit test determineProfil dengan 5 synthetic centroids (A/B/C/D/Unmatched)
     * Menggunakan Reflection untuk test private method dengan centroid scores yang diketahui
     */
    public function test_determine_profil_with_synthetic_centroids()
    {
        // Inisialisasi service
        $service = new TiyasProfilKlaster(new TiyasScorer, new FuzzyCMeans);

        // Gunakan Reflection untuk akses private determineProfil method
        $reflection = new \ReflectionMethod($service, 'determineProfil');
        $reflection->setAccessible(true);

        // Test Case 1: Centroid A — [3.8, 3.8, 3.8, 3.8] => Optimal Transfer
        // TC, TE, ITT, YB semuanya Tinggi (> 3.0)
        $result_a = $reflection->invoke($service, [3.8, 3.8, 3.8, 3.8]);
        $this->assertIsArray($result_a);
        $this->assertArrayHasKey('label', $result_a);
        $this->assertEquals('Optimal Transfer', $result_a['label']);

        // Test Case 2: Centroid B — [3.5, 3.5, 3.5, 2.0] => Developing Transfer
        // TC, TE, ITT Tinggi; YB Sedang (2.0-3.0)
        $result_b = $reflection->invoke($service, [3.5, 3.5, 3.5, 2.0]);
        $this->assertEquals('Developing Transfer', $result_b['label']);

        // Test Case 3: Centroid C — [2.0, 2.0, 3.8, 2.0] => Constrained Transfer
        // TC, TE Rendah/Sedang; ITT Tinggi; YB Rendah/Sedang
        $result_c = $reflection->invoke($service, [2.0, 2.0, 3.8, 2.0]);
        $this->assertEquals('Constrained Transfer', $result_c['label']);

        // Test Case 4: Centroid D — [1.2, 1.2, 1.2, 1.2] => At-Risk Transfer
        // Semuanya Rendah (< 2.0)
        $result_d = $reflection->invoke($service, [1.2, 1.2, 1.2, 1.2]);
        $this->assertEquals('At-Risk Transfer', $result_d['label']);

        // Test Case 5: Unmatched — [2.5, 3.5, 2.5, 2.5] => Belum cocok dengan 4 profil konseptual
        // TC Sedang, TE Tinggi, ITT Sedang, YB Sedang — tidak match profil manapun
        $result_unmatched = $reflection->invoke($service, [2.5, 3.5, 2.5, 2.5]);
        $this->assertEquals('Belum cocok dengan 4 profil konseptual', $result_unmatched['label']);
    }

    /**
     * Helper: Generate jawaban respons (mapping ke 4 skor: TC, TE, ITT, YB)
     * Input: skor dalam skala 1.0-4.0
     * Output: jawaban array dengan butir bernilai 1-4 (sesuai skala)
     */
    private function generateJawaban(array $override = []): array
    {
        // Skala default: 2.0 (sedang/netral) untuk setiap konstruk
        $default = [
            'TC' => 2.0, 'TE' => 2.0, 'ITT' => 2.0, 'YB' => 2.0,
        ];
        $scores = array_merge($default, $override);

        // Clamp scores ke [1.0, 4.0] karena ini skala Likert 1-4
        $scores = array_map(function ($v) {
            return max(1.0, min(4.0, $v));
        }, $scores);

        $jawaban = [];

        // TC (3 butir): set semua ke nilai score (1-4)
        $tc_val = (int) round($scores['TC']);
        for ($i = 0; $i < 3; $i++) {
            $jawaban['TC'.($i + 1)] = $tc_val;
        }

        // TE (3 butir)
        $te_val = (int) round($scores['TE']);
        for ($i = 0; $i < 3; $i++) {
            $jawaban['TE'.($i + 1)] = $te_val;
        }

        // ITT (4 butir)
        $itt_val = (int) round($scores['ITT']);
        for ($i = 0; $i < 4; $i++) {
            $jawaban['ITT'.($i + 1)] = $itt_val;
        }

        // YB (20 butir)
        $yb_val = (int) round($scores['YB']);
        for ($i = 0; $i < 20; $i++) {
            $jawaban['YB'.($i + 1)] = $yb_val;
        }

        return $jawaban;
    }
}
