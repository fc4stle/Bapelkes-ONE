<?php

namespace App\Services;

/**
 * Fuzzy C-Means (FCM) clustering algorithm - pure PHP implementation.
 *
 * Deterministic initialization (no randomness) ensures reproducible results.
 * Partition Coefficient (PC), Partition Entropy (PE), Xie-Beni (XB),
 * and Silhouette validity indices guide cluster selection.
 */
class FuzzyCMeans
{
    /**
     * Fit FCM model to data.
     *
     * @param  array  $data  Array of vectors (each row = [TC, TE, ITT, YB])
     * @param  int  $c  Number of clusters
     * @param  float  $m  Fuzziness exponent (default 2.0)
     * @param  float  $epsilon  Convergence threshold (default 1e-5)
     * @param  int  $maxIter  Maximum iterations (default 200)
     * @return array ['centroid' => array, 'U' => array, 'iterations' => int]
     */
    public function fit(
        array $data,
        int $c,
        float $m = 2.0,
        float $epsilon = 1e-5,
        int $maxIter = 200
    ): array {
        $n = count($data);

        // Validate
        if ($c >= $n) {
            throw new \InvalidArgumentException("Clusters (c=$c) must be < data points (n=$n)");
        }
        if ($c < 2) {
            throw new \InvalidArgumentException("Clusters (c=$c) must be >= 2");
        }
        if (empty($data)) {
            throw new \InvalidArgumentException('Data is empty');
        }

        // Deterministic initialization: spread centroids across data range
        $centroid = $this->initializeCentroidsDeterministic($data, $c);

        $U = null; // Membership matrix
        $iteration = 0;

        for ($iter = 0; $iter < $maxIter; $iter++) {
            $iteration = $iter;

            // Step 1: Update membership matrix U
            $U_new = $this->updateMembership($data, $centroid, $m);

            // Step 2: Update centroids
            $centroid_new = $this->updateCentroid($data, $U_new, $m);

            // Step 3: Check convergence
            $diff = $this->matrixDifference($centroid, $centroid_new);
            $centroid = $centroid_new;
            $U = $U_new;

            if ($diff < $epsilon) {
                break;
            }
        }

        return [
            'centroid' => $centroid,
            'U' => $U,
            'iterations' => $iteration + 1,
        ];
    }

    /**
     * Initialize centroids deterministically (no randomness).
     * Spread them across data range by selecting min, max, and intermediate points.
     */
    private function initializeCentroidsDeterministic(array $data, int $c): array
    {
        $n = count($data);
        $d = count($data[0] ?? []); // Number of dimensions

        if ($d === 0) {
            throw new \InvalidArgumentException('Data vectors are empty');
        }

        // Sort data indices by sum of all dimensions (deterministic)
        $indices = range(0, $n - 1);
        usort($indices, function ($i, $j) use ($data) {
            $sum_i = array_sum($data[$i]);
            $sum_j = array_sum($data[$j]);

            return $sum_i - $sum_j;
        });

        // Select c centroids spread across sorted data
        $centroid = [];
        for ($i = 0; $i < $c; $i++) {
            $idx = intval($i * ($n - 1) / ($c - 1));
            $centroid[] = $data[$indices[$idx]];
        }

        return $centroid;
    }

    /**
     * Update membership matrix U.
     * U[i][j] = degree to which data point i belongs to cluster j.
     * Each row sums to 1.
     */
    private function updateMembership(array $data, array $centroid, float $m): array
    {
        $n = count($data);
        $c = count($centroid);
        $U = [];

        for ($i = 0; $i < $n; $i++) {
            $U[$i] = [];
            $distances = [];

            // Calculate distance from point i to all centroids
            for ($j = 0; $j < $c; $j++) {
                $distances[$j] = $this->euclideanDistance($data[$i], $centroid[$j]);
            }

            // Calculate membership: U[i][j] = 1 / (sum_k (d_ij / d_ik) ^ (2/(m-1)))
            for ($j = 0; $j < $c; $j++) {
                $d_ij = $distances[$j];

                // Avoid division by zero
                if ($d_ij < 1e-10) {
                    $U[$i][$j] = 1.0;
                } else {
                    $sum = 0;
                    for ($k = 0; $k < $c; $k++) {
                        $d_ik = $distances[$k];
                        if ($d_ik < 1e-10) {
                            $sum += 1e10; // Very large number
                        } else {
                            $sum += pow($d_ij / $d_ik, 2 / ($m - 1));
                        }
                    }
                    $U[$i][$j] = 1 / $sum;
                }
            }

            // Normalize row to sum to 1
            $row_sum = array_sum($U[$i]);
            if ($row_sum > 0) {
                for ($j = 0; $j < $c; $j++) {
                    $U[$i][$j] /= $row_sum;
                }
            }
        }

        return $U;
    }

    /**
     * Update centroid positions.
     * centroid[j] = sum(U[i][j]^m * data[i]) / sum(U[i][j]^m)
     */
    private function updateCentroid(array $data, array $U, float $m): array
    {
        $n = count($data);
        $c = count($U[0] ?? []);
        $d = count($data[0] ?? []);
        $centroid = [];

        for ($j = 0; $j < $c; $j++) {
            $numerator = array_fill(0, $d, 0);
            $denominator = 0;

            for ($i = 0; $i < $n; $i++) {
                $weight = pow($U[$i][$j], $m);
                $denominator += $weight;

                for ($dim = 0; $dim < $d; $dim++) {
                    $numerator[$dim] += $weight * $data[$i][$dim];
                }
            }

            // Calculate centroid
            if ($denominator > 0) {
                for ($dim = 0; $dim < $d; $dim++) {
                    $numerator[$dim] /= $denominator;
                }
            }
            $centroid[] = $numerator;
        }

        return $centroid;
    }

    /**
     * Calculate Euclidean distance between two vectors.
     */
    private function euclideanDistance(array $v1, array $v2): float
    {
        $sum = 0;
        for ($i = 0; $i < count($v1); $i++) {
            $diff = $v1[$i] - ($v2[$i] ?? 0);
            $sum += $diff * $diff;
        }

        return sqrt($sum);
    }

    /**
     * Calculate L2 norm difference between two centroid matrices.
     */
    private function matrixDifference(array $old, array $new): float
    {
        $sum = 0;
        for ($i = 0; $i < count($old); $i++) {
            for ($j = 0; $j < count($old[$i] ?? []); $j++) {
                $diff = ($old[$i][$j] ?? 0) - ($new[$i][$j] ?? 0);
                $sum += $diff * $diff;
            }
        }

        return sqrt($sum);
    }

    /**
     * Calculate Partition Coefficient (PC). Higher is better (range 0-1).
     */
    public function partitionCoefficient(array $U): float
    {
        $n = count($U);
        $c = count($U[0] ?? []);

        if ($n === 0 || $c === 0) {
            return 0;
        }

        $sum = 0;
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $c; $j++) {
                $uij = $U[$i][$j] ?? 0;
                $sum += $uij * $uij;
            }
        }

        return $sum / $n;
    }

    /**
     * Calculate Partition Entropy (PE). Lower is better.
     */
    public function partitionEntropy(array $U): float
    {
        $n = count($U);
        $c = count($U[0] ?? []);

        if ($n === 0 || $c === 0) {
            return 0;
        }

        $sum = 0;
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $c; $j++) {
                $uij = $U[$i][$j] ?? 0;
                if ($uij > 0) {
                    $sum += $uij * log($uij);
                }
            }
        }

        return -$sum / $n;
    }

    /**
     * Calculate Xie-Beni index (XB). Lower is better.
     */
    public function xieBeni(array $data, array $centroid, array $U): float
    {
        $n = count($data);
        $c = count($centroid);

        if ($n === 0 || $c === 0) {
            return PHP_FLOAT_MAX;
        }

        // Numerator: sum of squared distances weighted by membership
        $numerator = 0;
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $c; $j++) {
                $uij = $U[$i][$j] ?? 0;
                $dist = $this->euclideanDistance($data[$i], $centroid[$j]);
                $numerator += $uij * $uij * $dist * $dist;
            }
        }

        // Denominator: minimum distance between centroids
        $minDist = PHP_FLOAT_MAX;
        for ($i = 0; $i < $c; $i++) {
            for ($j = $i + 1; $j < $c; $j++) {
                $dist = $this->euclideanDistance($centroid[$i], $centroid[$j]);
                $minDist = min($minDist, $dist);
            }
        }

        if ($minDist < 1e-10) {
            return PHP_FLOAT_MAX;
        }

        return $numerator / ($n * $minDist * $minDist);
    }

    /**
     * Calculate Silhouette index. Higher is better (range -1 to 1).
     * Hard assignment: assign each point to cluster with highest membership.
     */
    public function silhouette(array $data, array $centroid, array $U): float
    {
        $n = count($data);

        if ($n <= 1) {
            return 0;
        }

        // Hard assignment: assign to cluster with max membership
        $assignments = [];
        for ($i = 0; $i < $n; $i++) {
            $max_uij = 0;
            $max_j = 0;
            for ($j = 0; $j < count($U[$i]); $j++) {
                if (($U[$i][$j] ?? 0) > $max_uij) {
                    $max_uij = $U[$i][$j];
                    $max_j = $j;
                }
            }
            $assignments[$i] = $max_j;
        }

        // Calculate silhouette for each point
        $silhouettes = [];
        for ($i = 0; $i < $n; $i++) {
            $cluster = $assignments[$i];

            // a(i): average distance to points in same cluster
            $same_cluster = [];
            for ($k = 0; $k < $n; $k++) {
                if ($k !== $i && $assignments[$k] === $cluster) {
                    $same_cluster[] = $this->euclideanDistance($data[$i], $data[$k]);
                }
            }
            $a_i = count($same_cluster) > 0 ? array_sum($same_cluster) / count($same_cluster) : 0;

            // b(i): minimum average distance to other clusters
            $b_i = PHP_FLOAT_MAX;
            for ($c = 0; $c < count($centroid); $c++) {
                if ($c === $cluster) {
                    continue;
                }
                $other_cluster = [];
                for ($k = 0; $k < $n; $k++) {
                    if ($assignments[$k] === $c) {
                        $other_cluster[] = $this->euclideanDistance($data[$i], $data[$k]);
                    }
                }
                if (count($other_cluster) > 0) {
                    $avg = array_sum($other_cluster) / count($other_cluster);
                    $b_i = min($b_i, $avg);
                }
            }

            // silhouette(i) = (b(i) - a(i)) / max(a(i), b(i))
            if ($b_i === PHP_FLOAT_MAX) {
                $silhouettes[] = 0;
            } else {
                $max_ab = max($a_i, $b_i);
                $silhouettes[] = $max_ab > 0 ? ($b_i - $a_i) / $max_ab : 0;
            }
        }

        return count($silhouettes) > 0 ? array_sum($silhouettes) / count($silhouettes) : 0;
    }

    /**
     * Choose optimal number of clusters based on validity indices.
     *
     * @param  array  $data  Array of vectors
     * @param  int  $cMin  Minimum clusters (default 2)
     * @param  int  $cMax  Maximum clusters (default 4)
     * @return int Optimal number of clusters
     */
    public function pilihJumlahKlaster(
        array $data,
        int $cMin = 2,
        int $cMax = 4
    ): int {
        $n = count($data);

        // Validate
        if ($cMax >= $n) {
            $cMax = $n - 1;
        }
        if ($cMin >= $cMax) {
            throw new \InvalidArgumentException('cMin must be < cMax');
        }

        $rankings = []; // c => total_rank

        // Test each c
        for ($c = $cMin; $c <= $cMax; $c++) {
            $result = $this->fit($data, $c);
            $centroid = $result['centroid'];
            $U = $result['U'];

            // Calculate indices
            $pc = $this->partitionCoefficient($U);
            $pe = $this->partitionEntropy($U);
            $xb = $this->xieBeni($data, $centroid, $U);
            $silhouette = $this->silhouette($data, $centroid, $U);

            // Rank this c
            // PC: higher is better
            // PE: lower is better
            // XB: lower is better
            // Silhouette: higher is better
            $rankings[$c] = [
                'pc' => $pc,
                'pe' => $pe,
                'xb' => $xb,
                'silhouette' => $silhouette,
                'rank' => 0,
            ];
        }

        // Calculate ranking for each index
        $c_list = array_keys($rankings);

        // PC ranking: higher is better
        usort($c_list, fn ($a, $b) => $rankings[$b]['pc'] <=> $rankings[$a]['pc']);
        for ($rank = 0; $rank < count($c_list); $rank++) {
            $rankings[$c_list[$rank]]['rank'] += $rank;
        }

        // PE ranking: lower is better
        usort($c_list, fn ($a, $b) => $rankings[$a]['pe'] <=> $rankings[$b]['pe']);
        for ($rank = 0; $rank < count($c_list); $rank++) {
            $rankings[$c_list[$rank]]['rank'] += $rank;
        }

        // XB ranking: lower is better
        usort($c_list, fn ($a, $b) => $rankings[$a]['xb'] <=> $rankings[$b]['xb']);
        for ($rank = 0; $rank < count($c_list); $rank++) {
            $rankings[$c_list[$rank]]['rank'] += $rank;
        }

        // Silhouette ranking: higher is better
        usort($c_list, fn ($a, $b) => $rankings[$b]['silhouette'] <=> $rankings[$a]['silhouette']);
        for ($rank = 0; $rank < count($c_list); $rank++) {
            $rankings[$c_list[$rank]]['rank'] += $rank;
        }

        // Find c with lowest total rank (prefer lower c in case of tie)
        $best_c = $cMin;
        $best_rank = PHP_INT_MAX;
        for ($c = $cMin; $c <= $cMax; $c++) {
            if ($rankings[$c]['rank'] < $best_rank) {
                $best_rank = $rankings[$c]['rank'];
                $best_c = $c;
            }
        }

        return $best_c;
    }

    /**
     * Get validity indices for a given cluster count.
     * Returns array with PC, PE, XB, Silhouette.
     */
    public function getValidityIndices(array $data, int $c): array
    {
        $result = $this->fit($data, $c);

        return [
            'c' => $c,
            'pc' => $this->partitionCoefficient($result['U']),
            'pe' => $this->partitionEntropy($result['U']),
            'xb' => $this->xieBeni($data, $result['centroid'], $result['U']),
            'silhouette' => $this->silhouette($data, $result['centroid'], $result['U']),
        ];
    }
}
