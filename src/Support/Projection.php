<?php

declare(strict_types=1);

namespace Worldly\Support;

/**
 * Robinson projection.
 *
 * The same maths is mirrored in public/assets/js/projection.js so that markers,
 * the day/night terminator and the great-circle arcs drawn in the browser land
 * exactly on top of the coastlines rendered here by PHP.
 */
final class Projection
{
    /** Horizontal scaling factor per 5 degrees of latitude. */
    private const AA = [
        1.0000, 0.9986, 0.9954, 0.9900, 0.9822, 0.9730, 0.9600, 0.9427, 0.9216,
        0.8962, 0.8679, 0.8350, 0.7986, 0.7597, 0.7186, 0.6732, 0.6213, 0.5722, 0.5322,
    ];

    /** Vertical position factor per 5 degrees of latitude. */
    private const BB = [
        0.0000, 0.0620, 0.1240, 0.1860, 0.2480, 0.3100, 0.3720, 0.4340, 0.4958,
        0.5571, 0.6176, 0.6769, 0.7346, 0.7903, 0.8435, 0.8936, 0.9394, 0.9761, 1.0000,
    ];

    /** Half width of the projected world in projection units. */
    public const HALF_WIDTH = 0.8487 * M_PI;

    /** Half height of the projected world in projection units. */
    public const HALF_HEIGHT = 1.3523;

    public const ASPECT = self::HALF_WIDTH / self::HALF_HEIGHT;

    /**
     * Project a lon/lat pair into viewport coordinates.
     *
     * @return array{0: float, 1: float}
     */
    public static function point(float $lon, float $lat, float $width): array
    {
        $height = $width / self::ASPECT;

        $abs = min(abs($lat), 90.0);
        $index = $abs / 5.0;

        $ax = self::interpolate(self::AA, $index);
        $by = self::interpolate(self::BB, $index);

        $x = 0.8487 * deg2rad($lon) * $ax;
        $y = 1.3523 * $by * ($lat < 0 ? -1 : 1);

        return [
            ($x / self::HALF_WIDTH * 0.5 + 0.5) * $width,
            (0.5 - $y / self::HALF_HEIGHT * 0.5) * $height,
        ];
    }

    public static function height(float $width): float
    {
        return $width / self::ASPECT;
    }

    /**
     * Smooth (Neville) interpolation across the four table entries closest to $index.
     *
     * @param list<float> $table
     */
    private static function interpolate(array $table, float $index): float
    {
        $last = count($table) - 1;
        $i = (int) floor($index);
        $i = max(1, min($last - 2, $i));

        $t = $index - $i;
        $p0 = $table[$i - 1];
        $p1 = $table[$i];
        $p2 = $table[$i + 1];
        $p3 = $table[$i + 2];

        // Catmull-Rom spline through the tabulated control points.
        return 0.5 * (
            2 * $p1
            + (-$p0 + $p2) * $t
            + (2 * $p0 - 5 * $p1 + 4 * $p2 - $p3) * $t * $t
            + (-$p0 + 3 * $p1 - 3 * $p2 + $p3) * $t * $t * $t
        );
    }
}
