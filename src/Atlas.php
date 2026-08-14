<?php

declare(strict_types=1);

namespace Worldly;

/**
 * In-memory read model over the generated and curated datasets.
 *
 * Everything is loaded lazily and memoised, so a request that only renders the
 * clock page never touches the 140 KB of country geometry.
 */
final class Atlas
{
    /** @var array<string, mixed> */
    private array $cache = [];

    public function __construct(private readonly string $dataDir)
    {
    }

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

    /** @return array<string, array<string, mixed>> */
    public function continents(): array
    {
        return $this->load('continents');
    }

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

    /** Capital city record for a country, if we have one. */
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
     * Aggregate headline numbers used by the hero counters.
     *
     * @return array<string, int|float>
     */
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
            'continents' => count($this->continents()),
            'timezones' => count(\DateTimeZone::listIdentifiers()),
            'land' => array_sum(array_column($this->continents(), 'area')),
        ];
    }

    /**
     * Continent roll-up: country count, population and land area.
     *
     * @return array<string, array{countries: int, population: int, area: int}>
     */
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
     * Everything the browser needs to drive the interactive map.
     *
     * Geometry is kept separate from the profile fields so the client can build
     * a lightweight search index without carrying the path strings around.
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
            'mountains' => $mountains,
            'places' => $places,
            'continents' => $continents,
        ];
    }

    /** @return mixed */
    private function load(string $name)
    {
        return $this->cache[$name] ??= require "{$this->dataDir}/{$name}.php";
    }
}
