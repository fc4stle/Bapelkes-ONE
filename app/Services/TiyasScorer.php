<?php

namespace App\Services;

class TiyasScorer
{
    /**
     * Hitung skor rata-rata per konstruk dan dimensi.
     * Input: array jawaban dengan key = kode butir (e.g., "TC1"), value = 1-4
     * Output: array dengan key = konstruk (TC, TE, ITT, YB), value = ['skor' => float, 'kategori' => string]
     */
    public function hitungSkor(array $jawaban): array
    {
        $config = config('tiyas');
        $skor = [];

        // Hitung rata-rata per konstruk
        foreach ($config['konstruk'] as $kodeKonstruk => $konstruk) {
            $butirs = [];

            // Ambil semua butir dalam konstruk ini
            foreach ($konstruk['dimensi'] as $dimensi) {
                foreach ($dimensi as $kode => $text) {
                    if (isset($jawaban[$kode])) {
                        $butirs[$kode] = (int) $jawaban[$kode];
                    }
                }
            }

            // Hitung rata-rata
            $validJawaban = array_filter($butirs, fn ($j) => $j !== null && $j > 0);
            $skor[$kodeKonstruk]['skor'] = count($validJawaban) > 0
                ? round(array_sum($validJawaban) / count($validJawaban), 2)
                : 0;
            $skor[$kodeKonstruk]['kategori'] = $this->kategori($skor[$kodeKonstruk]['skor']);
        }

        return $skor;
    }

    /**
     * Tentukan kategori skor berdasarkan batas di config('tiyas.kategori').
     */
    public function kategori(float $skor): string
    {
        $config = config('tiyas');

        foreach ($config['kategori'] as $kategori) {
            if ($skor <= $kategori['maks']) {
                return $kategori['label'];
            }
        }

        return 'Tinggi'; // Default jika > batas tertinggi
    }

    /**
     * Hitung skor per dimensi.
     * Input: array jawaban dengan key = kode butir (e.g., "TC1"), value = 1-4
     * Output: array dengan struktur [kodeKonstruk => [namaDimensi => skorRataRata (2 desimal)]
     */
    public function hitungSkorDimensi(array $jawaban): array
    {
        $config = config('tiyas');
        $skor = [];

        // Hitung rata-rata per dimensi untuk setiap konstruk
        foreach ($config['konstruk'] as $kodeKonstruk => $konstruk) {
            $skor[$kodeKonstruk] = [];

            foreach ($konstruk['dimensi'] as $namaDimensi => $butirs) {
                $nilai = [];

                // Kumpulkan jawaban untuk dimensi ini
                foreach ($butirs as $kode => $text) {
                    if (isset($jawaban[$kode])) {
                        $nilai[] = (int) $jawaban[$kode];
                    }
                }

                // Hitung rata-rata
                $skor[$kodeKonstruk][$namaDimensi] = count($nilai) > 0
                    ? round(array_sum($nilai) / count($nilai), 2)
                    : 0;
            }
        }

        return $skor;
    }

    /**
     * Interpretasi hasil TIYAS berdasarkan skor konstruk.
     * Input: array $skorKonstruk = ['TC' => [...], 'TE' => [...], 'ITT' => [...], 'YB' => [...]
     *        setiap item = ['skor' => float, 'kategori' => string]
     *
     * Aturan interpretasi:
     * a) YB Tinggi: "Hasil pelatihan sudah diterapkan di tempat kerja."
     * b) ITT Sedang/Tinggi dan YB Rendah: "Niat menerapkan sudah ada, tetapi perilaku belum muncul.
     *    Periksa dukungan tempat kerja (TC dan TE)."
     * c) TC atau TE Rendah: "Dukungan tempat kerja masih rendah. Perlu tindak lanjut dari
     *    atasan atau instansi."
     * d) Selain itu: "Penerapan hasil pelatihan perlu dipantau."
     */
    public function interpretasi(array $skorKonstruk): string
    {
        // a) YB Tinggi
        if (isset($skorKonstruk['YB']) && $skorKonstruk['YB']['kategori'] === 'Tinggi') {
            return 'Hasil pelatihan sudah diterapkan di tempat kerja.';
        }

        // b) ITT Sedang/Tinggi dan YB Rendah
        if (
            isset($skorKonstruk['ITT'], $skorKonstruk['YB']) &&
            in_array($skorKonstruk['ITT']['kategori'], ['Sedang', 'Tinggi']) &&
            $skorKonstruk['YB']['kategori'] === 'Rendah'
        ) {
            return 'Niat menerapkan sudah ada, tetapi perilaku belum muncul. Periksa dukungan tempat kerja (TC dan TE).';
        }

        // c) TC atau TE Rendah
        if (
            (isset($skorKonstruk['TC']) && $skorKonstruk['TC']['kategori'] === 'Rendah') ||
            (isset($skorKonstruk['TE']) && $skorKonstruk['TE']['kategori'] === 'Rendah')
        ) {
            return 'Dukungan tempat kerja masih rendah. Perlu tindak lanjut dari atasan atau instansi.';
        }

        // d) Default
        return 'Penerapan hasil pelatihan perlu dipantau.';
    }
}
