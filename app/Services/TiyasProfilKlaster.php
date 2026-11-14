<?php

namespace App\Services;

class TiyasProfilKlaster
{
    private $scorer;

    private $fcm;

    public function __construct(TiyasScorer $scorer, FuzzyCMeans $fcm)
    {
        $this->scorer = $scorer;
        $this->fcm = $fcm;
    }

    /**
     * Analisis FCM pada respons TIYAS.
     *
     * @param  array  $tiyasResponses
     * @return array Status, c_optimal, tabel_klaster, tabel_peserta, indeks validitas
     */
    public function analisis($tiyasResponses)
    {
        $n = count($tiyasResponses);
        if ($n < 8) {
            return [
                'status' => 'kurang_data',
                'c_optimal' => 0,
                'jumlah_sekarang' => $n,
                'jumlah_butuh' => 8,
                'tabel_klaster' => [],
                'tabel_peserta' => [],
                'validity_table' => [],
                'iteration' => 0,
                'fuzzy_partition_coefficient' => 0,
                'normalized_classification_entropy' => 0,
                'deskriptif' => $this->deskriptif($tiyasResponses),
                'pcaData' => null,
                'clusterExplanations' => [],
            ];
        }

        // Ekstrak data 4D dengan scoring dari jawaban
        $data = [];
        $names = [];
        $scores = [];
        foreach ($tiyasResponses as $resp) {
            $names[] = $resp->pendaftaran->peserta->name;
            $skor = $this->scorer->hitungSkor($resp->jawaban ?? []);
            $scores[] = $skor;
            $data[] = [
                floatval($skor['TC']['skor'] ?? 2.0),
                floatval($skor['TE']['skor'] ?? 2.0),
                floatval($skor['ITT']['skor'] ?? 2.0),
                floatval($skor['YB']['skor'] ?? 2.0),
            ];
        }

        // Pilih jumlah klaster optimal menggunakan FuzzyCMeans::pilihJumlahKlaster()
        $cMin = 2;
        $cMax = min(4, (int) ceil($n / 2));
        $bestC = $this->fcm->pilihJumlahKlaster($data, $cMin, $cMax);

        // Fit FCM dengan c optimal
        $bestResult = $this->fcm->fit($data, $bestC, $m = 2, $epsilon = 1e-5, $maxIter = 100);
        $U = $bestResult['U'];
        $V = $bestResult['centroid'];
        $iter = $bestResult['iterations'];

        // Bangun validity table dari semua c (cMin hingga cMax)
        $validityTable = [];
        for ($c = $cMin; $c <= $cMax; $c++) {
            $indices = $this->fcm->getValidityIndices($data, $c);
            $validityTable[] = [
                'c' => $c,
                'pc' => $indices['pc'],
                'pe' => $indices['pe'],
                'xb' => $indices['xb'],
                'silhouette' => $indices['silhouette'],
                'iteration' => ($c === $bestC) ? $iter : 0,
                'is_optimal' => ($c === $bestC),
            ];
        }

        // Bangun tabel klaster dengan raw centroid untuk PCA
        $tabelKlaster = [];
        $centroids_raw = [];
        for ($k = 0; $k < $bestC; $k++) {
            $centroid = $V[$k];
            $centroids_raw[] = $centroid;
            $profil = $this->determineProfil($centroid);
            $jumlahPeserta = 0;
            for ($i = 0; $i < $n; $i++) {
                if ($U[$i][$k] > 0.5) {
                    $jumlahPeserta++;
                }
            }

            $tabelKlaster[] = [
                'klaster' => $k + 1,
                'kode_profil' => $profil['code'],
                'profil' => $profil['label'],
                'karakteristik' => $profil['karakteristik'],
                'rekomendasi' => $profil['rekomendasi'],
                'jumlah_peserta' => $jumlahPeserta,
                'centroid' => [
                    'TC' => number_format($centroid[0], 2),
                    'TE' => number_format($centroid[1], 2),
                    'ITT' => number_format($centroid[2], 2),
                    'YB' => number_format($centroid[3], 2),
                ],
            ];
        }

        // Bangun tabel peserta
        $tabelPeserta = [];
        for ($i = 0; $i < $n; $i++) {
            $klasterDominan = 0;
            $maxU = 0;
            $memberships = [];
            for ($k = 0; $k < $bestC; $k++) {
                $memberships[$k + 1] = round($U[$i][$k] * 100, 1); // Convert to percentage
                if ($U[$i][$k] > $maxU) {
                    $maxU = $U[$i][$k];
                    $klasterDominan = $k + 1;
                }
            }

            $tabelPeserta[] = [
                'nama' => $names[$i],
                'klaster_dominan' => $klasterDominan,
                'memberships' => $memberships,
            ];
        }

        // Hitung PC dan PE untuk summary (dari best result)
        $result_best = $this->fcm->getValidityIndices($data, $bestC);

        return [
            'status' => 'sukses',
            'c_optimal' => $bestC,
            'tabel_klaster' => $tabelKlaster,
            'tabel_peserta' => $tabelPeserta,
            'validity_table' => $validityTable,
            'iteration' => $iter,
            'fuzzy_partition_coefficient' => $result_best['pc'],
            'normalized_classification_entropy' => $result_best['pe'],
            'centroids_raw' => $centroids_raw,
            'deskriptif' => $this->deskriptif($tiyasResponses),
            'pcaData' => $this->pcaProjection($data, $centroids_raw),
            'clusterExplanations' => $this->generateClusterExplanations($tabelKlaster, $tabelPeserta, $n),
        ];
    }

    /**
     * Determine profil (A/B/C/D/Belum cocok) from centroid using TiyasScorer::kategori().
     */
    private function determineProfil($centroid)
    {
        $tc = $centroid[0];
        $te = $centroid[1];
        $itt = $centroid[2];
        $yb = $centroid[3];

        // Kategorisasi setiap skor menggunakan TiyasScorer
        $kategoriTC = $this->scorer->kategori($tc);
        $kategoriTE = $this->scorer->kategori($te);
        $kategoriITT = $this->scorer->kategori($itt);
        $kategoriYB = $this->scorer->kategori($yb);

        $config = config('tiyas');
        $profilConfig = $config['profil_klaster'] ?? [];

        // Tentukan profil berdasarkan aturan kombinasi kategori
        // A: TC,TE,ITT,YB semuanya Tinggi
        if ($kategoriTC === 'Tinggi' && $kategoriTE === 'Tinggi' && $kategoriITT === 'Tinggi' && $kategoriYB === 'Tinggi') {
            $code = 'A';
        }
        // B: TC,TE,ITT Tinggi dan YB bukan Tinggi
        elseif ($kategoriTC === 'Tinggi' && $kategoriTE === 'Tinggi' && $kategoriITT === 'Tinggi' && $kategoriYB !== 'Tinggi') {
            $code = 'B';
        }
        // C: TC dan TE Rendah/Sedang, ITT Tinggi, YB Rendah/Sedang
        elseif (
            in_array($kategoriTC, ['Rendah', 'Sedang']) &&
            in_array($kategoriTE, ['Rendah', 'Sedang']) &&
            $kategoriITT === 'Tinggi' &&
            in_array($kategoriYB, ['Rendah', 'Sedang'])
        ) {
            $code = 'C';
        }
        // D: semuanya Rendah
        elseif ($kategoriTC === 'Rendah' && $kategoriTE === 'Rendah' && $kategoriITT === 'Rendah' && $kategoriYB === 'Rendah') {
            $code = 'D';
        } else {
            $code = null;
        }

        if ($code && isset($profilConfig[$code])) {
            $label = $profilConfig[$code]['label'] ?? $code;
            $karakteristik = $profilConfig[$code]['deskripsi'] ?? '';
            $rekomendasi = $profilConfig[$code]['rekomendasi'] ?? '';
        } else {
            $label = 'Belum cocok dengan 4 profil konseptual';
            $karakteristik = 'Pola data tidak sesuai dengan profil A-D yang didefinisikan.';
            $rekomendasi = 'Tinjau ulang kriteria profil atau data responsnya.';
        }

        return [
            'code' => $code,
            'label' => $label,
            'karakteristik' => $karakteristik,
            'rekomendasi' => $rekomendasi,
        ];
    }

    /**
     * Statistik deskriptif per konstruk.
     *
     * @param  array  $tiyasResponses
     * @return array ['TC' => [...], 'TE' => [...], ...] dengan mean, stdev, min, max, kategori
     */
    public function deskriptif($tiyasResponses)
    {
        $konstruk = ['TC' => [], 'TE' => [], 'ITT' => [], 'YB' => []];

        foreach ($tiyasResponses as $resp) {
            $skor = $this->scorer->hitungSkor($resp->jawaban ?? []);
            $konstruk['TC'][] = floatval($skor['TC']['skor'] ?? 2.0);
            $konstruk['TE'][] = floatval($skor['TE']['skor'] ?? 2.0);
            $konstruk['ITT'][] = floatval($skor['ITT']['skor'] ?? 2.0);
            $konstruk['YB'][] = floatval($skor['YB']['skor'] ?? 2.0);
        }

        $config = config('tiyas');
        $kategoriConfig = $config['kategori'] ?? [
            ['maks' => 2.00, 'label' => 'Rendah'],
            ['maks' => 3.00, 'label' => 'Sedang'],
            ['maks' => 4.00, 'label' => 'Tinggi'],
        ];

        $result = [];
        foreach ($konstruk as $name => $values) {
            if (empty($values)) {
                continue;
            }

            $n = count($values);
            $mean = array_sum($values) / $n;
            $variance = array_sum(array_map(fn ($v) => ($v - $mean) ** 2, $values)) / $n;
            $stdev = sqrt($variance);
            $min = min($values);
            $max = max($values);

            // Kategorisasi berdasarkan config
            $kategori = [];
            foreach ($kategoriConfig as $cat) {
                $label = $cat['label'];
                $kategori[$label] = ['count' => 0];
            }

            foreach ($values as $v) {
                $found = false;
                foreach ($kategoriConfig as $cat) {
                    if ($v <= $cat['maks']) {
                        $kategori[$cat['label']]['count']++;
                        $found = true;
                        break;
                    }
                }
            }

            // Hitung persen
            foreach ($kategori as &$k) {
                $k['percent'] = $n > 0 ? round($k['count'] / $n * 100) : 0;
            }

            $result[$name] = [
                'mean' => number_format($mean, 2),
                'stdev' => number_format($stdev, 2),
                'min' => number_format($min, 2),
                'max' => number_format($max, 2),
                'kategori' => $kategori,
            ];
        }

        return $result;
    }

    /**
     * PCA projection of data to 2D.
     *
     * @param  array  $data  nx4 matrix of scores
     * @param  array  $centroids  cx4 matrix of centroid
     * @return array ['loadings', 'variance', 'points', 'centroid_scores']
     */
    public function pcaProjection($data, $centroids)
    {
        if (empty($data)) {
            return ['loadings' => [], 'variance' => [0, 0], 'points' => [], 'centroid_scores' => []];
        }

        // Normalisasi dan center data
        $n = count($data);
        $d = count($data[0] ?? []);

        // Centroid data
        $mean = [];
        for ($j = 0; $j < $d; $j++) {
            $sum = 0;
            foreach ($data as $x) {
                $sum += $x[$j];
            }
            $mean[$j] = $sum / $n;
        }

        // Center data
        $X = [];
        for ($i = 0; $i < $n; $i++) {
            $X[$i] = [];
            for ($j = 0; $j < $d; $j++) {
                $X[$i][$j] = $data[$i][$j] - $mean[$j];
            }
        }

        // Covariance matrix
        $cov = array_fill(0, $d, array_fill(0, $d, 0));
        for ($i = 0; $i < $d; $i++) {
            for ($j = 0; $j < $d; $j++) {
                $sum = 0;
                foreach ($X as $x) {
                    $sum += $x[$i] * $x[$j];
                }
                $cov[$i][$j] = $sum / ($n - 1);
            }
        }

        // Eigen decomposition (simplified: power iteration for 2 largest)
        $eigenvalues = [];
        $eigenvectors = [];

        // First eigenvector: power iteration
        $v = array_fill(0, $d, 1.0 / sqrt($d));
        for ($iter = 0; $iter < 20; $iter++) {
            $Av = array_fill(0, $d, 0);
            for ($i = 0; $i < $d; $i++) {
                for ($j = 0; $j < $d; $j++) {
                    $Av[$i] += $cov[$i][$j] * $v[$j];
                }
            }
            $norm = sqrt(array_sum(array_map(fn ($x) => $x ** 2, $Av)));
            if ($norm > 0.0001) {
                $v = array_map(fn ($x) => $x / $norm, $Av);
            }
        }
        $eigenvectors[] = $v;
        $lambda1 = 0;
        foreach ($v as $vi) {
            for ($j = 0; $j < $d; $j++) {
                $lambda1 += $v[$j] * $cov[$j][$j];
            }
        }
        $lambda1 /= $d;
        $eigenvalues[] = max($lambda1, 0.01);

        // Second eigenvector: deflation
        $cov2 = [];
        for ($i = 0; $i < $d; $i++) {
            $cov2[$i] = [];
            for ($j = 0; $j < $d; $j++) {
                $cov2[$i][$j] = $cov[$i][$j] - $eigenvalues[0] * $v[$i] * $v[$j];
            }
        }
        $v2 = array_fill(0, $d, 1.0 / sqrt($d));
        for ($iter = 0; $iter < 20; $iter++) {
            $Av = array_fill(0, $d, 0);
            for ($i = 0; $i < $d; $i++) {
                for ($j = 0; $j < $d; $j++) {
                    $Av[$i] += $cov2[$i][$j] * $v2[$j];
                }
            }
            $norm = sqrt(array_sum(array_map(fn ($x) => $x ** 2, $Av)));
            if ($norm > 0.0001) {
                $v2 = array_map(fn ($x) => $x / $norm, $Av);
            }
        }
        $eigenvectors[] = $v2;
        $lambda2 = max(array_sum(array_map(fn ($x) => $x ** 2, $Av)), 0.01);
        $eigenvalues[] = $lambda2;

        // Loadings (first 2 eigenvectors)
        $loadings = [
            'PC1' => $eigenvectors[0],
            'PC2' => count($eigenvectors) > 1 ? $eigenvectors[1] : array_fill(0, $d, 0),
        ];

        // Variance explained
        $totalVar = array_sum($eigenvalues);
        $var1 = $totalVar > 0 ? round($eigenvalues[0] / $totalVar * 100) : 50;
        $var2 = $totalVar > 0 ? round($eigenvalues[1] / $totalVar * 100) : 50;

        // Project data
        $scores = [];
        foreach ($X as $x) {
            $pc1 = 0;
            $pc2 = 0;
            for ($j = 0; $j < $d; $j++) {
                $pc1 += $x[$j] * $loadings['PC1'][$j];
                $pc2 += $x[$j] * $loadings['PC2'][$j];
            }
            $scores[] = [$pc1, $pc2];
        }

        // Project centroids
        $centroid_scores = [];
        foreach ($centroids as $c) {
            $pc1 = 0;
            $pc2 = 0;
            for ($j = 0; $j < $d; $j++) {
                $pc1 += ($c[$j] - $mean[$j]) * $loadings['PC1'][$j];
                $pc2 += ($c[$j] - $mean[$j]) * $loadings['PC2'][$j];
            }
            $centroid_scores[] = [$pc1, $pc2];
        }

        return [
            'loadings' => [$eigenvectors[0], count($eigenvectors) > 1 ? $eigenvectors[1] : array_fill(0, $d, 0)],
            'variance' => [$var1, $var2],
            'points' => $scores,
            'centroid_scores' => $centroid_scores,
        ];
    }

    /**
     * Generate cluster explanations.
     *
     * @param  array  $tabelKlaster
     * @param  array  $tabelPeserta
     * @param  int  $totalPeserta
     * @return array Cluster explanations indexed by cluster number
     */
    public function generateClusterExplanations($tabelKlaster, $tabelPeserta, $totalPeserta)
    {
        $explanations = [];
        foreach ($tabelKlaster as $klaster) {
            $klasterNum = $klaster['klaster'];
            $profil = $klaster['profil'];
            $jumlah = $klaster['jumlah_peserta'];
            $persen = $totalPeserta > 0 ? round($jumlah / $totalPeserta * 100) : 0;

            $centroids = $klaster['centroid'];
            $tc = floatval(str_replace(',', '.', $centroids['TC']));
            $te = floatval(str_replace(',', '.', $centroids['TE']));
            $itt = floatval(str_replace(',', '.', $centroids['ITT']));
            $yb = floatval(str_replace(',', '.', $centroids['YB']));

            $text = "Klaster {$klasterNum} ({$jumlah} peserta, {$persen}%) berprofil {$profil}. "
                  .'Rerata TC '.number_format($tc, 2).', TE '.number_format($te, 2).', '
                  .'ITT '.number_format($itt, 2).', YB '.number_format($yb, 2).'.';

            $explanations[$klasterNum] = $text;
        }

        return $explanations;
    }
}
