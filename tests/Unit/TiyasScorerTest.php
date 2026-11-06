<?php

namespace Tests\Unit;

use App\Services\TiyasScorer;
use Tests\TestCase;

class TiyasScorerTest extends TestCase
{
    /**
     * Test: hitungSkorDimensi dengan semua jawaban 4 -> semua dimensi 4.00
     */
    public function test_hitung_skor_dimensi_semua_empat()
    {
        $scorer = new TiyasScorer;

        // Ambil config untuk tahu struktur butir
        $config = config('tiyas');
        $jawaban = [];

        // Isi semua butir dengan nilai 4
        foreach ($config['konstruk'] as $kodeKonstruk => $konstruk) {
            foreach ($konstruk['dimensi'] as $namaDimensi => $butirs) {
                foreach ($butirs as $kode => $text) {
                    $jawaban[$kode] = 4;
                }
            }
        }

        $skor = $scorer->hitungSkorDimensi($jawaban);

        // Verifikasi setiap dimensi di setiap konstruk = 4.00
        foreach ($skor as $kodeKonstruk => $dimensis) {
            foreach ($dimensis as $namaDimensi => $nilai) {
                $this->assertEquals(4.00, $nilai, "Dimensi {$namaDimensi} di {$kodeKonstruk} harus 4.00");
            }
        }
    }

    /**
     * Test: hitungSkorDimensi dengan jawaban campuran
     */
    public function test_hitung_skor_dimensi_campuran()
    {
        $scorer = new TiyasScorer;

        // Data manual: buat jawaban tertentu dan verifikasi hasilnya
        $config = config('tiyas');

        // Ambil konstruk pertama
        $kodeKonstrukPertama = array_key_first($config['konstruk']);
        $konstrukPertama = $config['konstruk'][$kodeKonstrukPertama];

        $jawaban = [];

        // Isi konstruk pertama dengan nilai campuran (1,2,3,4)
        $nilaiCampuran = [1, 2, 3, 4];
        $indexNilai = 0;

        foreach ($konstrukPertama['dimensi'] as $namaDimensi => $butirs) {
            foreach ($butirs as $kode => $text) {
                $jawaban[$kode] = $nilaiCampuran[$indexNilai % 4];
                $indexNilai++;
            }
        }

        // Isi konstruk lain dengan nilai default (null/tidak ada)
        foreach ($config['konstruk'] as $kodeKonstruk => $konstruk) {
            if ($kodeKonstruk === $kodeKonstrukPertama) {
                continue;
            }
            foreach ($konstruk['dimensi'] as $namaDimensi => $butirs) {
                foreach ($butirs as $kode => $text) {
                    // Tidak ada jawaban untuk konstruk lainnya
                }
            }
        }

        $skor = $scorer->hitungSkorDimensi($jawaban);

        // Verifikasi hasil perhitungan untuk konstruk pertama
        // Jika ada 4 butir dengan nilai [1,2,3,4], rata-rata = 2.50
        // Jika ada 3 butir dengan nilai [1,2,3], rata-rata = 2.00
        $this->assertIsArray($skor);
        $this->assertArrayHasKey($kodeKonstrukPertama, $skor);

        // Semua nilai harus numeric dan <= 4
        foreach ($skor[$kodeKonstrukPertama] as $nilai) {
            $this->assertIsFloat($nilai);
            $this->assertGreaterThanOrEqual(0, $nilai);
            $this->assertLessThanOrEqual(4, $nilai);
        }
    }

    /**
     * Test: hitungSkorDimensi dengan tidak ada jawaban
     */
    public function test_hitung_skor_dimensi_tidak_ada_jawaban()
    {
        $scorer = new TiyasScorer;

        $jawaban = [];
        $skor = $scorer->hitungSkorDimensi($jawaban);

        // Semua dimensi harus 0
        foreach ($skor as $kodeKonstruk => $dimensis) {
            foreach ($dimensis as $nilai) {
                $this->assertEquals(0, $nilai);
            }
        }
    }
}
