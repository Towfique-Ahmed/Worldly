<?php

declare(strict_types=1);

namespace Worldly\Support;

/**
 * Meta titles and descriptions, one set per page.
 *
 * Search results truncate on pixel width rather than characters, but character
 * budgets are a reliable proxy: roughly 60 for a title and 160 for a
 * description before Google starts cutting. Everything here is built to sit
 * inside those, front-load the words people actually search for, and stay
 * unique across every page — `php tools/audit_seo.php` proves it.
 */
final class Seo
{
    public const BRAND = 'Worldly';

    public const TITLE_MAX = 60;
    public const TITLE_MIN = 30;
    public const DESCRIPTION_MAX = 160;
    public const DESCRIPTION_MIN = 110;

    /**
     * Static page metadata, keyed by route.
     *
     * @var array<string, array{title: string, description: string}>
     */
    private const PAGES = [
        'explore' => [
            'title' => 'Interactive World Map & 3D Globe Atlas',
            'description' => 'Explore a live physical world map and spinning 3D globe with rivers, lakes, terrain and real-time day and night. Click any country for ten facts about it.',
        ],
        'continents' => [
            'title' => 'The 7 Continents: Size, Population & Facts',
            'description' => 'Compare all seven continents by land area, population and country count. See the highest and lowest point on each, and fly the map straight to any of them.',
        ],
        'countries' => [
            'title' => 'All Countries of the World: Population & Area',
            'description' => 'Browse every country and territory on Earth, sorted by population, land area or GDP per person, and filtered by continent. Each opens a profile with ten facts.',
        ],
        'mountains' => [
            'title' => 'Highest Mountains in the World, Drawn to Scale',
            'description' => 'All fourteen eight-thousanders and the Seven Summits, drawn to scale on one chart. Compare elevation, prominence, mountain range and first ascent for 46 peaks.',
        ],
        'waters' => [
            'title' => 'Longest Rivers, Biggest Lakes & Deepest Oceans',
            'description' => 'The longest rivers, largest lakes and deepest oceans on Earth, drawn as real geometry on the world map, with length, basin, area and depth for each of them.',
        ],
        'travel' => [
            'title' => 'Best Places to Visit in the World, by Season',
            'description' => 'Sixty-eight destinations worth crossing an ocean for: ancient wonders, reefs, deserts and cities, each pinned on the map with the season that suits it.',
        ],
        'compare' => [
            'title' => 'Compare Two Countries Side by Side',
            'description' => 'Put any two countries head to head and compare population, land area, density, GDP, borders, time zones, languages and currency, plus the distance between them.',
        ],
        'quiz' => [
            'title' => 'World Geography Quiz: Flags, Capitals & Map',
            'description' => 'Test your geography in three modes: name the flag, name the capital, or find the country on the world map. Eight questions, streaks, and a real fact each time.',
        ],
        'clocks' => [
            'title' => 'World Clock, Countdown Timer & Stopwatch',
            'description' => 'Live analog clocks for cities worldwide that tint with the local hour, plus a countdown timer that rings and a stopwatch with laps. Build your own clock wall.',
        ],
        'converter' => [
            'title' => 'Time Zone Converter: Any City, Any Date',
            'description' => 'Convert any date and time between any two time zones on Earth. Daylight saving is handled automatically, and you see the same moment across twelve cities.',
        ],
        'bookmarks' => [
            'title' => 'Your Saved Places & Bookmarks',
            'description' => 'The countries, mountains, rivers, lakes and destinations you have starred, kept privately in this browser and never sent anywhere.',
        ],
        'not-found' => [
            'title' => 'Page Not Found — Off the Edge of the Map',
            'description' => 'That page is off the edge of the map. Head back to the interactive world atlas to explore countries, mountains, rivers and travel destinations.',
        ],
    ];

    /**
     * Title and description for a static page.
     *
     * @return array{title: string, description: string}
     */
    public static function page(string $key): array
    {
        $page = self::PAGES[$key] ?? self::PAGES['explore'];

        return [
            'title' => self::brandedTitle($page['title']),
            'description' => $page['description'],
        ];
    }

    /**
     * Title and description for one country, built from its own figures so all
     * 242 differ from each other.
     *
     * @param array<string, mixed>      $country
     * @param array<string, mixed>|null $capital
     * @return array{title: string, description: string}
     */
    public static function country(array $country, ?array $capital): array
    {
        $name = (string) $country['name'];
        $capitalName = $capital['name'] ?? null;

        // Try the richest title first and fall back until one fits.
        $title = self::firstThatFits([
            $name . ': 10 Facts, Capital, Population & Map',
            $name . ': 10 Facts, Population & Map',
            $name . ': Facts, Population & Map',
            $name . ' Facts & Map',
        ], self::TITLE_MAX);

        $lead = $capitalName !== null
            ? sprintf('%s in %s: capital %s, population %s', $name, $country['continent'], $capitalName, self::people((int) $country['population']))
            : sprintf('%s in %s: population %s', $name, $country['continent'], self::people((int) $country['population']));

        if ($country['area'] > 0) {
            $lead .= sprintf(', %s km²', Format::number((int) $country['area']));
        }

        $description = self::firstThatFits([
            $lead . '. Ten facts, an interactive map, languages, currency, time zones and neighbouring countries.',
            $lead . '. Ten facts, an interactive map, languages, currency and local time.',
            $lead . '. Ten facts, a map, languages, currency and local time.',
            $lead . '. Ten facts, a map and local time.',
            $lead . '.',
        ], self::DESCRIPTION_MAX);

        return [
            'title' => self::brandedTitle($title),
            'description' => self::clamp($description, self::DESCRIPTION_MAX),
        ];
    }

    /**
     * @param array<string, mixed> $continent
     * @return array{title: string, description: string}
     */
    public static function continent(array $continent, int $countryCount, int $population): array
    {
        $name = (string) $continent['name'];

        $title = self::firstThatFits([
            $name . ': Countries, Size, Population & Facts',
            $name . ': Countries, Population & Facts',
            $name . ': Countries & Facts',
        ], self::TITLE_MAX);

        $lead = $countryCount > 0
            ? sprintf(
                '%s at a glance: %d countries, %s people, %s km² of land',
                $name,
                $countryCount,
                self::people($population),
                Format::compact((int) $continent['area']),
            )
            : sprintf('%s at a glance: %s km² of land', $name, Format::compact((int) $continent['area']));

        $description = self::firstThatFits([
            $lead . '. See its highest and lowest points, peaks and places to visit.',
            $lead . '. See its highest and lowest points and places to visit.',
            $lead . '. See its highest and lowest points.',
            $lead . '.',
        ], self::DESCRIPTION_MAX);

        return [
            'title' => self::brandedTitle($title),
            'description' => self::clamp($description, self::DESCRIPTION_MAX),
        ];
    }

    /** Append the brand only when there is room for it. */
    public static function brandedTitle(string $core): string
    {
        $branded = $core . ' | ' . self::BRAND;

        return strlen($branded) <= self::TITLE_MAX ? $branded : self::clamp($core, self::TITLE_MAX);
    }

    /**
     * "1.4 billion", "163 million", "5.6 million", "892,000" — friendlier in a
     * search snippet than either raw digits or a terse 163M.
     */
    public static function people(int $population): string
    {
        if ($population >= 1_000_000_000) {
            return rtrim(rtrim(number_format($population / 1_000_000_000, 2, '.', ''), '0'), '.') . ' billion';
        }

        if ($population >= 1_000_000) {
            $millions = $population / 1_000_000;
            return rtrim(rtrim(number_format($millions, $millions < 10 ? 1 : 0, '.', ''), '0'), '.') . ' million';
        }

        return Format::number($population);
    }

    /** Truncate on a word boundary, with an ellipsis only when text was cut. */
    public static function clamp(string $text, int $max): string
    {
        if (strlen($text) <= $max) {
            return $text;
        }

        $cut = substr($text, 0, $max - 1);
        $lastSpace = strrpos($cut, ' ');

        if ($lastSpace !== false && $lastSpace > $max * 0.6) {
            $cut = substr($cut, 0, $lastSpace);
        }

        return rtrim($cut, " ,.;:-—") . '…';
    }

    /**
     * The first candidate inside the budget, or the last one clamped.
     *
     * @param list<string> $candidates
     */
    private static function firstThatFits(array $candidates, int $max): string
    {
        foreach ($candidates as $candidate) {
            if (strlen($candidate) <= $max) {
                return $candidate;
            }
        }

        return self::clamp(end($candidates) ?: '', $max);
    }
}
