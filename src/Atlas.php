<?php

declare(strict_types=1);

namespace Worldly;

/**
 * In-memory read model over the generated and curated datasets.
 *
 * Everything is loaded lazily and memoised. Geometry lives in Data/geometry and
 * is only touched when a page actually draws a map, so the clock pages never
 * pay to parse a few hundred kilobytes of SVG paths.
 */
final class Atlas
{
    /**
     * Natural Earth admin-0 entries with no permanent civilian population —
     * research stations, a military base, a glacier. Their tiny population
     * estimate makes GDP per capita a meaningless outlier, so wealth rankings
     * leave them out rather than crown Antarctica the richest place on Earth.
     */
    private const UNINHABITED = ['ATA', 'ATF', 'ATC', 'HMD', 'SGS', 'IOT', 'IOA', 'KAS'];

    /** @var array<string, mixed> */
    private array $cache = [];

    public function __construct(private readonly string $dataDir)
    {
    }

    // ---------------------------------------------------------------- datasets

    /** @return list<array<string, mixed>> */
    public function countries(): array
    {
        return $this->load('countries');
    }

    /** @return list<array<string, mixed>> */
    public function cities(): array
    {
        return $this->load('cities');
    }

    /** @return list<array<string, mixed>> */
    public function mountains(): array
    {
        return $this->load('mountains');
    }

    /** @return list<array<string, mixed>> */
    public function places(): array
    {
        return $this->load('places');
    }

    /** @return list<array<string, mixed>> */
    public function rivers(): array
    {
        return $this->load('rivers');
    }

    /** @return list<array<string, mixed>> */
    public function lakes(): array
    {
        return $this->load('lakes');
    }

    /** @return list<array<string, mixed>> */
    public function oceans(): array
    {
        return $this->load('oceans');
    }

    /** @return array<string, array<string, mixed>> */
    public function continents(): array
    {
        return $this->load('continents');
    }

    /** @return array<string, list<string>> */
    public function facts(): array
    {
        return $this->load('facts');
    }

    /** @return array<string, float> iso3 => average annual PM2.5, µg/m³ */
    public function pollutionIndex(): array
    {
        return $this->load('pollution');
    }

    /** @return array<string, float> iso3 => everyday-safety score out of 100 */
    public function safetyIndex(): array
    {
        return $this->load('safety');
    }

    /** @return array<string, float> iso3 => peacefulness score out of 100 */
    public function peaceIndex(): array
    {
        return $this->load('peace');
    }

    /** @return list<string> */
    public function factsFor(string $iso3): array
    {
        return $this->facts()[strtoupper($iso3)] ?? [];
    }

    // ---------------------------------------------------------------- geometry

    /** @return array<string, string> ISO3 => SVG path */
    public function countryPaths(): array
    {
        return $this->cache['geometry.countries'] ??= require "{$this->dataDir}/geometry/countries.php";
    }

    /** @return list<array<string, mixed>> */
    public function terrain(): array
    {
        return $this->cache['geometry.terrain'] ??= require "{$this->dataDir}/geometry/terrain.php";
    }

    // ----------------------------------------------------------------- lookups

    /** @return array<string, mixed>|null */
    public function country(string $iso3): ?array
    {
        foreach ($this->countries() as $country) {
            if (strcasecmp($country['iso3'], $iso3) === 0) {
                return $country;
            }
        }

        return null;
    }

    /** @return array<string, mixed>|null */
    public function continent(string $name): ?array
    {
        foreach ($this->continents() as $key => $continent) {
            if (strcasecmp($key, $name) === 0) {
                return $continent;
            }
        }

        return null;
    }

    /** @return list<array<string, mixed>> */
    public function countriesIn(string $continent): array
    {
        return array_values(array_filter(
            $this->countries(),
            static fn (array $c): bool => strcasecmp($c['continent'], $continent) === 0,
        ));
    }

    /** @return list<array<string, mixed>> */
    public function capitals(): array
    {
        return array_values(array_filter(
            $this->cities(),
            static fn (array $c): bool => $c['capital'] === true,
        ));
    }

    public function capitalOf(string $iso3): ?array
    {
        foreach ($this->cities() as $city) {
            if ($city['capital'] && strcasecmp($city['iso3'], $iso3) === 0) {
                return $city;
            }
        }

        return null;
    }

    /** @return list<array<string, mixed>> */
    public function citiesIn(string $iso3, int $limit = 8): array
    {
        $matches = array_values(array_filter(
            $this->cities(),
            static fn (array $c): bool => strcasecmp($c['iso3'], $iso3) === 0,
        ));

        return $limit > 0 ? array_slice($matches, 0, $limit) : $matches;
    }

    /** @return list<array<string, mixed>> */
    public function mountainsIn(string $iso3): array
    {
        return array_values(array_filter(
            $this->mountains(),
            static fn (array $m): bool => strcasecmp($m['iso3'], $iso3) === 0,
        ));
    }

    /** @return list<array<string, mixed>> */
    public function placesIn(string $iso3): array
    {
        return array_values(array_filter(
            $this->places(),
            static fn (array $p): bool => strcasecmp($p['iso3'], $iso3) === 0,
        ));
    }

    /**
     * Countries that share a land border, resolved from ISO3 codes to records.
     *
     * @return list<array<string, mixed>>
     */
    public function neighboursOf(array $country): array
    {
        $wanted = array_flip($country['borders'] ?? []);
        if ($wanted === []) {
            return [];
        }

        return array_values(array_filter(
            $this->countries(),
            static fn (array $c): bool => isset($wanted[$c['iso3']]),
        ));
    }

    /**
     * Where a country stands against every other mapped country, plus its
     * share of world totals, for the "By the numbers" panel on its page.
     *
     * @return array<string, mixed>
     */
    public function worldRanks(string $iso3): array
    {
        $countries = $this->countries();
        $total = count($countries);

        $ranks = [];
        foreach (['population', 'area', 'gdp', 'gdpPerCapita', 'density'] as $field) {
            $sorted = $countries;
            usort($sorted, static fn (array $a, array $b): int => $b[$field] <=> $a[$field]);
            foreach ($sorted as $index => $c) {
                if (strcasecmp($c['iso3'], $iso3) === 0) {
                    $ranks[$field] = $index + 1;
                    break;
                }
            }
        }

        $country = $this->country($iso3);
        $worldPopulation = array_sum(array_column($countries, 'population'));
        $worldArea = array_sum(array_column($countries, 'area'));

        return [
            'of' => $total,
            'ranks' => $ranks,
            'populationShare' => $country && $worldPopulation > 0 ? $country['population'] / $worldPopulation : 0.0,
            'areaShare' => $country && $worldArea > 0 ? $country['area'] / $worldArea : 0.0,
        ];
    }

    /**
     * All countries ranked by GDP per capita, richest first.
     *
     * @return list<array<string, mixed>>
     */
    public function richestCountries(): array
    {
        $countries = array_values(array_filter(
            $this->countries(),
            static fn (array $c): bool => $c['gdpPerCapita'] > 0 && !in_array($c['iso3'], self::UNINHABITED, true),
        ));

        usort($countries, static fn (array $a, array $b): int => $b['gdpPerCapita'] <=> $a['gdpPerCapita']);

        return $countries;
    }

    /**
     * Countries carrying a curated metric (pollution, safety, peace…), each
     * merged with its profile under $field and sorted with the highest value
     * first. Countries the metric has no entry for are left out.
     *
     * @param array<string, float> $metric iso3 => value
     * @return list<array<string, mixed>>
     */
    public function countriesWithMetric(array $metric, string $field): array
    {
        $countries = [];

        foreach ($this->countries() as $country) {
            if (!isset($metric[$country['iso3']])) {
                continue;
            }

            $countries[] = [...$country, $field => $metric[$country['iso3']]];
        }

        usort($countries, static fn (array $a, array $b): float => $b[$field] <=> $a[$field]);

        return $countries;
    }

    // ------------------------------------------------------------- aggregates

    /** @return array<string, int|float> */
    public function summary(): array
    {
        $countries = $this->countries();

        return [
            'countries' => count($countries),
            'population' => array_sum(array_column($countries, 'population')),
            'cities' => count($this->cities()),
            'capitals' => count($this->capitals()),
            'mountains' => count($this->mountains()),
            'places' => count($this->places()),
            'rivers' => count($this->rivers()),
            'lakes' => count($this->lakes()),
            'oceans' => count($this->oceans()),
            'facts' => count($this->facts()) * 10,
            'continents' => count($this->continents()),
            'timezones' => count(\DateTimeZone::listIdentifiers()),
            'land' => array_sum(array_column($this->continents(), 'area')),
        ];
    }

    /** @return array<string, array{countries: int, population: int, area: int}> */
    public function continentTotals(): array
    {
        $totals = [];

        foreach ($this->continents() as $key => $continent) {
            $members = $this->countriesIn($key);
            $totals[$key] = [
                'countries' => count($members),
                'population' => array_sum(array_column($members, 'population')),
                'area' => $continent['area'],
            ];
        }

        return $totals;
    }

    /**
     * Point data the browser needs to drive the map. Line and polygon geometry
     * is rendered server-side into the SVG instead, so this stays small.
     *
     * @return array<string, mixed>
     */
    public function mapPayload(): array
    {
        $countries = array_map(
            static fn (array $c): array => [
                'iso3' => $c['iso3'],
                'iso2' => $c['iso2'],
                'name' => $c['name'],
                'continent' => $c['continent'],
                'subregion' => $c['subregion'],
                'population' => $c['population'],
                'area' => $c['area'],
                'density' => $c['density'],
                'gdpPerCapita' => $c['gdpPerCapita'],
                'flag' => $c['flag'],
                'lon' => $c['lon'],
                'lat' => $c['lat'],
            ],
            $this->countries(),
        );

        $capitals = array_map(
            static fn (array $c): array => [
                'name' => $c['name'],
                'iso3' => $c['iso3'],
                'country' => $c['country'],
                'population' => $c['population'],
                'lat' => $c['lat'],
                'lon' => $c['lon'],
            ],
            $this->capitals(),
        );

        $bigCities = array_map(
            static fn (array $c): array => [
                'name' => $c['name'],
                'iso3' => $c['iso3'],
                'country' => $c['country'],
                'population' => $c['population'],
                'lat' => $c['lat'],
                'lon' => $c['lon'],
            ],
            array_slice(array_values(array_filter(
                $this->cities(),
                static fn (array $c): bool => !$c['capital'] && $c['population'] > 1_500_000,
            )), 0, 180),
        );

        $mountains = array_map(
            static fn (array $m): array => [
                'name' => $m['name'],
                'iso3' => $m['iso3'],
                'elevation' => $m['elevation'],
                'range' => $m['range'],
                'lat' => $m['lat'],
                'lon' => $m['lon'],
            ],
            $this->mountains(),
        );

        $places = array_map(
            static fn (array $p): array => [
                'name' => $p['name'],
                'iso3' => $p['iso3'],
                'country' => $p['country'],
                'category' => $p['category'],
                'lat' => $p['lat'],
                'lon' => $p['lon'],
            ],
            $this->places(),
        );

        $waterLabels = [];
        foreach ($this->oceans() as $ocean) {
            $waterLabels[] = [
                'name' => $ocean['label'],
                'kind' => $ocean['kind'],
                'rank' => $ocean['rank'],
                'lat' => $ocean['lat'],
                'lon' => $ocean['lon'],
            ];
        }

        $continents = [];
        foreach ($this->continents() as $key => $continent) {
            $continents[$key] = [
                'accent' => $continent['accent'],
                'accent2' => $continent['accent2'],
                'focus' => $continent['focus'],
            ];
        }

        return [
            'countries' => $countries,
            'capitals' => $capitals,
            'cities' => $bigCities,
            'mountains' => $mountains,
            'places' => $places,
            'water' => $waterLabels,
            'continents' => $continents,
        ];
    }

    private function load(string $name): mixed
    {
        return $this->cache[$name] ??= require "{$this->dataDir}/{$name}.php";
    }
}
