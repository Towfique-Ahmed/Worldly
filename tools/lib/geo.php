<?php

declare(strict_types=1);

/**
 * Shared geometry helpers for the build scripts.
 */

require_once __DIR__ . '/../../src/Support/Projection.php';

use Worldly\Support\Projection;

const MAP_WIDTH = 1000.0;

/**
 * Ramer-Douglas-Peucker simplification in lon/lat space.
 *
 * @param list<array{0: float, 1: float}> $points
 * @return list<array{0: float, 1: float}>
 */
function simplify(array $points, float $tolerance): array
{
    $count = count($points);
    if ($count < 3) {
        return $points;
    }

    $keep = array_fill(0, $count, false);
    $keep[0] = true;
    $keep[$count - 1] = true;

    $stack = [[0, $count - 1]];
    $toleranceSq = $tolerance * $tolerance;

    while ($stack) {
        [$start, $end] = array_pop($stack);
        if ($end - $start < 2) {
            continue;
        }

        [$ax, $ay] = $points[$start];
        [$bx, $by] = $points[$end];
        $dx = $bx - $ax;
        $dy = $by - $ay;
        $lenSq = $dx * $dx + $dy * $dy;

        $farthest = -1;
        $farthestDist = 0.0;

        for ($i = $start + 1; $i < $end; $i++) {
            [$px, $py] = $points[$i];

            if ($lenSq > 0.0) {
                $t = (($px - $ax) * $dx + ($py - $ay) * $dy) / $lenSq;
                $t = max(0.0, min(1.0, $t));
                $cx = $ax + $t * $dx;
                $cy = $ay + $t * $dy;
            } else {
                $cx = $ax;
                $cy = $ay;
            }

            $dist = ($px - $cx) ** 2 + ($py - $cy) ** 2;
            if ($dist > $farthestDist) {
                $farthestDist = $dist;
                $farthest = $i;
            }
        }

        if ($farthest > 0 && $farthestDist > $toleranceSq) {
            $keep[$farthest] = true;
            $stack[] = [$start, $farthest];
            $stack[] = [$farthest, $end];
        }
    }

    $result = [];
    foreach ($points as $i => $point) {
        if ($keep[$i]) {
            $result[] = $point;
        }
    }

    return $result;
}

/**
 * Shoelace area of a ring, in square degrees.
 *
 * @param list<array{0: float, 1: float}> $ring
 */
function ringArea(array $ring): float
{
    $area = 0.0;
    $count = count($ring);
    for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
        $area += ($ring[$j][0] * $ring[$i][1]) - ($ring[$i][0] * $ring[$j][1]);
    }

    return abs($area) / 2.0;
}

/**
 * Flatten a GeoJSON geometry to a list of outer rings (polygons) or
 * line strings (lines).
 *
 * @param array<string, mixed> $geometry
 * @return list<list<array{0: float, 1: float}>>
 */
function geometryRings(array $geometry): array
{
    return match ($geometry['type']) {
        'Polygon' => [$geometry['coordinates'][0]],
        'MultiPolygon' => array_map(static fn (array $p): array => $p[0], $geometry['coordinates']),
        'LineString' => [$geometry['coordinates']],
        'MultiLineString' => $geometry['coordinates'],
        default => [],
    };
}

/**
 * Project already-simplified rings into an SVG path.
 *
 * @param list<list<array{0: float, 1: float}>> $rings
 */
function ringsToPath(array $rings, bool $close = true, int $precision = 1): string
{
    $path = '';

    foreach ($rings as $ring) {
        $segment = '';
        $previous = null;

        foreach ($ring as $coordinate) {
            [$x, $y] = Projection::point((float) $coordinate[0], (float) $coordinate[1], MAP_WIDTH);
            $x = round($x, $precision);
            $y = round($y, $precision);

            if ($previous === [$x, $y]) {
                continue;
            }

            $segment .= ($segment === '' ? 'M' : 'L') . $x . ' ' . $y;
            $previous = [$x, $y];
        }

        if ($segment !== '') {
            $path .= $segment . ($close ? 'Z' : '');
        }
    }

    return $path;
}

/**
 * A path that ignores segments crossing the antimeridian, so features do not
 * smear a stripe across the whole map.
 *
 * @param list<list<array{0: float, 1: float}>> $rings
 */
function ringsToOpenPath(array $rings, int $precision = 1): string
{
    $path = '';

    foreach ($rings as $ring) {
        $segment = '';
        $previousLon = null;
        $previousPoint = null;

        foreach ($ring as $coordinate) {
            $lon = (float) $coordinate[0];

            // A jump of more than 180 degrees means the line wrapped around.
            if ($previousLon !== null && abs($lon - $previousLon) > 180) {
                $path .= $segment;
                $segment = '';
                $previousPoint = null;
            }

            [$x, $y] = Projection::point($lon, (float) $coordinate[1], MAP_WIDTH);
            $x = round($x, $precision);
            $y = round($y, $precision);
            $previousLon = $lon;

            if ($previousPoint === [$x, $y]) {
                continue;
            }

            $segment .= ($segment === '' ? 'M' : 'L') . $x . ' ' . $y;
            $previousPoint = [$x, $y];
        }

        $path .= $segment;
    }

    return $path;
}

/** Area-weighted centroid of a geometry's rings, in lon/lat. */
function geometryCentroid(array $geometry): array
{
    $best = null;
    $bestArea = -1.0;

    foreach (geometryRings($geometry) as $ring) {
        $area = ringArea($ring);
        if ($area > $bestArea) {
            $bestArea = $area;
            $best = $ring;
        }
    }

    if (!$best) {
        return [0.0, 0.0];
    }

    $lon = 0.0;
    $lat = 0.0;
    foreach ($best as $point) {
        $lon += $point[0];
        $lat += $point[1];
    }

    return [round($lon / count($best), 3), round($lat / count($best), 3)];
}

/**
 * Natural Earth writes a double space after abbreviations ("Washington,  D.C.",
 * "Amu  Darya"). Collapse runs of whitespace so names read correctly wherever
 * they end up — page copy, tooltips and meta descriptions alike.
 */
function cleanName(?string $name): ?string
{
    if ($name === null) {
        return null;
    }

    return trim((string) preg_replace('/\s+/u', ' ', $name));
}

function flagEmoji(string $iso2): string
{
    if (strlen($iso2) !== 2 || !ctype_alpha($iso2)) {
        return '🏳';
    }

    $iso2 = strtoupper($iso2);

    return mb_chr(0x1F1E6 + (ord($iso2[0]) - 65), 'UTF-8')
         . mb_chr(0x1F1E6 + (ord($iso2[1]) - 65), 'UTF-8');
}

/**
 * Write a generated PHP data file.
 */
function emitData(string $file, string $header, array $payload): void
{
    $export = var_export($payload, true);
    $export = preg_replace('/=>\s*\n\s*array \(/', '=> array (', $export);

    $code = "<?php\n\ndeclare(strict_types=1);\n\n/**\n * {$header}\n *\n"
          . " * GENERATED FILE - do not edit by hand.\n"
          . " * Run `php tools/build_geodata.php` to regenerate.\n */\n\nreturn {$export};\n";

    if (!is_dir(dirname($file))) {
        mkdir(dirname($file), 0o777, true);
    }

    file_put_contents($file, $code);
    printf("  %-30s %6d entries  %7.1f KB\n", basename($file), count($payload), strlen($code) / 1024);
}

/** Download a source file into storage/raw once, then reuse it. */
function source(string $name, string $url): string
{
    $file = __DIR__ . '/../../storage/raw/' . $name;

    if (!is_file($file)) {
        fwrite(STDERR, "  downloading {$name}...\n");
        $body = file_get_contents($url);
        if ($body === false) {
            fwrite(STDERR, "  FAILED: {$url}\n");
            exit(1);
        }
        file_put_contents($file, $body);
    }

    return $file;
}

/** @return array<string, mixed> */
function loadJson(string $file): array
{
    return json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
}
