<?php

declare(strict_types=1);

/**
 * Worldly — front controller.
 *
 * Serve with:  php -S localhost:8000 -t public public/index.php
 */

// Let the built-in server hand back real files (css, js, images) untouched.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file)) {
        return false;
    }
}

require __DIR__ . '/../src/bootstrap.php';

use Worldly\Atlas;
use Worldly\Router;
use Worldly\Support\Format;
use Worldly\Support\Timezones;
use Worldly\View;

$atlas = new Atlas(__DIR__ . '/../src/Data');
$view = new View(__DIR__ . '/../src/View');

$view->share('atlas', $atlas);
$view->share('summary', $atlas->summary());
$view->share('assetVersion', ASSET_VERSION);

$router = new Router();

// ---------------------------------------------------------------------------
// Pages
// ---------------------------------------------------------------------------

$router->get('/', static fn (): string => $view->render('explore', [
    'title' => 'Worldly — an interactive atlas of everywhere',
    'nav' => 'explore',
    'description' => 'A live physical world map and spinning 3D globe with rivers, lakes, terrain, a real day-night terminator and ten facts for every country. Built in plain PHP, with no map tiles and no tracking.',
    'mapPayload' => $atlas->mapPayload(),
    'continents' => $atlas->continents(),
    'featuredZones' => array_slice(Timezones::featured(), 0, 8),
]));

$router->get('/continents', static fn (): string => $view->render('continents', [
    'title' => 'Continents — Worldly',
    'nav' => 'continents',
    'description' => 'All seven continents compared by land area, population and country count, each with its highest and lowest points and a map that flies to it.',
    'continents' => $atlas->continents(),
    'totals' => $atlas->continentTotals(),
    'mapPayload' => $atlas->mapPayload(),
]));

$router->get('/continent/{name}', static function (array $params) use ($atlas, $view): string {
    $continent = $atlas->continent(str_replace('-', ' ', $params['name']));
    if ($continent === null) {
        return $view->render('not-found', ['title' => 'Not found — Worldly', 'nav' => '', 'what' => 'continent']);
    }

    return $view->render('continent', [
        'title' => $continent['name'] . ' — Worldly',
        'nav' => 'continents',
        'description' => $continent['blurb'],
        'continent' => $continent,
        'countries' => $atlas->countriesIn($continent['name']),
        'mountains' => array_values(array_filter(
            $atlas->mountains(),
            static fn (array $m): bool => $m['continent'] === $continent['name'],
        )),
        'places' => array_values(array_filter(
            $atlas->places(),
            static fn (array $p): bool => $p['continent'] === $continent['name'],
        )),
        'mapPayload' => $atlas->mapPayload(),
    ]);
});

$router->get('/countries', static fn (): string => $view->render('countries', [
    'title' => 'Countries & regions — Worldly',
    'nav' => 'countries',
    'description' => 'Every country and territory on the map, filterable by continent and sortable by population, area or GDP per person. Each opens a full profile with ten facts.',
    'countries' => $atlas->countries(),
    'continents' => $atlas->continents(),
]));

$router->get('/country/{iso3}', static function (array $params) use ($atlas, $view): string {
    $country = $atlas->country($params['iso3']);
    if ($country === null) {
        return $view->render('not-found', ['title' => 'Not found — Worldly', 'nav' => '', 'what' => 'country']);
    }

    return $view->render('country', [
        'title' => $country['name'] . ' — 10 facts, map and profile — Worldly',
        'nav' => 'countries',
        'description' => sprintf(
            'Ten facts about %s: population %s, capital %s, in %s. Plus its map, languages, currency, time zones, largest cities and land neighbours.',
            $country['name'],
            Format::number($country['population']),
            $atlas->capitalOf($country['iso3'])['name'] ?? 'not recorded',
            $country['continent'],
        ),
        'country' => $country,
        'facts' => $atlas->factsFor($country['iso3']),
        'continent' => $atlas->continent($country['continent']),
        'capital' => $atlas->capitalOf($country['iso3']),
        'cities' => $atlas->citiesIn($country['iso3'], 10),
        'mountains' => $atlas->mountainsIn($country['iso3']),
        'places' => $atlas->placesIn($country['iso3']),
        'neighbours' => $atlas->neighboursOf($country),
        'mapPayload' => $atlas->mapPayload(),
    ]);
});

$router->get('/mountains', static fn (): string => $view->render('mountains', [
    'title' => 'Mountains — Worldly',
    'nav' => 'mountains',
    'description' => 'The highest ground on Earth drawn to scale, including all fourteen eight-thousanders and the Seven Summits, with elevation, prominence and first ascent for each.',
    'mountains' => $atlas->mountains(),
    'continents' => $atlas->continents(),
    'mapPayload' => $atlas->mapPayload(),
]));

$router->get('/waters', static fn (): string => $view->render('waters', [
    'title' => 'Rivers, lakes & oceans — Worldly',
    'nav' => 'waters',
    'description' => 'The world\'s great rivers, lakes and oceans drawn as real geometry on the map, with length, basin, discharge, area and depth, plus a to-scale ocean depth chart.',
    'rivers' => $atlas->rivers(),
    'lakes' => $atlas->lakes(),
    'oceans' => $atlas->oceans(),
    'mapPayload' => $atlas->mapPayload(),
]));

$router->get('/travel', static fn (): string => $view->render('travel', [
    'title' => 'Travel places — Worldly',
    'nav' => 'travel',
    'description' => 'Ancient wonders, reefs, deserts and cities worth crossing an ocean for, each pinned on the world map with the season that actually suits it.',
    'places' => $atlas->places(),
    'continents' => $atlas->continents(),
    'mapPayload' => $atlas->mapPayload(),
]));

$router->get('/compare', static fn (): string => $view->render('compare', [
    'title' => 'Compare countries — Worldly',
    'nav' => 'compare',
    'description' => 'Put any two countries side by side and compare population, area, density, GDP, borders, time zones, languages and currency, plus the distance between them.',
    'countries' => $atlas->countries(),
]));

$router->get('/quiz', static fn (): string => $view->render('quiz', [
    'title' => 'Atlas quiz — Worldly',
    'nav' => 'quiz',
    'description' => 'Test your geography: guess the flag, the capital, or find the country on the map. Eight questions, streaks, and a real fact after every answer.',
    'mapPayload' => $atlas->mapPayload(),
]));

$router->get('/bookmarks', static fn (): string => $view->render('bookmarks', [
    'title' => 'Your bookmarks — Worldly',
    'nav' => 'bookmarks',
    'description' => 'Countries, peaks, rivers, lakes and destinations you have starred, kept in this browser.',
]));

$router->get('/clocks', static fn (): string => $view->render('clocks', [
    'title' => 'World clock, timer & stopwatch — Worldly',
    'nav' => 'clocks',
    'description' => 'A wall of analog world clocks that tint with the local hour, plus a countdown timer that rings and a stopwatch with laps.',
    'zones' => Timezones::featured(),
    'wall' => Timezones::defaultWall(),
]));

$router->get('/converter', static fn (): string => $view->render('converter', [
    'title' => 'Time converter — Worldly',
    'nav' => 'converter',
    'description' => 'Convert any moment between any two time zones, with daylight saving handled automatically and the same instant shown across twelve cities.',
    'zones' => Timezones::featured(),
    'grouped' => Timezones::grouped(),
]));

// ---------------------------------------------------------------------------
// Search engine files
// ---------------------------------------------------------------------------

$sitemap = new Worldly\Sitemap($atlas, __DIR__ . '/../src/Data');

$router->get('/sitemap.xml', static fn (): array => [
    'status' => 200,
    'cache' => true,
    'contentType' => 'application/xml',
    'raw' => $sitemap->xml(),
]);

$router->get('/robots.txt', static fn (): array => [
    'status' => 200,
    'cache' => true,
    'contentType' => 'text/plain',
    'raw' => $sitemap->robots(),
]);

// ---------------------------------------------------------------------------
// JSON endpoints
// ---------------------------------------------------------------------------

/** Coarse lon/lat rings, fetched on demand by the canvas globe. */
$router->get('/api/globe', static function () use ($atlas): array {
    return ['status' => 200, 'cache' => true, 'body' => [
        'rings' => require __DIR__ . '/../src/Data/geometry/globe.php',
    ]];
});

$router->get('/api/country/{iso3}', static function (array $params) use ($atlas): array {
    $country = $atlas->country($params['iso3']);
    if ($country === null) {
        return ['status' => 404, 'body' => ['error' => 'Unknown country']];
    }

    return ['status' => 200, 'body' => [
        'country' => $country,
        'facts' => $atlas->factsFor($country['iso3']),
        'capital' => $atlas->capitalOf($country['iso3']),
        'continent' => $atlas->continent($country['continent']),
        'mountains' => $atlas->mountainsIn($country['iso3']),
        'places' => $atlas->placesIn($country['iso3']),
        'cities' => $atlas->citiesIn($country['iso3'], 5),
        'neighbours' => array_map(
            static fn (array $c): array => ['iso3' => $c['iso3'], 'name' => $c['name'], 'flag' => $c['flag']],
            $atlas->neighboursOf($country),
        ),
    ]];
});

/** Bulk lookup used by the bookmarks page to rehydrate saved ids. */
$router->get('/api/bookmarks', static function () use ($atlas): array {
    $ids = array_filter(explode(',', (string) ($_GET['ids'] ?? '')));
    $items = [];

    foreach ($ids as $id) {
        [$type, $key] = array_pad(explode(':', $id, 2), 2, '');

        if ($type === 'country') {
            $country = $atlas->country($key);
            if ($country) {
                $items[] = [
                    'id' => $id, 'type' => 'country', 'title' => $country['flag'] . ' ' . $country['name'],
                    'detail' => $country['continent'] . ' · ' . number_format($country['population']) . ' people',
                    'href' => '/country/' . $country['iso3'], 'lon' => $country['lon'], 'lat' => $country['lat'],
                ];
            }
        } elseif ($type === 'mountain') {
            foreach ($atlas->mountains() as $peak) {
                if (Format::slug($peak['name']) === $key) {
                    $items[] = [
                        'id' => $id, 'type' => 'mountain', 'title' => '🏔 ' . $peak['name'],
                        'detail' => $peak['range'] . ' · ' . number_format($peak['elevation']) . ' m',
                        'href' => '/mountains#' . $key, 'lon' => $peak['lon'], 'lat' => $peak['lat'],
                    ];
                }
            }
        } elseif ($type === 'place') {
            foreach ($atlas->places() as $place) {
                if (Format::slug($place['name']) === $key) {
                    $items[] = [
                        'id' => $id, 'type' => 'place', 'title' => '📍 ' . $place['name'],
                        'detail' => $place['country'] . ' · ' . $place['category'],
                        'href' => '/travel#' . $key, 'lon' => $place['lon'], 'lat' => $place['lat'],
                    ];
                }
            }
        } elseif ($type === 'river' || $type === 'lake') {
            $source = $type === 'river' ? $atlas->rivers() : $atlas->lakes();
            foreach ($source as $water) {
                if (Format::slug($water['name']) === $key) {
                    $items[] = [
                        'id' => $id, 'type' => $type,
                        'title' => ($type === 'river' ? '🏞 ' : '💧 ') . $water['name'],
                        'detail' => $type === 'river'
                            ? ($water['length'] ? number_format($water['length']) . ' km' : 'River')
                            : ($water['area'] ? number_format($water['area']) . ' km²' : 'Lake'),
                        'href' => '/waters#' . $key, 'lon' => $water['lon'], 'lat' => $water['lat'],
                    ];
                }
            }
        }
    }

    return ['status' => 200, 'body' => ['items' => $items]];
});

/** Question bank for the quiz, built fresh each request. */
$router->get('/api/quiz', static function () use ($atlas): array {
    $mode = (string) ($_GET['mode'] ?? 'flag');
    $pool = array_values(array_filter(
        $atlas->countries(),
        static fn (array $c): bool => $c['population'] > 300000 && $c['iso2'] !== '' && $c['unMember'],
    ));

    shuffle($pool);
    $questions = [];

    foreach (array_slice($pool, 0, 10) as $answer) {
        $decoys = array_values(array_filter(
            $pool,
            static fn (array $c): bool => $c['iso3'] !== $answer['iso3'] && $c['continent'] === $answer['continent'],
        ));
        shuffle($decoys);
        $decoys = array_slice($decoys, 0, 3);

        while (count($decoys) < 3) {
            $candidate = $pool[array_rand($pool)];
            if ($candidate['iso3'] !== $answer['iso3']) {
                $decoys[] = $candidate;
            }
        }

        $options = array_map(static fn (array $c): array => ['iso3' => $c['iso3'], 'label' => $c['name']], $decoys);
        $options[] = ['iso3' => $answer['iso3'], 'label' => $answer['name']];
        shuffle($options);

        $capital = $atlas->capitalOf($answer['iso3']);

        $prompt = match ($mode) {
            'capital' => $capital ? 'Which country has ' . $capital['name'] . ' as its capital?' : null,
            'map' => 'Which country is highlighted on the map?',
            default => 'Which country flies this flag?',
        };

        if ($prompt === null) {
            continue;
        }

        $questions[] = [
            'mode' => $mode,
            'prompt' => $prompt,
            'flag' => $answer['flag'],
            'iso3' => $answer['iso3'],
            'lon' => $answer['lon'],
            'lat' => $answer['lat'],
            'options' => $options,
            'fact' => $atlas->factsFor($answer['iso3'])[0] ?? '',
        ];
    }

    return ['status' => 200, 'body' => ['questions' => array_slice($questions, 0, 8)]];
});

$router->get('/api/time/{zone:.+}', static function (array $params): array {
    try {
        $tz = new DateTimeZone($params['zone']);
    } catch (Exception) {
        return ['status' => 404, 'body' => ['error' => 'Unknown timezone']];
    }

    $now = new DateTimeImmutable('now', $tz);

    return ['status' => 200, 'body' => [
        'zone' => $params['zone'],
        'iso' => $now->format(DateTimeInterface::ATOM),
        'offset' => Format::offset($params['zone']),
        'abbr' => $now->format('T'),
        'dst' => (bool) $now->format('I'),
    ]];
});

$router->get('/api/convert', static function (): array {
    $from = (string) ($_GET['from'] ?? 'UTC');
    $to = (string) ($_GET['to'] ?? 'UTC');
    $when = (string) ($_GET['at'] ?? 'now');

    try {
        $source = new DateTimeImmutable($when, new DateTimeZone($from));
        $target = $source->setTimezone(new DateTimeZone($to));
    } catch (Exception $e) {
        return ['status' => 400, 'body' => ['error' => 'Could not convert: ' . $e->getMessage()]];
    }

    return ['status' => 200, 'body' => [
        'from' => ['zone' => $from, 'iso' => $source->format(DateTimeInterface::ATOM), 'label' => $source->format('D, j M Y · H:i')],
        'to' => ['zone' => $to, 'iso' => $target->format(DateTimeInterface::ATOM), 'label' => $target->format('D, j M Y · H:i')],
        'differenceHours' => round(($target->getOffset() - $source->getOffset()) / 3600, 2),
    ]];
});

$router->get('/api/search', static function () use ($atlas): array {
    $query = trim((string) ($_GET['q'] ?? ''));
    if ($query === '') {
        return ['status' => 200, 'body' => ['results' => []]];
    }

    $needle = mb_strtolower($query);
    $results = [];

    $push = static function (array $item) use (&$results): void {
        $results[] = $item;
    };

    foreach ($atlas->countries() as $country) {
        if (str_contains(mb_strtolower($country['name']), $needle)) {
            $push(['type' => 'country', 'label' => $country['flag'] . ' ' . $country['name'], 'detail' => $country['continent'], 'href' => '/country/' . $country['iso3'], 'lat' => $country['lat'], 'lon' => $country['lon']]);
        }
    }

    foreach ($atlas->cities() as $city) {
        if (str_contains(mb_strtolower($city['name']), $needle)) {
            $push(['type' => 'city', 'label' => '🏙 ' . $city['name'], 'detail' => $city['country'], 'href' => '/country/' . $city['iso3'], 'lat' => $city['lat'], 'lon' => $city['lon']]);
        }
    }

    foreach ($atlas->mountains() as $mountain) {
        if (str_contains(mb_strtolower($mountain['name']), $needle)) {
            $push(['type' => 'mountain', 'label' => '🏔 ' . $mountain['name'], 'detail' => Format::number($mountain['elevation']) . ' m', 'href' => '/mountains#' . Format::slug($mountain['name']), 'lat' => $mountain['lat'], 'lon' => $mountain['lon']]);
        }
    }

    foreach ($atlas->rivers() as $river) {
        if (str_contains(mb_strtolower($river['name']), $needle)) {
            $push(['type' => 'river', 'label' => '🏞 ' . $river['name'], 'detail' => $river['length'] ? Format::number($river['length']) . ' km' : 'River', 'href' => '/waters#' . Format::slug($river['name']), 'lat' => $river['lat'], 'lon' => $river['lon']]);
        }
    }

    foreach ($atlas->lakes() as $lake) {
        if (str_contains(mb_strtolower($lake['name']), $needle)) {
            $push(['type' => 'lake', 'label' => '💧 ' . $lake['name'], 'detail' => $lake['area'] ? Format::number($lake['area']) . ' km²' : 'Lake', 'href' => '/waters#' . Format::slug($lake['name']), 'lat' => $lake['lat'], 'lon' => $lake['lon']]);
        }
    }

    foreach ($atlas->places() as $place) {
        if (str_contains(mb_strtolower($place['name']), $needle)) {
            $push(['type' => 'place', 'label' => '📍 ' . $place['name'], 'detail' => $place['country'], 'href' => '/travel#' . Format::slug($place['name']), 'lat' => $place['lat'], 'lon' => $place['lon']]);
        }
    }

    return ['status' => 200, 'body' => ['results' => array_slice($results, 0, 14)]];
});

$router->dispatch(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/',
    static fn (): string => $view->render('not-found', ['title' => 'Not found — Worldly', 'nav' => '', 'what' => 'page']),
);
