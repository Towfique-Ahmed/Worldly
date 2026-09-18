<?php

declare(strict_types=1);

namespace Worldly\Support;

final class Timezones
{
    /**
     * Hand-picked zones for the default world clock wall and the converter's
     * quick-pick row. Each carries the coordinates so the map can pin it.
     */
    private const FEATURED = [
        ['zone' => 'Pacific/Auckland', 'city' => 'Auckland', 'country' => 'New Zealand', 'flag' => '🇳🇿', 'lat' => -36.85, 'lon' => 174.76],
        ['zone' => 'Australia/Sydney', 'city' => 'Sydney', 'country' => 'Australia', 'flag' => '🇦🇺', 'lat' => -33.87, 'lon' => 151.21],
        ['zone' => 'Asia/Tokyo', 'city' => 'Tokyo', 'country' => 'Japan', 'flag' => '🇯🇵', 'lat' => 35.69, 'lon' => 139.69],
        ['zone' => 'Asia/Seoul', 'city' => 'Seoul', 'country' => 'South Korea', 'flag' => '🇰🇷', 'lat' => 37.57, 'lon' => 126.98],
        ['zone' => 'Asia/Shanghai', 'city' => 'Shanghai', 'country' => 'China', 'flag' => '🇨🇳', 'lat' => 31.23, 'lon' => 121.47],
        ['zone' => 'Asia/Singapore', 'city' => 'Singapore', 'country' => 'Singapore', 'flag' => '🇸🇬', 'lat' => 1.35, 'lon' => 103.82],
        ['zone' => 'Asia/Jakarta', 'city' => 'Jakarta', 'country' => 'Indonesia', 'flag' => '🇮🇩', 'lat' => -6.21, 'lon' => 106.85],
        ['zone' => 'Asia/Bangkok', 'city' => 'Bangkok', 'country' => 'Thailand', 'flag' => '🇹🇭', 'lat' => 13.76, 'lon' => 100.50],
        ['zone' => 'Asia/Dhaka', 'city' => 'Dhaka', 'country' => 'Bangladesh', 'flag' => '🇧🇩', 'lat' => 23.81, 'lon' => 90.41],
        ['zone' => 'Asia/Kolkata', 'city' => 'Mumbai', 'country' => 'India', 'flag' => '🇮🇳', 'lat' => 19.08, 'lon' => 72.88],
        ['zone' => 'Asia/Karachi', 'city' => 'Karachi', 'country' => 'Pakistan', 'flag' => '🇵🇰', 'lat' => 24.86, 'lon' => 67.01],
        ['zone' => 'Asia/Dubai', 'city' => 'Dubai', 'country' => 'United Arab Emirates', 'flag' => '🇦🇪', 'lat' => 25.20, 'lon' => 55.27],
        ['zone' => 'Europe/Moscow', 'city' => 'Moscow', 'country' => 'Russia', 'flag' => '🇷🇺', 'lat' => 55.76, 'lon' => 37.62],
        ['zone' => 'Africa/Nairobi', 'city' => 'Nairobi', 'country' => 'Kenya', 'flag' => '🇰🇪', 'lat' => -1.29, 'lon' => 36.82],
        ['zone' => 'Europe/Istanbul', 'city' => 'Istanbul', 'country' => 'Turkey', 'flag' => '🇹🇷', 'lat' => 41.01, 'lon' => 28.98],
        ['zone' => 'Africa/Cairo', 'city' => 'Cairo', 'country' => 'Egypt', 'flag' => '🇪🇬', 'lat' => 30.04, 'lon' => 31.24],
        ['zone' => 'Europe/Berlin', 'city' => 'Berlin', 'country' => 'Germany', 'flag' => '🇩🇪', 'lat' => 52.52, 'lon' => 13.40],
        ['zone' => 'Europe/Paris', 'city' => 'Paris', 'country' => 'France', 'flag' => '🇫🇷', 'lat' => 48.86, 'lon' => 2.35],
        ['zone' => 'Africa/Lagos', 'city' => 'Lagos', 'country' => 'Nigeria', 'flag' => '🇳🇬', 'lat' => 6.52, 'lon' => 3.38],
        ['zone' => 'Europe/London', 'city' => 'London', 'country' => 'United Kingdom', 'flag' => '🇬🇧', 'lat' => 51.51, 'lon' => -0.13],
        ['zone' => 'UTC', 'city' => 'UTC', 'country' => 'Coordinated Universal Time', 'flag' => '🌐', 'lat' => 0.0, 'lon' => 0.0],
        ['zone' => 'America/Sao_Paulo', 'city' => 'São Paulo', 'country' => 'Brazil', 'flag' => '🇧🇷', 'lat' => -23.55, 'lon' => -46.63],
        ['zone' => 'America/New_York', 'city' => 'New York', 'country' => 'United States', 'flag' => '🇺🇸', 'lat' => 40.71, 'lon' => -74.01],
        ['zone' => 'America/Toronto', 'city' => 'Toronto', 'country' => 'Canada', 'flag' => '🇨🇦', 'lat' => 43.65, 'lon' => -79.38],
        ['zone' => 'America/Mexico_City', 'city' => 'Mexico City', 'country' => 'Mexico', 'flag' => '🇲🇽', 'lat' => 19.43, 'lon' => -99.13],
        ['zone' => 'America/Chicago', 'city' => 'Chicago', 'country' => 'United States', 'flag' => '🇺🇸', 'lat' => 41.88, 'lon' => -87.63],
        ['zone' => 'America/Denver', 'city' => 'Denver', 'country' => 'United States', 'flag' => '🇺🇸', 'lat' => 39.74, 'lon' => -104.99],
        ['zone' => 'America/Los_Angeles', 'city' => 'Los Angeles', 'country' => 'United States', 'flag' => '🇺🇸', 'lat' => 34.05, 'lon' => -118.24],
        ['zone' => 'America/Anchorage', 'city' => 'Anchorage', 'country' => 'United States', 'flag' => '🇺🇸', 'lat' => 61.22, 'lon' => -149.90],
        ['zone' => 'Pacific/Honolulu', 'city' => 'Honolulu', 'country' => 'United States', 'flag' => '🇺🇸', 'lat' => 21.31, 'lon' => -157.86],
    ];

    /** Zones shown on the clock wall before the visitor customises it. */
    private const DEFAULT_WALL = [
        'Asia/Dhaka',
        'Europe/London',
        'America/New_York',
        'Asia/Tokyo',
        'Europe/Berlin',
        'Australia/Sydney',
    ];

    /**
     * Featured zones with their current offset, ordered east to west.
     *
     * @return list<array<string, mixed>>
     */
    public static function featured(): array
    {
        $now = new \DateTimeImmutable('now');

        $zones = array_map(static function (array $entry) use ($now): array {
            $tz = new \DateTimeZone($entry['zone']);
            $entry['offsetSeconds'] = $tz->getOffset($now->setTimezone($tz));
            $entry['offset'] = Format::offset($entry['zone'], $now);
            $entry['abbr'] = $now->setTimezone($tz)->format('T');

            return $entry;
        }, self::FEATURED);

        usort($zones, static fn (array $a, array $b): int => $b['offsetSeconds'] <=> $a['offsetSeconds']);

        return $zones;
    }

    /**
     * IANA identifiers whose offset from UTC is exactly $seconds right now,
     * with the abbreviation each currently uses - the "zones observing GMT"
     * style reference table.
     *
     * @return list<array{zone: string, abbr: string}>
     */
    public static function atOffset(int $seconds = 0): array
    {
        $now = new \DateTimeImmutable('now');
        $matches = [];

        foreach (\DateTimeZone::listIdentifiers() as $identifier) {
            $tz = new \DateTimeZone($identifier);
            $moment = $now->setTimezone($tz);

            if ($tz->getOffset($moment) === $seconds) {
                $matches[] = ['zone' => $identifier, 'abbr' => $moment->format('T')];
            }
        }

        return $matches;
    }

    /** @return list<string> */
    public static function defaultWall(): array
    {
        return self::DEFAULT_WALL;
    }

    /**
     * Every IANA identifier grouped by region, for the converter's select menus.
     *
     * @return array<string, list<array{zone: string, label: string, offset: string}>>
     */
    public static function grouped(): array
    {
        $now = new \DateTimeImmutable('now');
        $grouped = [];

        foreach (\DateTimeZone::listIdentifiers() as $identifier) {
            $parts = explode('/', $identifier, 2);
            $region = count($parts) === 2 ? $parts[0] : 'Other';
            $label = str_replace('_', ' ', $parts[1] ?? $parts[0]);

            $grouped[$region][] = [
                'zone' => $identifier,
                'label' => $label,
                'offset' => Format::offset($identifier, $now),
            ];
        }

        ksort($grouped);

        return $grouped;
    }
}
