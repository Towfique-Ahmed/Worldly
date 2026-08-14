<?php

declare(strict_types=1);

/**
 * Regenerates src/Data/countries.php and src/Data/cities.php from Natural Earth.
 *
 * Usage: php tools/build_geodata.php
 *
 * The raw Natural Earth downloads are cached in storage/raw/ and are not tracked
 * in git — only the generated PHP payloads are.
 */

require __DIR__ . '/../src/Support/Projection.php';

use Worldly\Support\Projection;

const MAP_WIDTH = 1000.0;
const SIMPLIFY_TOLERANCE = 0.28; // degrees
const MIN_RING_AREA = 0.30;      // square degrees

$sources = [
    'countries' => [
        'url' => 'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/geojson/ne_110m_admin_0_countries.geojson',
        'file' => __DIR__ . '/../storage/raw/ne_110m_admin_0_countries.geojson',
    ],
    'places' => [
        'url' => 'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/geojson/ne_110m_populated_places_simple.geojson',
        'file' => __DIR__ . '/../storage/raw/ne_110m_populated_places_simple.geojson',
    ],
];

foreach ($sources as $name => $source) {
    if (!is_file($source['file'])) {
        fwrite(STDERR, "Downloading {$name}...\n");
        $body = file_get_contents($source['url']);
        if ($body === false) {
            fwrite(STDERR, "Failed to download {$source['url']}\n");
            exit(1);
        }
        file_put_contents($source['file'], $body);
    }
}

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
 * Build an SVG path from already-simplified rings.
 *
 * @param list<list<array{0: float, 1: float}>> $rings
 */
function ringsToPath(array $rings): string
{
    $path = '';
    foreach ($rings as $ring) {
        $segment = '';
        $previous = null;
        foreach ($ring as [$lon, $lat]) {
            [$x, $y] = Projection::point($lon, $lat, MAP_WIDTH);
            $x = round($x, 1);
            $y = round($y, 1);
            if ($previous === [$x, $y]) {
                continue;
            }
            $segment .= ($segment === '' ? 'M' : 'L') . $x . ' ' . $y;
            $previous = [$x, $y];
        }
        if ($segment !== '') {
            $path .= $segment . 'Z';
        }
    }

    return $path;
}

function flagEmoji(string $iso2): string
{
    if (strlen($iso2) !== 2 || !ctype_alpha($iso2)) {
        return '🏳';
    }

    $iso2 = strtoupper($iso2);
    $first = 0x1F1E6 + (ord($iso2[0]) - 65);
    $second = 0x1F1E6 + (ord($iso2[1]) - 65);

    return mb_chr($first, 'UTF-8') . mb_chr($second, 'UTF-8');
}

// ---------------------------------------------------------------------------
// Countries
// ---------------------------------------------------------------------------

$geo = json_decode((string) file_get_contents($sources['countries']['file']), true, 512, JSON_THROW_ON_ERROR);
$countries = [];

foreach ($geo['features'] as $feature) {
    $props = $feature['properties'];
    $geometry = $feature['geometry'];

    $polygons = $geometry['type'] === 'Polygon'
        ? [$geometry['coordinates']]
        : $geometry['coordinates'];

    $rings = [];
    $largest = null;
    $largestArea = 0.0;

    foreach ($polygons as $polygon) {
        // Only the outer ring of each polygon: at 110m scale the holes are noise.
        $ring = $polygon[0];
        $area = ringArea($ring);
        if ($area > $largestArea) {
            $largestArea = $area;
            $largest = $ring;
        }
        if ($area < MIN_RING_AREA) {
            continue;
        }
        $simplified = simplify($ring, SIMPLIFY_TOLERANCE);
        if (count($simplified) >= 4) {
            $rings[] = $simplified;
        }
    }

    if ($rings === [] && $largest !== null) {
        $rings[] = simplify($largest, SIMPLIFY_TOLERANCE / 3);
    }

    $path = ringsToPath($rings);
    if ($path === '') {
        continue;
    }

    $iso2 = $props['ISO_A2_EH'] ?? $props['ISO_A2'] ?? '-99';
    if ($iso2 === '-99' || strlen((string) $iso2) !== 2) {
        $iso2 = substr((string) ($props['ADM0_A3'] ?? 'XX'), 0, 2);
    }

    $iso3 = $props['ADM0_A3'] ?? $props['ISO_A3'] ?? 'XXX';

    $population = (int) ($props['POP_EST'] ?? 0);
    $gdp = (int) ($props['GDP_MD'] ?? 0);

    $countries[$iso3] = [
        'iso3' => $iso3,
        'iso2' => strtoupper((string) $iso2),
        'name' => $props['NAME'] ?? $props['ADMIN'],
        'longName' => $props['NAME_LONG'] ?? $props['NAME'],
        'formalName' => $props['FORMAL_EN'] ?? null,
        'continent' => $props['CONTINENT'] ?? 'Unknown',
        'region' => $props['REGION_UN'] ?? 'Unknown',
        'subregion' => $props['SUBREGION'] ?? 'Unknown',
        'economy' => preg_replace('/^\d+\.\s*/', '', (string) ($props['ECONOMY'] ?? '')),
        'income' => preg_replace('/^\d+\.\s*/', '', (string) ($props['INCOME_GRP'] ?? '')),
        'population' => $population,
        'gdp' => $gdp,
        'gdpPerCapita' => $population > 0 ? (int) round($gdp * 1_000_000 / $population) : 0,
        'flag' => flagEmoji(strtoupper((string) $iso2)),
        'lon' => round((float) ($props['LABEL_X'] ?? 0), 3),
        'lat' => round((float) ($props['LABEL_Y'] ?? 0), 3),
        'path' => $path,
    ];
}

uasort($countries, static fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

// ---------------------------------------------------------------------------
// Cities
// ---------------------------------------------------------------------------

$placesGeo = json_decode((string) file_get_contents($sources['places']['file']), true, 512, JSON_THROW_ON_ERROR);
$cities = [];

foreach ($placesGeo['features'] as $feature) {
    $props = $feature['properties'];

    $cities[] = [
        'name' => $props['name'],
        'country' => $props['adm0name'],
        'iso3' => $props['adm0_a3'],
        'iso2' => strtoupper((string) ($props['iso_a2'] ?? '')),
        'admin' => $props['adm1name'] ?? null,
        'capital' => (int) ($props['adm0cap'] ?? 0) === 1,
        'megacity' => (int) ($props['megacity'] ?? 0) === 1,
        'population' => (int) ($props['pop_max'] ?? 0),
        'lat' => round((float) $props['latitude'], 4),
        'lon' => round((float) $props['longitude'], 4),
    ];
}

usort($cities, static fn (array $a, array $b): int => $b['population'] <=> $a['population']);

// ---------------------------------------------------------------------------
// Emit
// ---------------------------------------------------------------------------

function emit(string $file, string $header, array $payload): void
{
    $export = var_export($payload, true);
    $export = preg_replace('/=>\s*\n\s*array \(/', '=> array (', $export);
    $code = "<?php\n\ndeclare(strict_types=1);\n\n/**\n * {$header}\n *\n * GENERATED FILE — do not edit by hand.\n * Run `php tools/build_geodata.php` to regenerate.\n */\n\nreturn {$export};\n";
    file_put_contents($file, $code);
    printf("%-34s %6d entries  %5.1f KB\n", basename($file), count($payload), strlen($code) / 1024);
}

emit(__DIR__ . '/../src/Data/countries.php', 'Country outlines (Robinson-projected SVG paths) and profile data.', array_values($countries));
emit(__DIR__ . '/../src/Data/cities.php', 'Notable cities and national capitals with coordinates.', $cities);
