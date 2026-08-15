<?php

declare(strict_types=1);

/**
 * Regenerates every dataset under src/Data from Natural Earth and the
 * mledoze/countries attribute set.
 *
 * Usage: php tools/build_geodata.php
 *
 * Raw downloads are cached in storage/raw/ and are not tracked in git; only the
 * generated PHP payloads are. Geometry is written to src/Data/geometry/ so that
 * pages which never draw a map do not pay to parse it.
 */

require __DIR__ . '/lib/geo.php';

const NE = 'https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/geojson/';

$data = __DIR__ . '/../src/Data';
$geometry = $data . '/geometry';

echo "Worldly geodata build\n\n";

// ---------------------------------------------------------------------------
// Countries — 1:50m outlines, ~5x the coastline detail of the previous 1:110m
// ---------------------------------------------------------------------------

echo "Countries\n";

$geo = loadJson(source('ne_50m_admin_0_countries.geojson', NE . 'ne_50m_admin_0_countries.geojson'));
$attributes = loadJson(source('countries.json', 'https://raw.githubusercontent.com/mledoze/countries/master/countries.json'));

$byIso3 = [];
foreach ($attributes as $entry) {
    $byIso3[$entry['cca3']] = $entry;
}

$countries = [];
$paths = [];
$globe = [];

foreach ($geo['features'] as $feature) {
    $props = $feature['properties'];

    $rings = [];
    $largest = null;
    $largestArea = 0.0;

    foreach (geometryRings($feature['geometry']) as $ring) {
        $area = ringArea($ring);
        if ($area > $largestArea) {
            $largestArea = $area;
            $largest = $ring;
        }
        if ($area < 0.02) {
            continue;
        }
        $simplified = simplify($ring, 0.055);
        if (count($simplified) >= 4) {
            $rings[] = $simplified;
        }
    }

    if ($rings === [] && $largest !== null) {
        $rings[] = simplify($largest, 0.01);
    }

    $path = ringsToPath($rings, true, 2);
    if ($path === '') {
        continue;
    }

    $iso3 = $props['ADM0_A3'] ?? $props['ISO_A3'] ?? 'XXX';
    $iso2 = $props['ISO_A2_EH'] ?? $props['ISO_A2'] ?? '-99';
    if ($iso2 === '-99' || strlen((string) $iso2) !== 2) {
        $iso2 = substr((string) $iso3, 0, 2);
    }
    $iso2 = strtoupper((string) $iso2);

    $extra = $byIso3[$iso3] ?? $byIso3[$props['ISO_A3'] ?? ''] ?? null;

    $population = (int) ($props['POP_EST'] ?? 0);
    $gdp = (int) ($props['GDP_MD'] ?? 0);
    $area = (int) round((float) ($extra['area'] ?? 0));

    // PHP ships the IANA database, so zones per country come for free.
    $zones = [];
    try {
        $zones = \DateTimeZone::listIdentifiers(\DateTimeZone::PER_COUNTRY, $iso2) ?: [];
    } catch (\Throwable) {
        $zones = [];
    }

    $languages = $extra['languages'] ?? [];
    $currencies = [];
    foreach ($extra['currencies'] ?? [] as $code => $currency) {
        $currencies[] = [
            'code' => $code,
            'name' => $currency['name'] ?? $code,
            'symbol' => $currency['symbol'] ?? '',
        ];
    }

    $calling = '';
    if (isset($extra['idd']['root'])) {
        $suffixes = $extra['idd']['suffixes'] ?? [];
        $calling = $extra['idd']['root'] . (count($suffixes) === 1 ? $suffixes[0] : '');
    }

    $countries[] = [
        'iso3' => $iso3,
        'iso2' => $iso2,
        'name' => $props['NAME'] ?? $props['ADMIN'],
        'longName' => $props['NAME_LONG'] ?? $props['NAME'],
        'formalName' => $extra['name']['official'] ?? $props['FORMAL_EN'] ?? null,
        'nativeName' => nativeName($extra),
        'continent' => $props['CONTINENT'] ?? 'Unknown',
        'region' => $props['REGION_UN'] ?? 'Unknown',
        'subregion' => $props['SUBREGION'] ?? 'Unknown',
        'economy' => preg_replace('/^\d+\.\s*/', '', (string) ($props['ECONOMY'] ?? '')),
        'income' => preg_replace('/^\d+\.\s*/', '', (string) ($props['INCOME_GRP'] ?? '')),
        'population' => $population,
        'gdp' => $gdp,
        'gdpPerCapita' => $population > 0 ? (int) round($gdp * 1_000_000 / $population) : 0,
        'area' => $area,
        'density' => ($area > 0 && $population > 0) ? round($population / $area, 1) : 0.0,
        'flag' => flagEmoji($iso2),
        'lon' => round((float) ($props['LABEL_X'] ?? 0), 3),
        'lat' => round((float) ($props['LABEL_Y'] ?? 0), 3),
        'landlocked' => (bool) ($extra['landlocked'] ?? false),
        'unMember' => (bool) ($extra['unMember'] ?? false),
        'independent' => (bool) ($extra['independent'] ?? false),
        'borders' => $extra['borders'] ?? [],
        'languages' => array_values($languages),
        'currencies' => $currencies,
        'calling' => $calling,
        'tld' => $extra['tld'][0] ?? '',
        'demonym' => $extra['demonyms']['eng']['m'] ?? '',
        'timezones' => array_values($zones),
    ];

    $paths[$iso3] = $path;

    // A much coarser lon/lat copy drives the canvas globe, which reprojects
    // every frame and so cannot reuse the pre-projected SVG paths.
    $coarse = [];
    foreach ($rings as $ring) {
        if (ringArea($ring) < 1.2) {
            continue;
        }
        $reduced = simplify($ring, 0.7);
        if (count($reduced) < 4) {
            continue;
        }
        $flat = [];
        foreach ($reduced as $point) {
            $flat[] = round((float) $point[0], 1);
            $flat[] = round((float) $point[1], 1);
        }
        $coarse[] = $flat;
    }
    if ($coarse !== []) {
        $globe[$iso3] = $coarse;
    }
}

usort($countries, static fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

emitData($data . '/countries.php', 'Country profiles merged from Natural Earth and mledoze/countries.', $countries);
emitData($geometry . '/countries.php', 'Robinson-projected country outlines (Natural Earth 1:50m).', $paths);
emitData($geometry . '/globe.php', 'Coarse lon/lat country rings, reprojected each frame by the canvas globe.', $globe);

function nativeName(?array $extra): ?string
{
    $native = $extra['name']['native'] ?? [];
    foreach ($native as $entry) {
        if (!empty($entry['common'])) {
            return $entry['common'];
        }
    }

    return null;
}

// ---------------------------------------------------------------------------
// Cities
// ---------------------------------------------------------------------------

echo "\nCities\n";

$placesGeo = loadJson(source('ne50places.geojson', NE . 'ne_50m_populated_places_simple.geojson'));
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
emitData($data . '/cities.php', 'Notable cities and national capitals (Natural Earth 1:50m).', $cities);

// ---------------------------------------------------------------------------
// Rivers
// ---------------------------------------------------------------------------

echo "\nWater\n";

$riverGeo = loadJson(source('ne_50m_rivers_lake_centerlines.geojson', NE . 'ne_50m_rivers_lake_centerlines.geojson'));
$riverFacts = require __DIR__ . '/data/river_facts.php';
$rivers = [];

foreach ($riverGeo['features'] as $feature) {
    $props = $feature['properties'];
    $name = $props['name_en'] ?? $props['name'] ?? null;
    if ($name === null || (int) ($props['scalerank'] ?? 99) > 7) {
        continue;
    }

    $rings = array_map(
        static fn (array $line): array => simplify($line, 0.05),
        geometryRings($feature['geometry']),
    );

    $path = ringsToOpenPath($rings, 2);
    if ($path === '') {
        continue;
    }

    // Several segments share a river name; merge them into one path.
    if (isset($rivers[$name])) {
        $rivers[$name]['path'] .= $path;
        continue;
    }

    $centroid = geometryCentroid($feature['geometry']);
    $facts = $riverFacts[$name] ?? null;

    $rivers[$name] = [
        'name' => $name,
        'rank' => (int) ($props['scalerank'] ?? 9),
        'path' => $path,
        'lon' => $centroid[0],
        'lat' => $centroid[1],
        'length' => $facts['length'] ?? 0,
        'basin' => $facts['basin'] ?? 0,
        'discharge' => $facts['discharge'] ?? 0,
        'outflow' => $facts['outflow'] ?? null,
        'continent' => $facts['continent'] ?? null,
        'countries' => $facts['countries'] ?? [],
        'note' => $facts['note'] ?? null,
    ];
}

$rivers = array_values($rivers);
usort($rivers, static fn (array $a, array $b): int => ($b['length'] <=> $a['length']) ?: ($a['rank'] <=> $b['rank']));
emitData($data . '/rivers.php', 'World rivers with projected centrelines and curated statistics.', $rivers);

// ---------------------------------------------------------------------------
// Lakes
// ---------------------------------------------------------------------------

$lakeGeo = loadJson(source('ne_50m_lakes.geojson', NE . 'ne_50m_lakes.geojson'));
$lakeFacts = require __DIR__ . '/data/lake_facts.php';
$lakes = [];

foreach ($lakeGeo['features'] as $feature) {
    $props = $feature['properties'];
    $name = $props['name_en'] ?? $props['name'] ?? null;
    if ($name === null || (int) ($props['scalerank'] ?? 99) > 2) {
        continue;
    }

    $rings = [];
    foreach (geometryRings($feature['geometry']) as $ring) {
        if (ringArea($ring) < 0.05) {
            continue;
        }
        $rings[] = simplify($ring, 0.04);
    }

    $path = ringsToPath($rings, true, 2);
    if ($path === '') {
        continue;
    }

    $centroid = geometryCentroid($feature['geometry']);
    $facts = $lakeFacts[$name] ?? null;

    if (isset($lakes[$name])) {
        $lakes[$name]['path'] .= $path;
        continue;
    }

    $lakes[$name] = [
        'name' => $name,
        'path' => $path,
        'lon' => $centroid[0],
        'lat' => $centroid[1],
        'area' => $facts['area'] ?? 0,
        'depth' => $facts['depth'] ?? 0,
        'volume' => $facts['volume'] ?? 0,
        'type' => $facts['type'] ?? 'Freshwater lake',
        'continent' => $facts['continent'] ?? null,
        'countries' => $facts['countries'] ?? [],
        'note' => $facts['note'] ?? null,
    ];
}

$lakes = array_values($lakes);
usort($lakes, static fn (array $a, array $b): int => $b['area'] <=> $a['area']);
emitData($data . '/lakes.php', 'World lakes with projected outlines and curated statistics.', $lakes);

// ---------------------------------------------------------------------------
// Oceans and seas
// ---------------------------------------------------------------------------

$marineGeo = loadJson(source('ne_50m_geography_marine_polys.geojson', NE . 'ne_50m_geography_marine_polys.geojson'));
$oceanFacts = require __DIR__ . '/data/ocean_facts.php';
$oceans = [];

foreach ($marineGeo['features'] as $feature) {
    $props = $feature['properties'];
    $name = $props['name_en'] ?? $props['name'] ?? null;
    if ($name === null || (int) ($props['scalerank'] ?? 99) > 1) {
        continue;
    }

    $centroid = geometryCentroid($feature['geometry']);
    $facts = $oceanFacts[$name] ?? null;

    $oceans[] = [
        'name' => $name,
        'label' => $props['label'] ?? strtoupper($name),
        'kind' => ucfirst((string) ($props['featurecla'] ?? 'sea')),
        'rank' => (int) ($props['scalerank'] ?? 9),
        'lon' => $centroid[0],
        'lat' => $centroid[1],
        'area' => $facts['area'] ?? 0,
        'maxDepth' => $facts['maxDepth'] ?? 0,
        'avgDepth' => $facts['avgDepth'] ?? 0,
        'deepest' => $facts['deepest'] ?? null,
        'note' => $facts['note'] ?? null,
    ];
}

usort($oceans, static fn (array $a, array $b): int => $b["area"] <=> $a["area"]);
emitData($data . '/oceans.php', 'Oceans and major seas with label anchors and curated statistics.', $oceans);

// ---------------------------------------------------------------------------
// Terrain — deserts, ranges, plateaus and tundra drive the physical map style
// ---------------------------------------------------------------------------

echo "\nTerrain\n";

$regionGeo = loadJson(source('geo_regions.geojson', NE . 'ne_50m_geography_regions_polys.geojson'));

$terrainClasses = [
    'Desert' => 'desert',
    'Range/mtn' => 'range',
    'Plateau' => 'plateau',
    'Tundra' => 'tundra',
    'Plain' => 'plain',
    'Basin' => 'basin',
    'Lowland' => 'plain',
    'Foothills' => 'range',
];

$terrain = [];

foreach ($regionGeo['features'] as $feature) {
    $props = $feature['properties'];
    $class = $terrainClasses[$props['FEATURECLA'] ?? ''] ?? null;
    if ($class === null) {
        continue;
    }

    $rings = [];
    foreach (geometryRings($feature['geometry']) as $ring) {
        if (ringArea($ring) < 0.6) {
            continue;
        }
        $rings[] = simplify($ring, 0.22);
    }

    $path = ringsToPath($rings, true, 1);
    if ($path === '') {
        continue;
    }

    $centroid = geometryCentroid($feature['geometry']);

    $terrain[] = [
        'name' => $props['NAME_EN'] ?? $props['NAME'] ?? '',
        'kind' => $class,
        'region' => $props['REGION'] ?? null,
        'rank' => (int) ($props['SCALERANK'] ?? 5),
        'lon' => $centroid[0],
        'lat' => $centroid[1],
        'path' => $path,
    ];
}

emitData($geometry . '/terrain.php', 'Deserts, mountain ranges, plateaus and tundra for the physical map style.', $terrain);

// ---------------------------------------------------------------------------
// Country facts
// ---------------------------------------------------------------------------

echo "\nFacts\n";
require __DIR__ . '/build_facts.php';

echo "\nDone.\n";
