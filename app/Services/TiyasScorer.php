<?php

namespace App\Services;

class TiyasScorer
{
    /**
     * Hitung skor rata-rata per konstruk dan dimensi.
     * Input: array jawaban dengan key = kode butir (e.g., "TC1"), value = 1-4
     * Output: array skor dengan struktur:
     *   [
     *     'TC' => ['skor' => 3.50, 'kategori' => 'Tinggi'],
     *     'TE' => ['skor' => 2.75, 'kategori' => 'Sedang'],
     *     ...
     *   ]
     */
    public function hitungSkor(array $jawaban): array
    {
        $config = config('tiyas');
        $skorKonstruk = [];

        // Kelompokkan jawaban per konstruk (TC, TE, ITT, YB)
        foreach ($config['bagian'] as $bagian) {
            $kode_bagian = $bagian['kode'];
            foreach ($bagian['dimensi'] as $dimensi) {
                foreach ($dimensi['butir'] as $butir) {
                    $kode_butir = $butir['kode'];
                    $konstruk = substr($kode_butir, 0, -1); // Ambil prefix (TC, TE, ITT, YB)

                    if (!isset($skorKonstruk[$konstruk])) {
                        $skorKonstruk[$konstruk] = [];
                    }

                    if (isset($jawaban[$kode_butir])) {
                        $skorKonstruk[$konstruk][] = (int) $jawaban[$kode_butir];
                    }
                }
            }
        }

        // Hitung rata-rata per konstruk dan tentukan kategori
        $hasil = [];
        foreach ($skorKonstruk as $konstruk => $nilai) {
            if (empty($nilai)) {
                $hasil[$konstruk] = ['skor' => 0.0, 'kategori' => 'Tidak Ada Data'];
            } else {
                $skor = round(array_sum($nilai) / count($nilai), 2);
                $kategori = $this->tentukanKategori($skor, $config);
                $hasil[$konstruk] = ['skor' => $skor, 'kategori' => $kategori];
            }
        }

        return $hasil;
    }

    /**
     * Tentukan kategori berdasarkan skor dan config tiyas.kategori.
     */
    private function tentukanKategori(float $skor, array $config): string
    {
        $kategori = $config['kategori'];

        foreach ($kategori as $cat) {
            if ($skor >= $cat['min'] && $skor <= $cat['max']) {
                return $cat['label'];
            }
        }

        return 'Tidak Terdefinisi';
    }

    /**
     * Interpretasi hasil berdasarkan skor konstruk.
     * Aturan:
     * a) YB Tinggi: "Hasil pelatihan sudah diterapkan di tempat kerja."
     * b) ITT Sedang/Tinggi dan YB Rendah: "Niat menerapkan sudah ada, tetapi perilaku belum muncul..."
     * c) TC atau TE Rendah: "Dukungan tempat kerja masih rendah..."
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

        // d) Selain itu
        return 'Penerapan hasil pelatihan perlu dipantau.';
    }
}
