<?php

namespace Tests\Unit;

use App\Services\EllipseCalculator;
use PHPUnit\Framework\TestCase;

class EllipseCalculatorTest extends TestCase
{
    /**
     * Test: Ellipse untuk titik-titik di garis horizontal
     * Expectation: Semi-major axis sepanjang X, semi-minor axis minimal di Y
     */
    public function test_ellipse_horizontal_points(): void
    {
        $points = [
            [0, 1],
            [1, 1],
            [2, 1],
        ];

        $ellipse = EllipseCalculator::computeEllipse($points);

        $this->assertArrayHasKey('cx', $ellipse);
        $this->assertArrayHasKey('cy', $ellipse);
        $this->assertArrayHasKey('a', $ellipse);
        $this->assertArrayHasKey('b', $ellipse);
        $this->assertArrayHasKey('angle', $ellipse);

        // Centroid harus di tengah
        $this->assertEqualsWithDelta(1.0, $ellipse['cx'], 0.01);
        $this->assertEqualsWithDelta(1.0, $ellipse['cy'], 0.01);

        // Semi-major harus lebih besar dari semi-minor (spread horizontal)
        $this->assertGreaterThan($ellipse['b'], $ellipse['a']);
    }

    /**
     * Test: Ellipse untuk titik-titik di garis vertikal
     */
    public function test_ellipse_vertical_points(): void
    {
        $points = [
            [1, 0],
            [1, 1],
            [1, 2],
        ];

        $ellipse = EllipseCalculator::computeEllipse($points);

        // Centroid di tengah
        $this->assertEqualsWithDelta(1.0, $ellipse['cx'], 0.01);
        $this->assertEqualsWithDelta(1.0, $ellipse['cy'], 0.01);

        // Semi-major harus lebih besar (spread vertikal)
        $this->assertGreaterThan($ellipse['b'], $ellipse['a']);
    }

    /**
     * Test: Ellipse untuk single point
     * Expectation: a=0, b=0 (bukan lingkaran, melainkan titik identik)
     */
    public function test_ellipse_single_point(): void
    {
        $points = [[2.5, 3.5]];

        $ellipse = EllipseCalculator::computeEllipse($points);

        $this->assertEqualsWithDelta(2.5, $ellipse['cx'], 0.01);
        $this->assertEqualsWithDelta(3.5, $ellipse['cy'], 0.01);
        $this->assertEquals(0, $ellipse['a']);
        $this->assertEquals(0, $ellipse['b']);
    }

    /**
     * Test: SVG ellipse path generation
     */
    public function test_svg_ellipse_path(): void
    {
        $path = EllipseCalculator::svgEllipsePath(100, 100, 50, 30, 0);

        // Path harus dimulai dengan M (move) dan diakhiri Z (close)
        $this->assertStringStartsWith('M', $path);
        $this->assertStringEndsWith('Z', $path);

        // Path harus berisi L commands (line)
        $this->assertStringContainsString('L', $path);

        // Path harus berisi koordinat
        $this->assertStringContainsString('100', $path);
    }

    /**
     * Test: Ellipse untuk 4 titik identik
     * Expectation: a=0, b=0 (no variance), tanpa NaN
     */
    public function test_ellipse_four_identical_points(): void
    {
        $points = [
            [5.0, 5.0],
            [5.0, 5.0],
            [5.0, 5.0],
            [5.0, 5.0],
        ];

        $ellipse = EllipseCalculator::computeEllipse($points);

        $this->assertEqualsWithDelta(5.0, $ellipse['cx'], 0.01);
        $this->assertEqualsWithDelta(5.0, $ellipse['cy'], 0.01);
        $this->assertEquals(0, $ellipse['a']);
        $this->assertEquals(0, $ellipse['b']);
        $this->assertTrue(is_finite($ellipse['a']), 'Semi-major axis should be finite');
        $this->assertTrue(is_finite($ellipse['b']), 'Semi-minor axis should be finite');
    }

    /**
     * Test: Ellipse untuk 3 titik (minimal) tidak menghasilkan NaN/INF
     * Expectation: Result finite, a > 0, b >= 0
     */
    public function test_ellipse_three_points_finite(): void
    {
        $points = [
            [0, 0],
            [1, 1],
            [2, 0],
        ];

        $ellipse = EllipseCalculator::computeEllipse($points);

        $this->assertTrue(is_finite($ellipse['cx']), 'Center X should be finite');
        $this->assertTrue(is_finite($ellipse['cy']), 'Center Y should be finite');
        $this->assertTrue(is_finite($ellipse['a']), 'Semi-major axis should be finite');
        $this->assertTrue(is_finite($ellipse['b']), 'Semi-minor axis should be finite');
        $this->assertTrue(is_finite($ellipse['angle']), 'Angle should be finite');
        $this->assertGreaterThanOrEqual(0, $ellipse['a']);
        $this->assertGreaterThanOrEqual(0, $ellipse['b']);
    }
}
