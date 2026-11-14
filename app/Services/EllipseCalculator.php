<?php

namespace App\Services;

/**
 * EllipseCalculator: Hitung elips keyakinan dari titik-titik 2D
 * Tanpa external library, menggunakan SVD numerik sederhana
 */
class EllipseCalculator
{
    /**
     * Hitung elips keyakinan (confidence ellipse) dari titik-titik 2D
     *
     * @param  array  $points  Array of [x, y] points
     * @param  float  $stdDev  Multiplier untuk standar deviasi (default 1.5 ~ 86% kepercayaan)
     * @return array ['cx' => centroid_x, 'cy' => centroid_y, 'a' => semi_major, 'b' => semi_minor, 'angle' => rotation_degrees]
     */
    public static function computeEllipse(array $points, float $stdDev = 1.5): array
    {
        if (count($points) < 2) {
            return [
                'cx' => $points[0][0] ?? 0,
                'cy' => $points[0][1] ?? 0,
                'a' => 0,
                'b' => 0,
                'angle' => 0,
            ];
        }

        // 1. Hitung centroid
        $cx = 0;
        $cy = 0;
        foreach ($points as $p) {
            $cx += $p[0];
            $cy += $p[1];
        }
        $cx /= count($points);
        $cy /= count($points);

        // 2. Hitung kovarians matrix 2x2
        $cov_xx = 0;
        $cov_yy = 0;
        $cov_xy = 0;
        foreach ($points as $p) {
            $dx = $p[0] - $cx;
            $dy = $p[1] - $cy;
            $cov_xx += $dx * $dx;
            $cov_yy += $dy * $dy;
            $cov_xy += $dx * $dy;
        }
        $n = count($points);
        $cov_xx /= ($n - 1);
        $cov_yy /= ($n - 1);
        $cov_xy /= ($n - 1);

        // 3. Hitung eigenvalues & eigenvectors (2x2 symmetric matrix)
        [$lambda1, $lambda2, $angle] = self::eigenvalues2x2($cov_xx, $cov_xy, $cov_yy);

        // 4. Semi-major & semi-minor axis (scaled by $stdDev)
        $a = $stdDev * sqrt(abs($lambda1));
        $b = $stdDev * sqrt(abs($lambda2));

        return [
            'cx' => $cx,
            'cy' => $cy,
            'a' => $a,
            'b' => $b,
            'angle' => $angle,
        ];
    }

    /**
     * Eigenvalue & eigenvector untuk 2x2 symmetric matrix
     * Matrix: [[a, c], [c, b]]
     *
     * @return array [lambda1, lambda2, angle_in_degrees]
     */
    private static function eigenvalues2x2(float $a, float $c, float $b): array
    {
        // Eigenvalues: λ = (a + b ± √((a-b)² + 4c²)) / 2
        $tr = $a + $b;
        $det = $a * $b - $c * $c;
        $disc = sqrt(max(0, $tr * $tr - 4 * $det));

        $lambda1 = ($tr + $disc) / 2;
        $lambda2 = ($tr - $disc) / 2;

        // Angle: arctan(2c / (a - b))
        $angle = 0;
        if (abs($a - $b) > 1e-10 || abs($c) > 1e-10) {
            $angle = 0.5 * atan2(2 * $c, $a - $b);
            $angle = rad2deg($angle);
        }

        return [$lambda1, $lambda2, $angle];
    }

    /**
     * Generate SVG path untuk elips
     *
     * @param  float  $cx  Center X
     * @param  float  $cy  Center Y
     * @param  float  $rx  Radius X
     * @param  float  $ry  Radius Y
     * @param  float  $angle  Rotasi dalam degrees
     * @return string SVG path command
     */
    public static function svgEllipsePath(float $cx, float $cy, float $rx, float $ry, float $angle): string
    {
        // Gunakan SVG arc untuk draw elips yang dirotasi
        // Simplified: gunakan formula parametrik dengan 36 points (10° interval)
        $points = [];
        for ($i = 0; $i <= 360; $i += 10) {
            $rad = deg2rad($i);
            $x = $rx * cos($rad);
            $y = $ry * sin($rad);

            // Rotasi
            $angleRad = deg2rad($angle);
            $xRot = $x * cos($angleRad) - $y * sin($angleRad);
            $yRot = $x * sin($angleRad) + $y * cos($angleRad);

            // Translate ke centroid
            $points[] = [$cx + $xRot, $cy + $yRot];
        }

        // Generate path
        $path = 'M '.round($points[0][0], 1).' '.round($points[0][1], 1);
        foreach (array_slice($points, 1) as $p) {
            $path .= ' L '.round($p[0], 1).' '.round($p[1], 1);
        }
        $path .= ' Z';

        return $path;
    }
}
