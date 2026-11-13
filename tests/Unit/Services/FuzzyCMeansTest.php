<?php

namespace Tests\Unit\Services;

use App\Services\FuzzyCMeans;
use PHPUnit\Framework\TestCase;

class FuzzyCMeansTest extends TestCase
{
    private FuzzyCMeans $fcm;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fcm = new FuzzyCMeans;
    }

    /**
     * Test: Dua kelompok sintetis terpisah jelas => FCM menemukan c=2
     */
    public function test_fcm_detects_two_clear_clusters()
    {
        // Kelompok 1: skor rendah
        $group1 = [
            [1.0, 1.2, 1.1, 1.3],
            [1.1, 1.0, 1.2, 1.0],
            [1.2, 1.1, 1.0, 1.2],
            [1.0, 1.1, 1.1, 1.1],
        ];

        // Kelompok 2: skor tinggi
        $group2 = [
            [3.8, 3.9, 3.7, 3.9],
            [3.9, 3.8, 3.9, 3.8],
            [3.7, 3.9, 3.8, 3.9],
            [3.8, 3.7, 3.9, 3.7],
        ];

        $data = array_merge($group1, $group2);

        // Tentukan jumlah klaster optimal (harus = 2)
        $c_optimal = $this->fcm->pilihJumlahKlaster($data, 2, 3);
        $this->assertEquals(2, $c_optimal, 'Two distinct clusters should be detected');
    }

    /**
     * Test: Membership matrix tiap baris berjumlah ~1.0
     */
    public function test_membership_rows_sum_to_one()
    {
        $data = [
            [1.0, 1.0, 1.0, 1.0],
            [1.5, 1.5, 1.5, 1.5],
            [2.0, 2.0, 2.0, 2.0],
            [3.0, 3.0, 3.0, 3.0],
            [3.5, 3.5, 3.5, 3.5],
            [4.0, 4.0, 4.0, 4.0],
        ];

        $result = $this->fcm->fit($data, 2);
        $U = $result['U'];

        foreach ($U as $i => $row) {
            $sum = array_sum($row);
            $this->assertAlmostEquals(1.0, $sum, 5, "Row $i membership should sum to ~1.0 (got $sum)");
        }
    }

    /**
     * Test: Hasil identik pada dua kali jalan (deterministic)
     */
    public function test_fcm_produces_identical_results_on_repeated_runs()
    {
        $data = [
            [1.0, 1.0, 1.0, 1.0],
            [1.2, 1.1, 1.2, 1.0],
            [3.0, 3.0, 3.0, 3.0],
            [3.2, 3.1, 3.2, 3.0],
            [2.0, 2.0, 2.0, 2.0],
            [2.2, 2.1, 2.2, 2.0],
            [1.5, 1.4, 1.5, 1.4],
            [3.5, 3.4, 3.5, 3.4],
        ];

        // Run 1
        $result1 = $this->fcm->fit($data, 2);
        $U1 = $result1['U'];
        $centroid1 = $result1['centroid'];

        // Run 2
        $result2 = $this->fcm->fit($data, 2);
        $U2 = $result2['U'];
        $centroid2 = $result2['centroid'];

        // Compare U matrices
        for ($i = 0; $i < count($U1); $i++) {
            for ($j = 0; $j < count($U1[$i]); $j++) {
                $this->assertAlmostEquals($U1[$i][$j], $U2[$i][$j], 8,
                    "U[$i][$j] should be identical on repeated runs");
            }
        }

        // Compare centroids
        for ($i = 0; $i < count($centroid1); $i++) {
            for ($j = 0; $j < count($centroid1[$i]); $j++) {
                $this->assertAlmostEquals($centroid1[$i][$j], $centroid2[$i][$j], 8,
                    "Centroid[$i][$j] should be identical on repeated runs");
            }
        }
    }

    /**
     * Test: Centroid dekat rata-rata kelompok
     */
    public function test_centroids_close_to_cluster_means()
    {
        $group1 = [
            [1.0, 1.0, 1.0, 1.0],
            [1.1, 1.0, 1.1, 1.0],
            [1.0, 1.1, 1.0, 1.1],
        ];
        $group2 = [
            [3.9, 3.9, 3.9, 3.9],
            [3.8, 4.0, 3.8, 4.0],
            [4.0, 3.8, 4.0, 3.8],
        ];

        $data = array_merge($group1, $group2);

        $result = $this->fcm->fit($data, 2);
        $centroids = $result['centroid'];

        // Calculate means
        $mean1 = [
            array_sum(array_column($group1, 0)) / count($group1),
            array_sum(array_column($group1, 1)) / count($group1),
            array_sum(array_column($group1, 2)) / count($group1),
            array_sum(array_column($group1, 3)) / count($group1),
        ];

        $mean2 = [
            array_sum(array_column($group2, 0)) / count($group2),
            array_sum(array_column($group2, 1)) / count($group2),
            array_sum(array_column($group2, 2)) / count($group2),
            array_sum(array_column($group2, 3)) / count($group2),
        ];

        // One centroid should be close to mean1, other to mean2
        $dist_c0_to_mean1 = sqrt(array_sum(array_map(fn ($a, $b) => ($a - $b) ** 2, $centroids[0], $mean1)));
        $dist_c0_to_mean2 = sqrt(array_sum(array_map(fn ($a, $b) => ($a - $b) ** 2, $centroids[0], $mean2)));
        $dist_c1_to_mean1 = sqrt(array_sum(array_map(fn ($a, $b) => ($a - $b) ** 2, $centroids[1], $mean1)));
        $dist_c1_to_mean2 = sqrt(array_sum(array_map(fn ($a, $b) => ($a - $b) ** 2, $centroids[1], $mean2)));

        // Check: one centroid close to mean1, other to mean2
        $close_match = (
            ($dist_c0_to_mean1 < $dist_c0_to_mean2 && $dist_c1_to_mean2 < $dist_c1_to_mean1) ||
            ($dist_c0_to_mean2 < $dist_c0_to_mean1 && $dist_c1_to_mean1 < $dist_c1_to_mean2)
        );
        $this->assertTrue($close_match, 'Centroids should align with cluster means');
    }

    /**
     * Test: Partition Coefficient (PC) lebih tinggi untuk cluster yang lebih jelas
     */
    public function test_partition_coefficient_increases_with_cluster_clarity()
    {
        $clear_clusters = [
            [1.0, 1.0, 1.0, 1.0],
            [1.0, 1.0, 1.0, 1.0],
            [4.0, 4.0, 4.0, 4.0],
            [4.0, 4.0, 4.0, 4.0],
        ];

        $fuzzy_data = [
            [1.0, 1.0, 1.0, 1.0],
            [2.0, 2.0, 2.0, 2.0],
            [3.0, 3.0, 3.0, 3.0],
            [4.0, 4.0, 4.0, 4.0],
        ];

        $result_clear = $this->fcm->fit($clear_clusters, 2);
        $result_fuzzy = $this->fcm->fit($fuzzy_data, 2);

        $pc_clear = $this->fcm->partitionCoefficient($result_clear['U']);
        $pc_fuzzy = $this->fcm->partitionCoefficient($result_fuzzy['U']);

        $this->assertGreaterThan($pc_fuzzy, $pc_clear,
            'PC should be higher for clearer clusters');
    }

    /**
     * Test: c >= n ditolak/dilewati
     */
    public function test_rejects_or_skips_c_greater_than_or_equal_to_n()
    {
        $data = [
            [1.0, 1.0, 1.0, 1.0],
            [2.0, 2.0, 2.0, 2.0],
            [3.0, 3.0, 3.0, 3.0],
        ];

        // Should throw on fit
        $this->expectException(\InvalidArgumentException::class);
        $this->fcm->fit($data, 3); // c=3, n=3 => should fail
    }

    /**
     * Test: pilihJumlahKlaster memilih c terbaik dari range
     */
    public function test_pilih_jumlah_klaster_selects_best_c()
    {
        $data = [
            [1.0, 1.0, 1.0, 1.0],
            [1.1, 1.0, 1.1, 1.0],
            [3.0, 3.0, 3.0, 3.0],
            [3.1, 3.0, 3.1, 3.0],
            [2.0, 2.0, 2.0, 2.0],
            [2.1, 2.0, 2.1, 2.0],
            [1.5, 1.5, 1.5, 1.5],
            [3.5, 3.5, 3.5, 3.5],
        ];

        $c_best = $this->fcm->pilihJumlahKlaster($data, 2, 4);

        $this->assertGreaterThanOrEqual(2, $c_best);
        $this->assertLessThanOrEqual(4, $c_best);
    }

    /**
     * Helper: assertAlmostEquals
     */
    private function assertAlmostEquals($expected, $actual, $decimals = 2, $message = '')
    {
        $factor = pow(10, $decimals);
        $this->assertEquals(
            round($expected * $factor),
            round($actual * $factor),
            $message
        );
    }
}
