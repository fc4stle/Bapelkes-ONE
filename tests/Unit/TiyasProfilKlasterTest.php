<?php

namespace Tests\Unit;

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
        $scorer = app(TiyasScorer::class);
        $fcm = app(FuzzyCMeans::class);
        $this->service = new TiyasProfilKlaster($scorer, $fcm);
    }

    /**
     * Test deskriptif() method returns correct structure:
     * mean, SD, min, max, count, dan kategori percentages
     */
    public function test_deskriptif_returns_mean_sd_min_max_kategori()
    {
        // Build mock responses dengan jawaban
        $responses = [
            (object) ['jawaban' => $this->buildJawabanTinggi()],
            (object) ['jawaban' => $this->buildJawabanRendah()],
        ];

        $result = $this->service->deskriptif($responses);

        // Verifikasi struktur hasil
        $this->assertIsArray($result);
        $this->assertArrayHasKey('TC', $result);
        $this->assertArrayHasKey('TE', $result);
        $this->assertArrayHasKey('ITT', $result);
        $this->assertArrayHasKey('YB', $result);

        // Setiap konstruk harus punya: mean, stdev, min, max, kategori
        foreach (['TC', 'TE', 'ITT', 'YB'] as $konstruk) {
            $this->assertArrayHasKey('mean', $result[$konstruk], "Konstruk $konstruk harus punya mean");
            $this->assertArrayHasKey('stdev', $result[$konstruk], "Konstruk $konstruk harus punya stdev");
            $this->assertArrayHasKey('min', $result[$konstruk], "Konstruk $konstruk harus punya min");
            $this->assertArrayHasKey('max', $result[$konstruk], "Konstruk $konstruk harus punya max");
            $this->assertArrayHasKey('kategori', $result[$konstruk], "Konstruk $konstruk harus punya kategori");

            // Kategori harus punya Rendah, Sedang, Tinggi dengan count dan percent
            $kategori = $result[$konstruk]['kategori'];
            $this->assertArrayHasKey('Rendah', $kategori, "$konstruk harus punya Rendah");
            $this->assertArrayHasKey('Sedang', $kategori, "$konstruk harus punya Sedang");
            $this->assertArrayHasKey('Tinggi', $kategori, "$konstruk harus punya Tinggi");

            foreach (['Rendah', 'Sedang', 'Tinggi'] as $label) {
                $this->assertArrayHasKey('count', $kategori[$label], "$konstruk/$label harus punya count");
                $this->assertArrayHasKey('percent', $kategori[$label], "$konstruk/$label harus punya percent");
            }

            // Total percent harus = 100
            $totalPercent = $kategori['Rendah']['percent'] + $kategori['Sedang']['percent'] + $kategori['Tinggi']['percent'];
            $this->assertEquals(100, $totalPercent, "$konstruk total persen harus 100, got $totalPercent");
        }
    }

    /**
     * Helper: Buat jawaban dengan semua skor tinggi (4).
     */
    private function buildJawabanTinggi(): array
    {
        $jawaban = [];
        for ($i = 1; $i <= 15; $i++) {
            $jawaban["TC$i"] = 4;
            $jawaban["TE$i"] = 4;
            $jawaban["ITT$i"] = 4;
            $jawaban["YB$i"] = 4;
        }
        return $jawaban;
    }

    /**
     * Helper: Buat jawaban dengan semua skor rendah (1).
     */
    private function buildJawabanRendah(): array
    {
        $jawaban = [];
        for ($i = 1; $i <= 15; $i++) {
            $jawaban["TC$i"] = 1;
            $jawaban["TE$i"] = 1;
            $jawaban["ITT$i"] = 1;
            $jawaban["YB$i"] = 1;
        }
        return $jawaban;
    }
}
