<?php

namespace Tests\Unit;

use App\Services\FuzzyCMeans;
use App\Services\TiyasProfilKlaster;
use App\Services\TiyasScorer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TiyasPosisiPelatihanTest extends TestCase
{
    use RefreshDatabase;

    private TiyasProfilKlaster $service;

    protected function setUp(): void
    {
        parent::setUp();
        $scorer = app(TiyasScorer::class);
        $fcm = app(FuzzyCMeans::class);
        $this->service = new TiyasProfilKlaster($scorer, $fcm);
    }

    /**
     * Test posisiPelatihan returns empty array when no tabel_peserta.
     */
    public function test_posisiPelatihan_empty_when_no_peserta()
    {
        $analisis = ['tabel_peserta' => []];
        $comparisonData = [];

        $result = $this->service->posisiPelatihan($analisis, $comparisonData);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test posisiPelatihan calculates correct structure for single training.
     */
    public function test_posisiPelatihan_single_training_structure()
    {
        $analisis = [
            'tabel_peserta' => [
                [
                    'pelatihan_id' => 1,
                    'skor_raw' => [3.0, 2.5, 3.5, 2.0],
                    'memberships' => [1 => 0.8, 2 => 0.2],
                ],
                [
                    'pelatihan_id' => 1,
                    'skor_raw' => [2.0, 2.0, 2.0, 3.0],
                    'memberships' => [1 => 0.7, 2 => 0.3],
                ],
            ],
        ];
        $comparisonData = [
            1 => ['nama' => 'Training A'],
        ];

        $result = $this->service->posisiPelatihan($analisis, $comparisonData);

        $this->assertCount(1, $result);
        $training = $result[0];

        // Check structure
        $this->assertArrayHasKey('id', $training);
        $this->assertArrayHasKey('nama', $training);
        $this->assertArrayHasKey('n_peserta', $training);
        $this->assertArrayHasKey('centroid_pelatihan', $training);
        $this->assertArrayHasKey('rata_keanggotaan', $training);
        $this->assertArrayHasKey('klaster_terdekat', $training);

        // Check values
        $this->assertEquals(1, $training['id']);
        $this->assertEquals('Training A', $training['nama']);
        $this->assertEquals(2, $training['n_peserta']);
        
        // Centroid: (3.0+2.0)/2=2.5, (2.5+2.0)/2=2.25, (3.5+2.0)/2=2.75, (2.0+3.0)/2=2.5
        $this->assertEquals([2.5, 2.25, 2.75, 2.5], $training['centroid_pelatihan']);
        
        // Rata keanggotaan: klaster 1: (0.8+0.7)/2=0.75 -> rounded to 1 decimal = 0.8, klaster 2: (0.2+0.3)/2=0.25 -> 0.3
        $this->assertEquals(0.8, $training['rata_keanggotaan'][1]);
        $this->assertEquals(0.3, $training['rata_keanggotaan'][2]);
        
        // Klaster terdekat = argmax = 1
        $this->assertEquals(1, $training['klaster_terdekat']);
    }

    /**
     * Test posisiPelatihan with multiple trainings and different closest clusters.
     */
    public function test_posisiPelatihan_multiple_trainings_different_clusters()
    {
        $analisis = [
            'tabel_peserta' => [
                // Training 1
                [
                    'pelatihan_id' => 1,
                    'skor_raw' => [4.0, 4.0, 4.0, 4.0],
                    'memberships' => [1 => 0.9, 2 => 0.1],
                ],
                [
                    'pelatihan_id' => 1,
                    'skor_raw' => [4.0, 4.0, 4.0, 4.0],
                    'memberships' => [1 => 0.85, 2 => 0.15],
                ],
                // Training 2
                [
                    'pelatihan_id' => 2,
                    'skor_raw' => [1.0, 1.0, 1.0, 1.0],
                    'memberships' => [1 => 0.2, 2 => 0.8],
                ],
                [
                    'pelatihan_id' => 2,
                    'skor_raw' => [1.0, 1.0, 1.0, 1.0],
                    'memberships' => [1 => 0.15, 2 => 0.85],
                ],
            ],
        ];
        $comparisonData = [
            1 => ['nama' => 'Training A'],
            2 => ['nama' => 'Training B'],
        ];

        $result = $this->service->posisiPelatihan($analisis, $comparisonData);

        $this->assertCount(2, $result);

        // Training 1: klaster_terdekat = 1
        $this->assertEquals(1, $result[0]['id']);
        $this->assertEquals(1, $result[0]['klaster_terdekat']);
        
        // Training 2: klaster_terdekat = 2
        $this->assertEquals(2, $result[1]['id']);
        $this->assertEquals(2, $result[1]['klaster_terdekat']);
    }

    /**
     * Test posisiPelatihan rounding of rata_keanggotaan to 1 decimal place.
     */
    public function test_posisiPelatihan_membership_rounding()
    {
        $analisis = [
            'tabel_peserta' => [
                [
                    'pelatihan_id' => 1,
                    'skor_raw' => [2.0, 2.0, 2.0, 2.0],
                    'memberships' => [1 => 0.333, 2 => 0.667],
                ],
                [
                    'pelatihan_id' => 1,
                    'skor_raw' => [2.0, 2.0, 2.0, 2.0],
                    'memberships' => [1 => 0.333, 2 => 0.667],
                ],
                [
                    'pelatihan_id' => 1,
                    'skor_raw' => [2.0, 2.0, 2.0, 2.0],
                    'memberships' => [1 => 0.333, 2 => 0.667],
                ],
            ],
        ];
        $comparisonData = [
            1 => ['nama' => 'Training A'],
        ];

        $result = $this->service->posisiPelatihan($analisis, $comparisonData);

        $training = $result[0];
        
        // Rata keanggotaan: (0.333*3)/3 = 0.333, rounded to 1 decimal = 0.3
        $this->assertEquals(0.3, $training['rata_keanggotaan'][1]);
        $this->assertEquals(0.7, $training['rata_keanggotaan'][2]);
    }
}
