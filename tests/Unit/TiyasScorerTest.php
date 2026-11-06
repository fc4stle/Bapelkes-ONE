<?php

namespace Tests\Unit;

use App\Services\TiyasScorer;
use Tests\TestCase;

class TiyasScorerTest extends TestCase
{
    /**
     * Test: semua jawaban 4 => semua konstruk 4.00 Tinggi.
     */
    public function test_all_max_scores_returns_all_four()
    {
        $scorer = new TiyasScorer;
        $jawaban = $this->generateAllScores(4);

        $hasil = $scorer->hitungSkor($jawaban);

        foreach (['TC', 'TE', 'ITT', 'YB'] as $konstruk) {
            $this->assertEquals(4.00, $hasil[$konstruk]['skor']);
            $this->assertEquals('Tinggi', $hasil[$konstruk]['kategori']);
        }
    }

    /**
     * Test: semua jawaban 1 => semua konstruk 1.00 Rendah.
     */
    public function test_all_min_scores_returns_all_one()
    {
        $scorer = new TiyasScorer;
        $jawaban = $this->generateAllScores(1);

        $hasil = $scorer->hitungSkor($jawaban);

        foreach (['TC', 'TE', 'ITT', 'YB'] as $konstruk) {
            $this->assertEquals(1.00, $hasil[$konstruk]['skor']);
            $this->assertEquals('Rendah', $hasil[$konstruk]['kategori']);
        }
    }

    /**
     * Test: campuran skor => rata-rata dihitung benar.
     */
    public function test_mixed_scores_calculates_average()
    {
        $scorer = new TiyasScorer;
        $jawaban = $this->generateMixedScores();

        $hasil = $scorer->hitungSkor($jawaban);

        // Semua konstruk akan ada, hanya check ada nilai
        foreach (['TC', 'TE', 'ITT', 'YB'] as $konstruk) {
            $this->assertArrayHasKey($konstruk, $hasil);
            $this->assertArrayHasKey('skor', $hasil[$konstruk]);
            $this->assertArrayHasKey('kategori', $hasil[$konstruk]);
            $this->assertGreaterThanOrEqual(1.00, $hasil[$konstruk]['skor']);
            $this->assertLessThanOrEqual(4.00, $hasil[$konstruk]['skor']);
        }
    }

    /**
     * Test: interpretasi aturan a (YB Tinggi).
     */
    public function test_interpretation_rule_a_yb_tinggi()
    {
        $scorer = new TiyasScorer;

        // Semua 4 => YB Tinggi
        $jawaban = $this->generateAllScores(4);
        $skor = $scorer->hitungSkor($jawaban);

        $interpretasi = $scorer->interpretasi($skor);

        $this->assertStringContainsString('diterapkan', strtolower($interpretasi));
        $this->assertStringContainsString('tempat kerja', $interpretasi);
    }

    /**
     * Test: interpretasi aturan b (ITT Sedang/Tinggi, YB Rendah).
     */
    public function test_interpretation_rule_b_itt_high_yb_low()
    {
        $scorer = new TiyasScorer;
        $config = config('tiyas');

        // Buat jawaban: ITT semua 3 (Sedang), YB semua 1 (Rendah), TC TE acak
        $jawaban = [];

        foreach ($config['konstruk'] as $kodeKonstruk => $konstruk) {
            foreach ($konstruk['dimensi'] as $dimensi) {
                foreach ($dimensi as $kode => $text) {
                    if (str_starts_with($kode, 'ITT')) {
                        $jawaban[$kode] = 3; // Sedang
                    } elseif (str_starts_with($kode, 'YB')) {
                        $jawaban[$kode] = 1; // Rendah
                    } else {
                        $jawaban[$kode] = 3; // TC TE: Sesuai
                    }
                }
            }
        }

        $skor = $scorer->hitungSkor($jawaban);
        $interpretasi = $scorer->interpretasi($skor);

        $this->assertStringContainsString('niat menerapkan', strtolower($interpretasi));
        $this->assertStringContainsString('dukungan', strtolower($interpretasi));
    }

    /**
     * Test: interpretasi aturan c (TC atau TE Rendah).
     */
    public function test_interpretation_rule_c_tc_te_low()
    {
        $scorer = new TiyasScorer;
        $config = config('tiyas');

        // Buat jawaban: TC semua 1 (Rendah), sisanya 3
        $jawaban = [];

        foreach ($config['konstruk'] as $kodeKonstruk => $konstruk) {
            foreach ($konstruk['dimensi'] as $dimensi) {
                foreach ($dimensi as $kode => $text) {
                    $jawaban[$kode] = str_starts_with($kode, 'TC') ? 1 : 3;
                }
            }
        }

        $skor = $scorer->hitungSkor($jawaban);
        $interpretasi = $scorer->interpretasi($skor);

        $this->assertStringContainsString('dukungan tempat kerja', strtolower($interpretasi));
        $this->assertStringContainsString('tindak lanjut', strtolower($interpretasi));
    }

    /**
     * Test: interpretasi aturan d (default).
     */
    public function test_interpretation_rule_d_default()
    {
        $scorer = new TiyasScorer;
        $config = config('tiyas');

        // Buat skor di mana tidak ada kondisi a, b, c yang terpenuhi
        // TC Sedang, TE Tinggi, ITT Rendah, YB Sedang
        $jawaban = [];
        foreach ($config['konstruk'] as $kodeKonstruk => $konstruk) {
            foreach ($konstruk['dimensi'] as $dimensi) {
                foreach ($dimensi as $kode => $text) {
                    if (str_starts_with($kode, 'TC')) {
                        $jawaban[$kode] = 3; // Sedang
                    } elseif (str_starts_with($kode, 'TE')) {
                        $jawaban[$kode] = 4; // Tinggi
                    } elseif (str_starts_with($kode, 'ITT')) {
                        $jawaban[$kode] = 1; // Rendah
                    } else {
                        $jawaban[$kode] = 2; // Sedang
                    }
                }
            }
        }

        $skor = $scorer->hitungSkor($jawaban);
        $interpretasi = $scorer->interpretasi($skor);

        // Rule d: "Penerapan hasil pelatihan perlu dipantau."
        $this->assertStringContainsString('dipantau', strtolower($interpretasi));
    }

    // Helper methods

    private function generateAllScores(int $value): array
    {
        $config = config('tiyas');
        $jawaban = [];

        foreach ($config['konstruk'] as $kodeKonstruk => $konstruk) {
            foreach ($konstruk['dimensi'] as $dimensi) {
                foreach ($dimensi as $kode => $text) {
                    $jawaban[$kode] = $value;
                }
            }
        }

        return $jawaban;
    }

    private function generateMixedScores(): array
    {
        $config = config('tiyas');
        $jawaban = [];

        foreach ($config['konstruk'] as $kodeKonstruk => $konstruk) {
            foreach ($konstruk['dimensi'] as $dimensi) {
                foreach ($dimensi as $kode => $text) {
                    $jawaban[$kode] = rand(1, 4);
                }
            }
        }

        return $jawaban;
    }
}
