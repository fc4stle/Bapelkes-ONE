<?php

namespace Tests\Feature;

use App\Services\FuzzyCMeans;
use App\Services\TiyasProfilKlaster;
use App\Services\TiyasScorer;
use Tests\TestCase;

class TiyasKlasterPelatihanFeatureTest extends TestCase
{
    private TiyasProfilKlaster $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TiyasProfilKlaster(new TiyasScorer, new FuzzyCMeans);
    }

    /**
     * Test dengan 6 pelatihan dalam 2 kelompok jelas terpisah.
     * Diharapkan: c_optimal = 2, anggota tiap klaster sesuai kelompok.
     */
    public function test_klasterPelatihan_dengan_6_pelatihan_2_kelompok(): void
    {
        // Klaster 1: mean TC~3.6, TE~3.6, ITT~3.6, YB~3.6 (group tinggi)
        // Klaster 2: mean TC~1.8, TE~1.8, ITT~1.8, YB~1.8 (group rendah)
        $comparisonData = [
            1 => ['id' => 1, 'nama' => 'P1', 'mean' => ['TC' => 3.6, 'TE' => 3.6, 'ITT' => 3.6, 'YB' => 3.6]],
            2 => ['id' => 2, 'nama' => 'P2', 'mean' => ['TC' => 3.5, 'TE' => 3.5, 'ITT' => 3.5, 'YB' => 3.5]],
            3 => ['id' => 3, 'nama' => 'P3', 'mean' => ['TC' => 3.7, 'TE' => 3.7, 'ITT' => 3.7, 'YB' => 3.7]],
            4 => ['id' => 4, 'nama' => 'P4', 'mean' => ['TC' => 1.8, 'TE' => 1.8, 'ITT' => 1.8, 'YB' => 1.8]],
            5 => ['id' => 5, 'nama' => 'P5', 'mean' => ['TC' => 1.9, 'TE' => 1.9, 'ITT' => 1.9, 'YB' => 1.9]],
            6 => ['id' => 6, 'nama' => 'P6', 'mean' => ['TC' => 1.7, 'TE' => 1.7, 'ITT' => 1.7, 'YB' => 1.7]],
        ];

        $result = $this->service->klasterPelatihan($comparisonData);

        // Assert status sukses
        $this->assertSame('sukses', $result['status']);

        // Assert c_optimal = 2
        $this->assertSame(2, $result['c_optimal']);

        // Assert tabel_klaster memiliki 2 klaster
        $this->assertCount(2, $result['tabel_klaster']);

        // Assert tabel_pelatihan memiliki 6 pelatihan
        $this->assertCount(6, $result['tabel_pelatihan']);

        // Verifikasi struktur tabel_klaster
        foreach ($result['tabel_klaster'] as $klaster) {
            $this->assertArrayHasKey('klaster', $klaster);
            $this->assertArrayHasKey('kode_profil', $klaster);
            $this->assertArrayHasKey('profil', $klaster);
            $this->assertArrayHasKey('centroid', $klaster);
        }

        // Verifikasi struktur tabel_pelatihan
        foreach ($result['tabel_pelatihan'] as $pelatihan) {
            $this->assertArrayHasKey('id', $pelatihan);
            $this->assertArrayHasKey('nama', $pelatihan);
            $this->assertArrayHasKey('keanggotaan', $pelatihan); // 1-based
            $this->assertArrayHasKey('klaster_dominan', $pelatihan); // 1-based
            $this->assertIsArray($pelatihan['keanggotaan']);
        }

        // Assert anggota perkelompok (group 1 harus > 50% di satu klaster, group 2 di klaster lain)
        $group1_ids = [1, 2, 3];
        $group2_ids = [4, 5, 6];
        $klaster_per_pelatihan = [];
        foreach ($result['tabel_pelatihan'] as $pel) {
            $klaster_per_pelatihan[$pel['id']] = $pel['klaster_dominan'];
        }

        // Check group 1 dominan ke satu klaster
        $klaster_group1 = array_map(fn ($id) => $klaster_per_pelatihan[$id], $group1_ids);
        $this->assertSame(1, count(array_unique($klaster_group1)), 'Group 1 harus dominan ke satu klaster');

        // Check group 2 dominan ke klaster berbeda
        $klaster_group2 = array_map(fn ($id) => $klaster_per_pelatihan[$id], $group2_ids);
        $this->assertSame(1, count(array_unique($klaster_group2)), 'Group 2 harus dominan ke satu klaster');
        $this->assertNotEquals($klaster_group1[0], $klaster_group2[0], 'Group 1 dan 2 harus berbeda klaster');
    }

    /**
     * Test dengan 5 pelatihan (kurang dari minimum 6).
     * Diharapkan: status kurang_data, dengan jumlah sekarang = 5, ambang = 6.
     */
    public function test_klasterPelatihan_dengan_5_pelatihan_kurang_data(): void
    {
        $comparisonData = [
            1 => ['id' => 1, 'nama' => 'P1', 'mean' => ['TC' => 2.0, 'TE' => 2.0, 'ITT' => 2.0, 'YB' => 2.0]],
            2 => ['id' => 2, 'nama' => 'P2', 'mean' => ['TC' => 2.5, 'TE' => 2.5, 'ITT' => 2.5, 'YB' => 2.5]],
            3 => ['id' => 3, 'nama' => 'P3', 'mean' => ['TC' => 3.0, 'TE' => 3.0, 'ITT' => 3.0, 'YB' => 3.0]],
            4 => ['id' => 4, 'nama' => 'P4', 'mean' => ['TC' => 1.5, 'TE' => 1.5, 'ITT' => 1.5, 'YB' => 1.5]],
            5 => ['id' => 5, 'nama' => 'P5', 'mean' => ['TC' => 2.8, 'TE' => 2.8, 'ITT' => 2.8, 'YB' => 2.8]],
        ];

        $result = $this->service->klasterPelatihan($comparisonData);

        // Assert status kurang_data
        $this->assertSame('kurang_data', $result['status']);

        // Assert jumlah sekarang dan ambang
        $this->assertSame(5, $result['jumlah_sekarang']);
        $this->assertSame(6, $result['jumlah_butuh']);

        // Assert tabel kosong atau tidak ada
        $this->assertEmpty($result['tabel_klaster'] ?? []);
        $this->assertEmpty($result['tabel_pelatihan'] ?? []);
    }

    /**
     * Test dengan 7 pelatihan dalam 3 kelompok.
     * Diharapkan: c_optimal bisa 2 atau 3, anggota sesuai kelompok masing-masing.
     */
    public function test_klasterPelatihan_dengan_7_pelatihan_3_kelompok(): void
    {
        $comparisonData = [
            1 => ['id' => 1, 'nama' => 'P1', 'mean' => ['TC' => 3.5, 'TE' => 3.5, 'ITT' => 3.5, 'YB' => 3.5]],
            2 => ['id' => 2, 'nama' => 'P2', 'mean' => ['TC' => 3.6, 'TE' => 3.6, 'ITT' => 3.6, 'YB' => 3.6]],
            3 => ['id' => 3, 'nama' => 'P3', 'mean' => ['TC' => 2.5, 'TE' => 2.5, 'ITT' => 2.5, 'YB' => 2.5]],
            4 => ['id' => 4, 'nama' => 'P4', 'mean' => ['TC' => 2.4, 'TE' => 2.4, 'ITT' => 2.4, 'YB' => 2.4]],
            5 => ['id' => 5, 'nama' => 'P5', 'mean' => ['TC' => 1.5, 'TE' => 1.5, 'ITT' => 1.5, 'YB' => 1.5]],
            6 => ['id' => 6, 'nama' => 'P6', 'mean' => ['TC' => 1.6, 'TE' => 1.6, 'ITT' => 1.6, 'YB' => 1.6]],
            7 => ['id' => 7, 'nama' => 'P7', 'mean' => ['TC' => 1.4, 'TE' => 1.4, 'ITT' => 1.4, 'YB' => 1.4]],
        ];

        $result = $this->service->klasterPelatihan($comparisonData);

        // Assert status sukses
        $this->assertSame('sukses', $result['status']);

        // Assert c_optimal dalam range [2, 3] untuk 7 pelatihan
        $this->assertGreaterThanOrEqual(2, $result['c_optimal']);
        $this->assertLessThanOrEqual(3, $result['c_optimal']);

        // Assert tabel_pelatihan memiliki 7 pelatihan
        $this->assertCount(7, $result['tabel_pelatihan']);

        // Verifikasi klaster_dominan adalah angka positif 1-based
        foreach ($result['tabel_pelatihan'] as $pelatihan) {
            $this->assertGreaterThanOrEqual(1, $pelatihan['klaster_dominan']);
            $this->assertLessThanOrEqual($result['c_optimal'], $pelatihan['klaster_dominan']);
        }
    }

    /**
     * Test bahwa cMin < cMax dijamin (tidak throw exception untuk n=6 atau 7).
     */
    public function test_klasterPelatihan_parameter_cMin_cMax_valid(): void
    {
        // Test n=6: cMax = min(4, intdiv(6,2)) = min(4, 3) = 3, cMin=2 -> OK (2 < 3)
        $comparisonData = array_fill_keys(range(1, 6), ['id' => 0, 'nama' => 'P', 'mean' => ['TC' => 2.0, 'TE' => 2.0, 'ITT' => 2.0, 'YB' => 2.0]]);
        $result = $this->service->klasterPelatihan($comparisonData);
        $this->assertNotNull($result['c_optimal']);

        // Test n=7: cMax = min(4, intdiv(7,2)) = min(4, 3) = 3, cMin=2 -> OK (2 < 3)
        $comparisonData = array_fill_keys(range(1, 7), ['id' => 0, 'nama' => 'P', 'mean' => ['TC' => 2.0, 'TE' => 2.0, 'ITT' => 2.0, 'YB' => 2.0]]);
        $result = $this->service->klasterPelatihan($comparisonData);
        $this->assertNotNull($result['c_optimal']);
    }

    /**
     * Test validitas centroid dalam hasil.
     * Centroid harus memiliki 4 dimensi (TC, TE, ITT, YB) berupa float.
     */
    public function test_klasterPelatihan_centroid_valid(): void
    {
        $comparisonData = [
            1 => ['id' => 1, 'nama' => 'P1', 'mean' => ['TC' => 3.0, 'TE' => 3.0, 'ITT' => 3.0, 'YB' => 3.0]],
            2 => ['id' => 2, 'nama' => 'P2', 'mean' => ['TC' => 3.1, 'TE' => 3.1, 'ITT' => 3.1, 'YB' => 3.1]],
            3 => ['id' => 3, 'nama' => 'P3', 'mean' => ['TC' => 1.9, 'TE' => 1.9, 'ITT' => 1.9, 'YB' => 1.9]],
            4 => ['id' => 4, 'nama' => 'P4', 'mean' => ['TC' => 2.0, 'TE' => 2.0, 'ITT' => 2.0, 'YB' => 2.0]],
            5 => ['id' => 5, 'nama' => 'P5', 'mean' => ['TC' => 2.2, 'TE' => 2.2, 'ITT' => 2.2, 'YB' => 2.2]],
            6 => ['id' => 6, 'nama' => 'P6', 'mean' => ['TC' => 1.8, 'TE' => 1.8, 'ITT' => 1.8, 'YB' => 1.8]],
        ];

        $result = $this->service->klasterPelatihan($comparisonData);

        foreach ($result['tabel_klaster'] as $klaster) {
            $this->assertArrayHasKey('centroid', $klaster);
            $centroid = $klaster['centroid'];
            $this->assertArrayHasKey('TC', $centroid);
            $this->assertArrayHasKey('TE', $centroid);
            $this->assertArrayHasKey('ITT', $centroid);
            $this->assertArrayHasKey('YB', $centroid);
            // Centroid harus numeric
            $this->assertTrue(is_numeric($centroid['TC']));
            $this->assertTrue(is_numeric($centroid['TE']));
            $this->assertTrue(is_numeric($centroid['ITT']));
            $this->assertTrue(is_numeric($centroid['YB']));
        }
    }
}
