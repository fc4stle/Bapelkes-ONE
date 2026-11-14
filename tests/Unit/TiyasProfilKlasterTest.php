<?php

namespace Tests\Unit;

use App\Services\TiyasProfilKlaster;
use PHPUnit\Framework\TestCase;

class TiyasProfilKlasterTest extends TestCase
{
    /**
     * Test posisiPelatihan dengan data kecil yang bisa dihitung tangan.
     *
     * Setup:
     * - 2 pelatihan: ID 1 (nama "Pelatihan A"), ID 2 (nama "Pelatihan B")
     * - 2 cluster
     * - Pelatihan 1: 2 peserta
     *   - Peserta 1A: skor_raw=[1.0, 2.0, 3.0, 4.0], memberships=[50%, 50%]
     *   - Peserta 1B: skor_raw=[3.0, 4.0, 1.0, 2.0], memberships=[30%, 70%]
     *   - Centroid: [(1+3)/2, (2+4)/2, (3+1)/2, (4+2)/2] = [2.0, 3.0, 2.0, 3.0]
     *   - Rata keanggotaan: [(50+30)/2, (50+70)/2] = [40%, 60%]
     *   - Klaster terdekat: 2 (argmax)
     *
     * - Pelatihan 2: 2 peserta
     *   - Peserta 2A: skor_raw=[2.0, 1.0, 4.0, 3.0], memberships=[70%, 30%]
     *   - Peserta 2B: skor_raw=[4.0, 3.0, 2.0, 1.0], memberships=[80%, 20%]
     *   - Centroid: [(2+4)/2, (1+3)/2, (4+2)/2, (3+1)/2] = [3.0, 2.0, 3.0, 2.0]
     *   - Rata keanggotaan: [(70+80)/2, (30+20)/2] = [75%, 25%]
     *   - Klaster terdekat: 1 (argmax)
     */
    public function test_posisi_pelatihan_dengan_dua_pelatihan()
    {
        $scorer = $this->createMock(\App\Services\TiyasScorer::class);
        $fcm = $this->createMock(\App\Services\FuzzyCMeans::class);
        $service = new TiyasProfilKlaster($scorer, $fcm);

        // Build test analisis data
        $analisis = [
            'tabel_peserta' => [
                // Pelatihan 1
                [
                    'nama' => 'Peserta 1A',
                    'skor_raw' => [1.0, 2.0, 3.0, 4.0],
                    'pelatihan_id' => 1,
                    'memberships' => [1 => 50.0, 2 => 50.0],
                    'klaster_dominan' => 1,
                ],
                [
                    'nama' => 'Peserta 1B',
                    'skor_raw' => [3.0, 4.0, 1.0, 2.0],
                    'pelatihan_id' => 1,
                    'memberships' => [1 => 30.0, 2 => 70.0],
                    'klaster_dominan' => 2,
                ],
                // Pelatihan 2
                [
                    'nama' => 'Peserta 2A',
                    'skor_raw' => [2.0, 1.0, 4.0, 3.0],
                    'pelatihan_id' => 2,
                    'memberships' => [1 => 70.0, 2 => 30.0],
                    'klaster_dominan' => 1,
                ],
                [
                    'nama' => 'Peserta 2B',
                    'skor_raw' => [4.0, 3.0, 2.0, 1.0],
                    'pelatihan_id' => 2,
                    'memberships' => [1 => 80.0, 2 => 20.0],
                    'klaster_dominan' => 1,
                ],
            ],
        ];

        $comparisonData = [
            1 => ['id' => 1, 'nama' => 'Pelatihan A'],
            2 => ['id' => 2, 'nama' => 'Pelatihan B'],
        ];

        $result = $service->posisiPelatihan($analisis, $comparisonData);

        // Assert result count
        $this->assertCount(2, $result);

        // Pelatihan 1 (ID 1)
        $p1 = $result[0];
        $this->assertEquals(1, $p1['id']);
        $this->assertEquals('Pelatihan A', $p1['nama']);
        $this->assertEquals(2, $p1['n_peserta']);

        // Centroid Pelatihan 1: [2.0, 3.0, 2.0, 3.0]
        $this->assertEqualsWithDelta([2.0, 3.0, 2.0, 3.0], $p1['centroid_pelatihan'], 0.01);

        // Rata keanggotaan: [40.0, 60.0]
        $this->assertEqualsWithDelta([1 => 40.0, 2 => 60.0], $p1['rata_keanggotaan'], 0.1);

        // Klaster terdekat: 2
        $this->assertEquals(2, $p1['klaster_terdekat']);

        // Pelatihan 2 (ID 2)
        $p2 = $result[1];
        $this->assertEquals(2, $p2['id']);
        $this->assertEquals('Pelatihan B', $p2['nama']);
        $this->assertEquals(2, $p2['n_peserta']);

        // Centroid Pelatihan 2: [3.0, 2.0, 3.0, 2.0]
        $this->assertEqualsWithDelta([3.0, 2.0, 3.0, 2.0], $p2['centroid_pelatihan'], 0.01);

        // Rata keanggotaan: [75.0, 25.0]
        $this->assertEqualsWithDelta([1 => 75.0, 2 => 25.0], $p2['rata_keanggotaan'], 0.1);

        // Klaster terdekat: 1
        $this->assertEquals(1, $p2['klaster_terdekat']);
    }

    /**
     * Test posisiPelatihan dengan single peserta (n=1)
     */
    public function test_posisi_pelatihan_single_peserta()
    {
        $scorer = $this->createMock(\App\Services\TiyasScorer::class);
        $fcm = $this->createMock(\App\Services\FuzzyCMeans::class);
        $service = new TiyasProfilKlaster($scorer, $fcm);

        $analisis = [
            'tabel_peserta' => [
                [
                    'nama' => 'Peserta Solo',
                    'skor_raw' => [2.5, 3.5, 1.5, 4.0],
                    'pelatihan_id' => 5,
                    'memberships' => [1 => 20.0, 2 => 35.0, 3 => 45.0],
                    'klaster_dominan' => 3,
                ],
            ],
        ];

        $comparisonData = [
            5 => ['id' => 5, 'nama' => 'Pelatihan Solo'],
        ];

        $result = $service->posisiPelatihan($analisis, $comparisonData);

        $this->assertCount(1, $result);
        $p = $result[0];

        // Centroid should equal single peserta's skor_raw
        $this->assertEqualsWithDelta([2.5, 3.5, 1.5, 4.0], $p['centroid_pelatihan'], 0.01);

        // Rata keanggotaan should equal single peserta's memberships
        $this->assertEqualsWithDelta([1 => 20.0, 2 => 35.0, 3 => 45.0], $p['rata_keanggotaan'], 0.1);

        // Klaster terdekat should be 3 (max)
        $this->assertEquals(3, $p['klaster_terdekat']);
    }

    /**
     * Test posisiPelatihan dengan empty tabel_peserta
     */
    public function test_posisi_pelatihan_empty_data()
    {
        $scorer = $this->createMock(\App\Services\TiyasScorer::class);
        $fcm = $this->createMock(\App\Services\FuzzyCMeans::class);
        $service = new TiyasProfilKlaster($scorer, $fcm);

        $analisis = ['tabel_peserta' => []];
        $comparisonData = [];

        $result = $service->posisiPelatihan($analisis, $comparisonData);

        $this->assertEmpty($result);
    }

    /**
     * Test posisiPelatihan dengan 3 cluster (1-based indexing)
     */
    public function test_posisi_pelatihan_tiga_cluster()
    {
        $scorer = $this->createMock(\App\Services\TiyasScorer::class);
        $fcm = $this->createMock(\App\Services\FuzzyCMeans::class);
        $service = new TiyasProfilKlaster($scorer, $fcm);

        $analisis = [
            'tabel_peserta' => [
                [
                    'nama' => 'P1',
                    'skor_raw' => [1.0, 1.0, 1.0, 1.0],
                    'pelatihan_id' => 10,
                    'memberships' => [1 => 10.0, 2 => 20.0, 3 => 70.0],
                    'klaster_dominan' => 3,
                ],
                [
                    'nama' => 'P2',
                    'skor_raw' => [2.0, 2.0, 2.0, 2.0],
                    'pelatihan_id' => 10,
                    'memberships' => [1 => 15.0, 2 => 25.0, 3 => 60.0],
                    'klaster_dominan' => 3,
                ],
            ],
        ];

        $comparisonData = [
            10 => ['id' => 10, 'nama' => 'Pelatihan X'],
        ];

        $result = $service->posisiPelatihan($analisis, $comparisonData);

        $this->assertCount(1, $result);
        $p = $result[0];

        // Centroid
        $this->assertEqualsWithDelta([1.5, 1.5, 1.5, 1.5], $p['centroid_pelatihan'], 0.01);

        // Rata keanggotaan (1-based): (10+15)/2, (20+25)/2, (70+60)/2
        $this->assertEqualsWithDelta([1 => 12.5, 2 => 22.5, 3 => 65.0], $p['rata_keanggotaan'], 0.1);

        // Klaster terdekat: 3
        $this->assertEquals(3, $p['klaster_terdekat']);
    }
}
