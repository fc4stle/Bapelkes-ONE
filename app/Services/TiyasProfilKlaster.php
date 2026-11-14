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

        // FCM clustering
        $cMax = min(5, (int) ceil($n / 2));
        $validityTable = [];
        $bestC = 2;
        $bestXB = PHP_FLOAT_MAX;
        $bestResult = null;

        for ($c = 2; $c <= $cMax; $c++) {
            $result = $this->fcm->fit($data, $c, $m = 2, $epsilon = 1e-5, $maxIter = 100);
            $U = $result['U'];
            $V = $result['centroid'];
            $iter = $result['iterations'];

            // Hitung validity indices
            $pc = $this->computePC($U);
            $pe = $this->computePE($U);
            $xb = $this->computeXB($data, $U, $V);
            $silhouette = $this->computeSilhouette($data, $U);

            if ($xb < $bestXB) {
                $bestXB = $xb;
                $bestC = $c;
                $bestResult = $result;
            }

            $validityTable[] = [
                'c' => $c,
                'pc' => $pc,
                'pe' => $pe,
                'xb' => $xb,
                'silhouette' => $silhouette,
                'iteration' => $iter,
            ];
        }

        // Mark optimal in validity table
        foreach ($validityTable as &$row) {
            $row['is_optimal'] = ($row['c'] === $bestC);
        }

        // Ensure bestResult is set (should always be set if loop ran)
        if ($bestResult === null) {
            // Fallback: run clustering with c=2
            $bestResult = $this->fcm->fit($data, 2, $m = 2, $epsilon = 1e-5, $maxIter = 100);
            $bestC = 2;
        }

        $U = $bestResult['U'];
        $V = $bestResult['centroid'];
        $iter = $bestResult['iterations'];

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

        // Hitung PC dan PE untuk summary
        $pc = $this->computePC($U);
        $pe = $this->computePE($U);

        return [
            'status' => 'sukses',
            'c_optimal' => $bestC,
            'tabel_klaster' => $tabelKlaster,
            'tabel_peserta' => $tabelPeserta,
            'validity_table' => $validityTable,
            'iteration' => $iter,
            'fuzzy_partition_coefficient' => $pc,
            'normalized_classification_entropy' => $pe,
            'centroids_raw' => $centroids_raw,
        ];
    }

    /**
     * Compute Partition Coefficient.
     */
    private function computePC($U)
    {
        $n = count($U);
        $c = count($U[0]);
        $sum = 0;
        for ($i = 0; $i < $n; $i++) {
            for ($k = 0; $k < $c; $k++) {
                $sum += $U[$i][$k] ** 2;
            }
        }

        return $sum / $n;
    }

    /**
     * Compute Partition Entropy.
     */
    private function computePE($U)
    {
        $n = count($U);
        $c = count($U[0]);
        $sum = 0;
        for ($i = 0; $i < $n; $i++) {
            for ($k = 0; $k < $c; $k++) {
                $uik = $U[$i][$k];
                if ($uik > 0.0001) {
                    $sum += $uik * log($uik);
                }
            }
        }

        return -$sum / $n / log($c);
    }

    /**
     * Compute Xie-Beni index.
     */
    private function computeXB($data, $U, $V)
    {
        $n = count($data);
        $c = count($V);
        $m = 2;

        // Numerator: sum of weighted distances
        $num = 0;
        for ($i = 0; $i < $n; $i++) {
            for ($k = 0; $k < $c; $k++) {
                $dist = $this->euclideanDistance($data[$i], $V[$k]);
                $num += ($U[$i][$k] ** $m) * ($dist ** 2);
            }
        }

        // Denominator: minimum distance between centroids
        $minDist = PHP_FLOAT_MAX;
        for ($k1 = 0; $k1 < $c; $k1++) {
            for ($k2 = $k1 + 1; $k2 < $c; $k2++) {
                $dist = $this->euclideanDistance($V[$k1], $V[$k2]);
                if ($dist < $minDist) {
                    $minDist = $dist;
                }
            }
        }

        if ($minDist < 0.0001) {
            return PHP_FLOAT_MAX;
        }

        return $num / ($n * ($minDist ** 2));
    }

    /**
     * Compute Silhouette coefficient.
     */
    private function computeSilhouette($data, $U)
    {
        $n = count($data);
        $c = count($U[0]);
        $silhouettes = [];

        for ($i = 0; $i < $n; $i++) {
            // Cluster assignment
            $maxU = max($U[$i]);
            $ci = array_search($maxU, $U[$i]);

            // Average distance to cluster members
            $a = 0;
            $count_ci = 0;
            for ($j = 0; $j < $n; $j++) {
                if ($U[$j][$ci] > 0.5) {
                    $a += $this->euclideanDistance($data[$i], $data[$j]);
                    $count_ci++;
                }
            }
            if ($count_ci > 1) {
                $a /= ($count_ci - 1);
            } else {
                $a = 0;
            }

            // Minimum average distance to other clusters
            $b = PHP_FLOAT_MAX;
            for ($k = 0; $k < $c; $k++) {
                if ($k === $ci) {
                    continue;
                }
                $dist = 0;
                $count_k = 0;
                for ($j = 0; $j < $n; $j++) {
                    if ($U[$j][$k] > 0.5) {
                        $dist += $this->euclideanDistance($data[$i], $data[$j]);
                        $count_k++;
                    }
                }
                if ($count_k > 0) {
                    $dist /= $count_k;
                    if ($dist < $b) {
                        $b = $dist;
                    }
                }
            }

            $sil = 0;
            if (max($a, $b) > 0) {
                $sil = ($b - $a) / max($a, $b);
            }
            $silhouettes[] = $sil;
        }

        return count($silhouettes) > 0 ? array_sum($silhouettes) / count($silhouettes) : 0;
    }

    /**
     * Euclidean distance between two vectors.
     */
    private function euclideanDistance($v1, $v2)
    {
        $sum = 0;
        for ($i = 0; $i < count($v1); $i++) {
            $sum += ($v1[$i] - $v2[$i]) ** 2;
        }

        return sqrt($sum);
    }

    /**
     * Determine profil (A/B/C/D/"Belum cocok") from centroid.
     */
    private function determineProfil($centroid)
    {
        $tc = $centroid[0];
        $te = $centroid[1];
        $itt = $centroid[2];
        $yb = $centroid[3];

        $config = config('tiyas');
        $profilConfig = $config['profil_klaster'] ?? [];

        // Match profile based on centroid pattern
        if ($tc >= 3.5 && $te >= 3.5 && $itt >= 3.5 && $yb >= 3.5) {
            $code = 'A';
        } elseif ($tc >= 3.0 && $te >= 3.0 && $itt >= 3.0 && $yb >= 2.5) {
            $code = 'B';
        } elseif ($itt >= 3.5 && $tc < 3.0 && $te < 3.0) {
            $code = 'C';
        } elseif ($tc <= 2.0 && $te <= 2.0 && $itt <= 2.0) {
            $code = 'D';
        } else {
            $code = null;
        }

        if ($code && isset($profilConfig[$code])) {
            $label = $profilConfig[$code]['label'] ?? $code;
            $karakteristik = $profilConfig[$code]['deskripsi'] ?? '';
            $rekomendasi = $profilConfig[$code]['rekomendasi'] ?? '';
        } else {
            $label = 'Belum cocok dengan profil';
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
     * @return array ['loadings', 'variance', 'scores', 'centroid_scores']
     */
    public function pcaProjection($data, $centroids)
    {
        if (empty($data)) {
            return ['loadings' => [], 'variance' => [0, 0], 'scores' => [], 'centroid_scores' => []];
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
