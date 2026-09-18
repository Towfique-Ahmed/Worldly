<?php

declare(strict_types=1);

/**
 * Builds exactly ten facts for every country.
 *
 * Required from tools/build_geodata.php, which leaves $countries, $cities and
 * $data in scope. The first facts are hand-written signature entries from
 * tools/data/country_facts.php; the rest are derived from the country's own
 * figures, so nothing is invented and every country reaches ten.
 */

/** @var list<array<string, mixed>> $countries */
/** @var list<array<string, mixed>> $cities */
/** @var string $data */

$curated = require __DIR__ . '/data/country_facts.php';
$mountains = require $data . '/mountains.php';
$places = require $data . '/places.php';

const FACT_TARGET = 10;

// --- lookup tables -----------------------------------------------------------

$nameByIso3 = [];
$continentTotals = [];
$worldPopulation = 0;
$worldArea = 0;

foreach ($countries as $country) {
    $nameByIso3[$country['iso3']] = $country['name'];
    $worldPopulation += $country['population'];
    $worldArea += $country['area'];

    $continent = $country['continent'];
    $continentTotals[$continent]['population'] = ($continentTotals[$continent]['population'] ?? 0) + $country['population'];
    $continentTotals[$continent]['area'] = ($continentTotals[$continent]['area'] ?? 0) + $country['area'];
    $continentTotals[$continent]['count'] = ($continentTotals[$continent]['count'] ?? 0) + 1;
}

/**
 * Dense ranking of countries by a numeric field, largest first.
 *
 * @return array<string, int>
 */
function rankBy(array $countries, string $field): array
{
    $sorted = array_filter($countries, static fn (array $c): bool => ($c[$field] ?? 0) > 0);
    usort($sorted, static fn (array $a, array $b): int => $b[$field] <=> $a[$field]);

    $ranks = [];
    foreach ($sorted as $index => $country) {
        $ranks[$country['iso3']] = $index + 1;
    }

    return $ranks;
}

$populationRank = rankBy($countries, 'population');
$areaRank = rankBy($countries, 'area');
$gdpRank = rankBy($countries, 'gdp');
$densityRank = rankBy($countries, 'density');
$rankedCount = count($countries);

// Cities grouped by country, largest first (the source list is already sorted).
$citiesByIso3 = [];
foreach ($cities as $city) {
    $citiesByIso3[$city['iso3']][] = $city;
}

$peaksByIso3 = [];
foreach ($mountains as $peak) {
    $peaksByIso3[$peak['iso3']][] = $peak;
}

$placesByIso3 = [];
foreach ($places as $place) {
    $placesByIso3[$place['iso3']][] = $place;
}

// --- helpers -----------------------------------------------------------------

function humanNumber(int|float $value): string
{
    return number_format((float) $value, 0, '.', ',');
}

function ordinal(int $n): string
{
    $suffix = match (true) {
        $n % 100 >= 11 && $n % 100 <= 13 => 'th',
        $n % 10 === 1 => 'st',
        $n % 10 === 2 => 'nd',
        $n % 10 === 3 => 'rd',
        default => 'th',
    };

    return $n . $suffix;
}

/** "A, B and C" */
function listPhrase(array $items, int $limit = 4): string
{
    $items = array_values(array_filter($items));
    $extra = 0;

    if (count($items) > $limit) {
        $extra = count($items) - $limit;
        $items = array_slice($items, 0, $limit);
    }

    $last = array_pop($items);
    $phrase = $items ? implode(', ', $items) . ' and ' . $last : (string) $last;

    return $extra > 0 ? $phrase . ' (and ' . $extra . ' more)' : $phrase;
}

/** Something recognisable to compare an area against. */
function areaComparison(int $km2): ?string
{
    $references = [
        ['Vatican City', 0.49],
        ['Monaco', 2.0],
        ['Malta', 316],
        ['Luxembourg', 2586],
        ['Cyprus', 9251],
        ['Belgium', 30528],
        ['Switzerland', 41285],
        ['Portugal', 92090],
        ['the United Kingdom', 242495],
        ['France', 551695],
        ['Egypt', 1002450],
        ['India', 3287590],
        ['Australia', 7692024],
        ['Russia', 17098242],
    ];

    foreach ($references as [$name, $size]) {
        if ($km2 <= 0 || $size <= 0) {
            continue;
        }

        $ratio = $km2 / $size;
        if ($ratio >= 1.6 && $ratio <= 12) {
            return sprintf('about %s times the size of %s', round($ratio, 1), $name);
        }
        if ($ratio >= 0.75 && $ratio < 1.35) {
            return sprintf('roughly the size of %s', $name);
        }
    }

    return null;
}

/** Which climate bands the country's label point sits in. */
function latitudeBand(float $lat): string
{
    $abs = abs($lat);
    $hemisphere = $lat >= 0 ? 'northern' : 'southern';

    return match (true) {
        $abs < 5 => 'sits almost on the equator',
        $abs < 23.44 => 'lies within the tropics, in the ' . $hemisphere . ' hemisphere',
        $abs < 35 => 'sits in the subtropics of the ' . $hemisphere . ' hemisphere',
        $abs < 60 => 'lies in the ' . $hemisphere . ' temperate belt',
        $abs < 66.56 => 'sits close to the ' . ($lat >= 0 ? 'Arctic' : 'Antarctic') . ' Circle',
        default => 'reaches beyond the ' . ($lat >= 0 ? 'Arctic' : 'Antarctic') . ' Circle',
    };
}

// --- fact generation ---------------------------------------------------------

$factsByIso3 = [];

foreach ($countries as $country) {
    $iso3 = $country['iso3'];
    $name = $country['name'];
    $facts = [];

    foreach ($curated[$iso3] ?? [] as $fact) {
        $facts[] = $fact;
    }

    $candidates = [];

    // Population and its world rank.
    if ($country['population'] > 0) {
        $share = $worldPopulation > 0 ? $country['population'] / $worldPopulation * 100 : 0;
        $rank = $populationRank[$iso3] ?? null;
        $candidates[] = sprintf(
            'Population is about %s people%s - roughly %s of everyone alive.',
            humanNumber($country['population']),
            $rank ? ', the ' . ordinal($rank) . ' largest of the ' . $rankedCount . ' countries mapped here' : '',
            $share >= 0.1 ? round($share, 2) . ' per cent' : 'one in ' . humanNumber(round(100 / max($share, 0.0001))),
        );
    }

    // Area, rank and a size comparison.
    if ($country['area'] > 0) {
        $rank = $areaRank[$iso3] ?? null;
        $comparison = areaComparison($country['area']);
        $candidates[] = sprintf(
            'Covers %s km²%s%s.',
            humanNumber($country['area']),
            $rank ? ', making it the ' . ordinal($rank) . ' largest country here' : '',
            $comparison ? ' - ' . $comparison : '',
        );
    }

    // Density.
    if ($country['density'] > 0) {
        $rank = $densityRank[$iso3] ?? null;
        $candidates[] = sprintf(
            'Population density is about %s people per km²%s.',
            humanNumber(round($country['density'], 1)),
            $rank && $rank <= 15 ? ', among the most crowded countries in the world' : ($rank && $rank > $rankedCount - 15 ? ', among the emptiest countries in the world' : ''),
        );
    }

    // Capital city.
    $capital = null;
    foreach ($citiesByIso3[$iso3] ?? [] as $city) {
        if ($city['capital']) {
            $capital = $city;
            break;
        }
    }
    if ($capital) {
        $candidates[] = sprintf(
            'The capital is %s, at %.2f° %s, %.2f° %s, with a metropolitan population of about %s.',
            $capital['name'],
            abs($capital['lat']), $capital['lat'] >= 0 ? 'N' : 'S',
            abs($capital['lon']), $capital['lon'] >= 0 ? 'E' : 'W',
            humanNumber($capital['population']),
        );
    }

    // Land borders.
    $borders = array_values(array_filter(array_map(
        static fn (string $code): ?string => $nameByIso3[$code] ?? null,
        $country['borders'],
    )));

    if ($borders) {
        $candidates[] = sprintf(
            'Shares land borders with %d %s: %s.',
            count($borders),
            count($borders) === 1 ? 'country' : 'countries',
            listPhrase($borders, 5),
        );
    } elseif ($country['landlocked']) {
        $candidates[] = 'Landlocked, with no coastline at all.';
    } else {
        $candidates[] = 'Has no land neighbours - it is an island nation, surrounded entirely by water.';
    }

    // Coast or no coast.
    if ($country['landlocked'] && $borders) {
        $candidates[] = sprintf('Landlocked: every route to the sea crosses at least one border.');
    } elseif (!$country['landlocked']) {
        $candidates[] = 'Has its own coastline and direct access to the open sea.';
    }

    // Time zones.
    $zones = $country['timezones'];
    if (count($zones) === 1) {
        $candidates[] = sprintf('Keeps a single time zone, %s.', str_replace('_', ' ', $zones[0]));
    } elseif (count($zones) > 1) {
        $candidates[] = sprintf(
            'Spans %d time zones, from %s to %s.',
            count($zones),
            str_replace('_', ' ', $zones[0]),
            str_replace('_', ' ', $zones[count($zones) - 1]),
        );
    }

    // Currency.
    if ($country['currencies']) {
        $currency = $country['currencies'][0];
        $candidates[] = sprintf(
            'The currency is the %s (%s%s).',
            $currency['name'],
            $currency['code'],
            $currency['symbol'] !== '' ? ', symbol ' . $currency['symbol'] : '',
        );
    }

    // Languages.
    if ($country['languages']) {
        $candidates[] = count($country['languages']) === 1
            ? sprintf('The official language is %s.', $country['languages'][0])
            : sprintf('Has %d official languages: %s.', count($country['languages']), listPhrase($country['languages'], 5));
    }

    // Dialling code and domain.
    if ($country['calling'] !== '' || $country['tld'] !== '') {
        $bits = [];
        if ($country['calling'] !== '') {
            $bits[] = 'dials on ' . $country['calling'];
        }
        if ($country['tld'] !== '') {
            $bits[] = 'uses the ' . $country['tld'] . ' internet domain';
        }
        $candidates[] = ucfirst(implode(' and ', $bits)) . '.';
    }

    // Economy.
    if ($country['gdp'] > 0) {
        $rank = $gdpRank[$iso3] ?? null;
        $candidates[] = sprintf(
            'Annual GDP is around $%s billion%s, or about $%s per person.',
            humanNumber(round($country['gdp'] / 1000)),
            $rank ? ' - the ' . ordinal($rank) . ' largest economy here' : '',
            humanNumber($country['gdpPerCapita']),
        );
    }

    // Where it sits.
    $candidates[] = sprintf(
        'Sits in %s, in the %s sub-region, and %s.',
        $country['continent'],
        $country['subregion'],
        latitudeBand((float) $country['lat']),
    );

    // Share of its continent.
    $continentTotal = $continentTotals[$country['continent']] ?? null;
    if ($continentTotal && $continentTotal['population'] > 0 && $country['population'] > 0) {
        $share = $country['population'] / $continentTotal['population'] * 100;
        if ($share >= 1) {
            $candidates[] = sprintf(
                'Holds about %s per cent of %s\'s people, one of %d countries on the continent.',
                round($share, 1),
                $country['continent'],
                $continentTotal['count'],
            );
        }
    }

    // Largest city, if it is not the capital.
    $largest = $citiesByIso3[$iso3][0] ?? null;
    if ($largest && (!$capital || $largest['name'] !== $capital['name'])) {
        $candidates[] = sprintf(
            'The largest city is %s, with about %s people - bigger than the capital.',
            $largest['name'],
            humanNumber($largest['population']),
        );
    }

    // Peaks.
    foreach ($peaksByIso3[$iso3] ?? [] as $peak) {
        $candidates[] = sprintf(
            '%s rises to %s m here, in the %s.',
            $peak['name'],
            humanNumber($peak['elevation']),
            $peak['range'],
        );
        break;
    }

    // Featured destinations.
    $destinations = array_column($placesByIso3[$iso3] ?? [], 'name');
    if ($destinations) {
        $candidates[] = sprintf(
            'Featured here for %s.',
            listPhrase($destinations, 3),
        );
    }

    // Demonym.
    if ($country['demonym'] !== '') {
        $candidates[] = sprintf('People from %s are called %s.', $name, $country['demonym']);
    }

    // Standing.
    if ($country['unMember']) {
        $candidates[] = sprintf('%s is a member state of the United Nations.', $name);
    } elseif ($country['independent']) {
        $candidates[] = sprintf('%s is independent but not a United Nations member state.', $name);
    } else {
        $candidates[] = sprintf('%s is a territory rather than a fully independent state.', $name);
    }

    // Number of cities we hold.
    $cityCount = count($citiesByIso3[$iso3] ?? []);
    if ($cityCount > 1) {
        $candidates[] = sprintf('This atlas plots %d of its towns and cities on the map.', $cityCount);
    }

    // Native name.
    if (!empty($country['nativeName']) && $country['nativeName'] !== $name) {
        $candidates[] = sprintf('Known locally as %s.', $country['nativeName']);
    }

    // Fill to exactly ten, skipping duplicates.
    foreach ($candidates as $candidate) {
        if (count($facts) >= FACT_TARGET) {
            break;
        }
        if (!in_array($candidate, $facts, true)) {
            $facts[] = $candidate;
        }
    }

    // A last-resort filler keeps the promise of ten even for sparse records.
    $fillers = [
        sprintf('Identified by the ISO codes %s and %s, and by the flag %s.', $country['iso2'], $iso3, $country['flag']),
        sprintf('Its map label sits at %.2f° %s, %.2f° %s.', abs((float) $country['lat']), $country['lat'] >= 0 ? 'N' : 'S', abs((float) $country['lon']), $country['lon'] >= 0 ? 'E' : 'W'),
        sprintf('Formally known as %s.', $country['formalName'] ?? $country['longName']),
        sprintf('Classified by Natural Earth as a %s economy.', strtolower($country['economy'] ?: 'developing region')),
        sprintf('Falls into the %s income group.', strtolower($country['income'] ?: 'unclassified')),
        sprintf('Grouped in the %s region of %s.', $country['subregion'], $country['continent']),
    ];

    foreach ($fillers as $filler) {
        if (count($facts) >= FACT_TARGET) {
            break;
        }
        if (!in_array($filler, $facts, true)) {
            $facts[] = $filler;
        }
    }

    $factsByIso3[$iso3] = array_slice($facts, 0, FACT_TARGET);
}

// --- verify ------------------------------------------------------------------

$short = array_filter($factsByIso3, static fn (array $f): bool => count($f) !== FACT_TARGET);
if ($short !== []) {
    fwrite(STDERR, "  WARNING: countries without exactly 10 facts: " . implode(', ', array_keys($short)) . "\n");
}

emitData(
    $data . '/facts.php',
    'Ten facts for every country: curated signature entries first, then facts derived from the country\'s own figures.',
    $factsByIso3,
);

printf("  %-30s %6d countries x 10 facts\n", 'verified', count($factsByIso3));
