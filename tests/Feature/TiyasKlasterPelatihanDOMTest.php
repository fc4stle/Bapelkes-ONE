<?php

namespace Tests\Feature;

use App\Services\FuzzyCMeans;
use App\Services\TiyasProfilKlaster;
use App\Services\TiyasScorer;
use Tests\TestCase;
use DOMDocument;

class TiyasKlasterPelatihanDOMTest extends TestCase
{
    private TiyasProfilKlaster $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TiyasProfilKlaster(new TiyasScorer, new FuzzyCMeans);
    }

    /**
     * Test service output struktur dengan 7 pelatihan dalam 3 kluster.
     * Validasi: jumlah pelatihan = 7, struktur data lengkap, membership sum ~100.
     */
    public function test_service_7_training_3_clusters_structure(): void
    {
        $comparisonData = [
            1 => ['id' => 1, 'nama' => 'Pelatihan Dasar Keterampilan', 'mean' => ['TC' => 3.5, 'TE' => 3.5, 'ITT' => 3.5, 'YB' => 3.5]],
            2 => ['id' => 2, 'nama' => 'Pelatihan Lanjut Teknis', 'mean' => ['TC' => 3.6, 'TE' => 3.6, 'ITT' => 3.6, 'YB' => 3.6]],
            3 => ['id' => 3, 'nama' => 'Pelatihan Menengah Manajemen', 'mean' => ['TC' => 2.5, 'TE' => 2.5, 'ITT' => 2.5, 'YB' => 2.5]],
            4 => ['id' => 4, 'nama' => 'Pelatihan Dasar Komunikasi', 'mean' => ['TC' => 2.4, 'TE' => 2.4, 'ITT' => 2.4, 'YB' => 2.4]],
            5 => ['id' => 5, 'nama' => 'Pelatihan Pemula Informatika', 'mean' => ['TC' => 1.5, 'TE' => 1.5, 'ITT' => 1.5, 'YB' => 1.5]],
            6 => ['id' => 6, 'nama' => 'Pelatihan Dasar IT Support', 'mean' => ['TC' => 1.6, 'TE' => 1.6, 'ITT' => 1.6, 'YB' => 1.6]],
            7 => ['id' => 7, 'nama' => 'Pelatihan Fondasi Teknologi', 'mean' => ['TC' => 1.4, 'TE' => 1.4, 'ITT' => 1.4, 'YB' => 1.4]],
        ];

        $result = $this->service->klasterPelatihan($comparisonData);

        // Validasi status dan jumlah pelatihan
        $this->assertSame('sukses', $result['status']);
        $this->assertCount(7, $result['tabel_pelatihan']);

        // Validasi: setiap pelatihan memiliki struktur lengkap
        foreach ($result['tabel_pelatihan'] as $pelatihan) {
            $this->assertArrayHasKey('id', $pelatihan);
            $this->assertArrayHasKey('nama', $pelatihan);
            $this->assertArrayHasKey('keanggotaan', $pelatihan);
            $this->assertArrayHasKey('klaster_dominan', $pelatihan);

            // Keanggotaan harus array 1-based dan sum ~100
            $this->assertIsArray($pelatihan['keanggotaan']);
            $sum = array_sum($pelatihan['keanggotaan']);
            $this->assertGreaterThan(99.5, $sum, 'Membership sum must be ~100');
            $this->assertLessThan(100.5, $sum);

            // Klaster dominan harus valid angka 1-based
            $this->assertGreaterThanOrEqual(1, $pelatihan['klaster_dominan']);
            $this->assertLessThanOrEqual($result['c_optimal'], $pelatihan['klaster_dominan']);
        }

        // Validasi centroid ada dan valid
        $this->assertCount($result['c_optimal'], $result['tabel_klaster']);
        foreach ($result['tabel_klaster'] as $klaster) {
            $this->assertArrayHasKey('centroid', $klaster);
            $centroid = $klaster['centroid'];
            $this->assertTrue(is_finite($centroid['TC']));
            $this->assertTrue(is_finite($centroid['TE']));
            $this->assertTrue(is_finite($centroid['ITT']));
            $this->assertTrue(is_finite($centroid['YB']));
        }
    }

    /**
     * Test dengan 5 pelatihan: status kurang_data, pesan benar.
     */
    public function test_service_5_training_kurang_data(): void
    {
        $comparisonData = [
            1 => ['id' => 1, 'nama' => 'P1', 'mean' => ['TC' => 2.0, 'TE' => 2.0, 'ITT' => 2.0, 'YB' => 2.0]],
            2 => ['id' => 2, 'nama' => 'P2', 'mean' => ['TC' => 2.5, 'TE' => 2.5, 'ITT' => 2.5, 'YB' => 2.5]],
            3 => ['id' => 3, 'nama' => 'P3', 'mean' => ['TC' => 3.0, 'TE' => 3.0, 'ITT' => 3.0, 'YB' => 3.0]],
            4 => ['id' => 4, 'nama' => 'P4', 'mean' => ['TC' => 1.5, 'TE' => 1.5, 'ITT' => 1.5, 'YB' => 1.5]],
            5 => ['id' => 5, 'nama' => 'P5', 'mean' => ['TC' => 2.8, 'TE' => 2.8, 'ITT' => 2.8, 'YB' => 2.8]],
        ];

        $result = $this->service->klasterPelatihan($comparisonData);

        $this->assertSame('kurang_data', $result['status']);
        $this->assertSame(5, $result['jumlah_sekarang']);
        $this->assertSame(6, $result['jumlah_butuh']);
        $this->assertEmpty($result['tabel_klaster'] ?? []);
        $this->assertEmpty($result['tabel_pelatihan'] ?? []);
    }

    /**
     * Test dengan 1 pelatihan: kurang_data.
     */
    public function test_service_1_training_kurang_data(): void
    {
        $comparisonData = [
            1 => ['id' => 1, 'nama' => 'P1', 'mean' => ['TC' => 2.0, 'TE' => 2.0, 'ITT' => 2.0, 'YB' => 2.0]],
        ];

        $result = $this->service->klasterPelatihan($comparisonData);

        $this->assertSame('kurang_data', $result['status']);
        $this->assertSame(1, $result['jumlah_sekarang']);
        $this->assertSame(6, $result['jumlah_butuh']);
    }

    /**
     * Test membership values sum to ~100 per training.
     */
    public function test_membership_sum_100_per_training(): void
    {
        $comparisonData = [
            1 => ['id' => 1, 'nama' => 'P1', 'mean' => ['TC' => 3.5, 'TE' => 3.5, 'ITT' => 3.5, 'YB' => 3.5]],
            2 => ['id' => 2, 'nama' => 'P2', 'mean' => ['TC' => 3.6, 'TE' => 3.6, 'ITT' => 3.6, 'YB' => 3.6]],
            3 => ['id' => 3, 'nama' => 'P3', 'mean' => ['TC' => 2.5, 'TE' => 2.5, 'ITT' => 2.5, 'YB' => 2.5]],
            4 => ['id' => 4, 'nama' => 'P4', 'mean' => ['TC' => 2.4, 'TE' => 2.4, 'ITT' => 2.4, 'YB' => 2.4]],
            5 => ['id' => 5, 'nama' => 'P5', 'mean' => ['TC' => 1.5, 'TE' => 1.5, 'ITT' => 1.5, 'YB' => 1.5]],
            6 => ['id' => 6, 'nama' => 'P6', 'mean' => ['TC' => 1.6, 'TE' => 1.6, 'ITT' => 1.6, 'YB' => 1.6]],
        ];

        $result = $this->service->klasterPelatihan($comparisonData);

        foreach ($result['tabel_pelatihan'] as $pelatihan) {
            $sum = array_sum($pelatihan['keanggotaan']);
            $this->assertGreaterThan(99.8, $sum, 'Sum must be >= 99.8');
            $this->assertLessThan(100.2, $sum, 'Sum must be <= 100.2');
        }
    }

    /**
     * Test cluster dominance = argmax(membership).
     */
    public function test_dominance_is_argmax(): void
    {
        $comparisonData = [
            1 => ['id' => 1, 'nama' => 'P1', 'mean' => ['TC' => 3.5, 'TE' => 3.5, 'ITT' => 3.5, 'YB' => 3.5]],
            2 => ['id' => 2, 'nama' => 'P2', 'mean' => ['TC' => 3.6, 'TE' => 3.6, 'ITT' => 3.6, 'YB' => 3.6]],
            3 => ['id' => 3, 'nama' => 'P3', 'mean' => ['TC' => 1.5, 'TE' => 1.5, 'ITT' => 1.5, 'YB' => 1.5]],
            4 => ['id' => 4, 'nama' => 'P4', 'mean' => ['TC' => 1.6, 'TE' => 1.6, 'ITT' => 1.6, 'YB' => 1.6]],
            5 => ['id' => 5, 'nama' => 'P5', 'mean' => ['TC' => 2.5, 'TE' => 2.5, 'ITT' => 2.5, 'YB' => 2.5]],
            6 => ['id' => 6, 'nama' => 'P6', 'mean' => ['TC' => 2.4, 'TE' => 2.4, 'ITT' => 2.4, 'YB' => 2.4]],
        ];

        $result = $this->service->klasterPelatihan($comparisonData);

        foreach ($result['tabel_pelatihan'] as $pelatihan) {
            $keanggotaan = $pelatihan['keanggotaan'];
            $maxCluster = (int) array_search(max($keanggotaan), $keanggotaan);
            $this->assertSame($maxCluster, $pelatihan['klaster_dominan']);
        }
    }

    /**
     * Test HTML rendering: view partial dengan mock variables.
     * Render hanya section FCM, bukan full page.
     */
    public function test_html_render_fcm_section_7_training(): void
    {
        $comparisonData = [
            1 => ['id' => 1, 'nama' => 'Pelatihan Dasar Keterampilan', 'mean' => ['TC' => 3.5, 'TE' => 3.5, 'ITT' => 3.5, 'YB' => 3.5]],
            2 => ['id' => 2, 'nama' => 'Pelatihan Lanjut Teknis', 'mean' => ['TC' => 3.6, 'TE' => 3.6, 'ITT' => 3.6, 'YB' => 3.6]],
            3 => ['id' => 3, 'nama' => 'Pelatihan Menengah Manajemen', 'mean' => ['TC' => 2.5, 'TE' => 2.5, 'ITT' => 2.5, 'YB' => 2.5]],
            4 => ['id' => 4, 'nama' => 'Pelatihan Dasar Komunikasi', 'mean' => ['TC' => 2.4, 'TE' => 2.4, 'ITT' => 2.4, 'YB' => 2.4]],
            5 => ['id' => 5, 'nama' => 'Pelatihan Pemula Informatika', 'mean' => ['TC' => 1.5, 'TE' => 1.5, 'ITT' => 1.5, 'YB' => 1.5]],
            6 => ['id' => 6, 'nama' => 'Pelatihan Dasar IT Support', 'mean' => ['TC' => 1.6, 'TE' => 1.6, 'ITT' => 1.6, 'YB' => 1.6]],
            7 => ['id' => 7, 'nama' => 'Pelatihan Fondasi Teknologi', 'mean' => ['TC' => 1.4, 'TE' => 1.4, 'ITT' => 1.4, 'YB' => 1.4]],
        ];

        $result = $this->service->klasterPelatihan($comparisonData);

        // Render view fragment untuk FCM section
        $html = view('components.fcm-pelatihan-section', [
            'klasterPelatihan' => $result,
            'cakupan' => 'semua',
        ])->render();

        // Check presence of key elements
        $this->assertStringContainsString('Pengelompokan Pelatihan (FCM antar Pelatihan)', $html);
        $this->assertStringContainsString('data-chart="fcm-pelatihan-scatter"', $html);
        $this->assertStringContainsString('data-chart="fcm-pelatihan-membership"', $html);

        // Check pelatihan names present
        $this->assertStringContainsString('Pelatihan Dasar Keterampilan', $html);
        $this->assertStringContainsString('Pelatihan Fondasi Teknologi', $html);

        // Check P1, P2, ..., P7 labels
        for ($i = 1; $i <= 7; $i++) {
            $this->assertStringContainsString('P' . $i, $html, 'Label P' . $i . ' must be present');
        }

        // Check Klaster labels
        for ($i = 1; $i <= $result['c_optimal']; $i++) {
            $this->assertStringContainsString('Klaster ' . $i, $html);
        }
    }

    /**
     * Test HTML rendering: kurang_data message with 5 trainings.
     */
    public function test_html_render_kurang_data_message(): void
    {
        $comparisonData = [
            1 => ['id' => 1, 'nama' => 'P1', 'mean' => ['TC' => 2.0, 'TE' => 2.0, 'ITT' => 2.0, 'YB' => 2.0]],
            2 => ['id' => 2, 'nama' => 'P2', 'mean' => ['TC' => 2.5, 'TE' => 2.5, 'ITT' => 2.5, 'YB' => 2.5]],
            3 => ['id' => 3, 'nama' => 'P3', 'mean' => ['TC' => 3.0, 'TE' => 3.0, 'ITT' => 3.0, 'YB' => 3.0]],
            4 => ['id' => 4, 'nama' => 'P4', 'mean' => ['TC' => 1.5, 'TE' => 1.5, 'ITT' => 1.5, 'YB' => 1.5]],
            5 => ['id' => 5, 'nama' => 'P5', 'mean' => ['TC' => 2.8, 'TE' => 2.8, 'ITT' => 2.8, 'YB' => 2.8]],
        ];

        $result = $this->service->klasterPelatihan($comparisonData);

        $html = view('components.fcm-pelatihan-section', [
            'klasterPelatihan' => $result,
            'cakupan' => 'semua',
        ])->render();

        $this->assertStringContainsString('Belum cukup pelatihan untuk pengelompokan', $html);
        $this->assertStringContainsString('butuh minimal 6', $html);
        $this->assertStringContainsString('saat ini 5', $html);

        // Charts should NOT be present
        $this->assertStringNotContainsString('data-chart="fcm-pelatihan-scatter"', $html);
        $this->assertStringNotContainsString('data-chart="fcm-pelatihan-membership"', $html);
    }

    /**
     * Test HTML loadXML: no corrupt attributes.
     */
    public function test_html_loadxml_valid(): void
    {
        $comparisonData = [
            1 => ['id' => 1, 'nama' => 'P1', 'mean' => ['TC' => 3.5, 'TE' => 3.5, 'ITT' => 3.5, 'YB' => 3.5]],
            2 => ['id' => 2, 'nama' => 'P2', 'mean' => ['TC' => 3.6, 'TE' => 3.6, 'ITT' => 3.6, 'YB' => 3.6]],
            3 => ['id' => 3, 'nama' => 'P3', 'mean' => ['TC' => 1.5, 'TE' => 1.5, 'ITT' => 1.5, 'YB' => 1.5]],
            4 => ['id' => 4, 'nama' => 'P4', 'mean' => ['TC' => 1.6, 'TE' => 1.6, 'ITT' => 1.6, 'YB' => 1.6]],
            5 => ['id' => 5, 'nama' => 'P5', 'mean' => ['TC' => 2.5, 'TE' => 2.5, 'ITT' => 2.5, 'YB' => 2.5]],
            6 => ['id' => 6, 'nama' => 'P6', 'mean' => ['TC' => 2.4, 'TE' => 2.4, 'ITT' => 2.4, 'YB' => 2.4]],
        ];

        $result = $this->service->klasterPelatihan($comparisonData);

        $html = view('components.fcm-pelatihan-section', [
            'klasterPelatihan' => $result,
            'cakupan' => 'semua',
        ])->render();

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML($html);
        libxml_use_internal_errors(false);

        $this->assertTrue($loaded, 'HTML must be loadable with loadHTML');
    }
}
