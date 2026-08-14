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
    'mapPayload' => $atlas->mapPayload(),
    'continents' => $atlas->continents(),
    'featuredZones' => array_slice(Timezones::featured(), 0, 8),
]));

$router->get('/continents', static fn (): string => $view->render('continents', [
    'title' => 'Continents — Worldly',
    'nav' => 'continents',
    'continents' => $atlas->continents(),
    'totals' => $atlas->continentTotals(),
    'mapPayload' => $atlas->mapPayload(),
]));

$router->get('/continent/{name}', static function (array $params) use ($atlas, $view): string {
    $name = str_replace('-', ' ', $params['name']);
    $continent = $atlas->continent($name);
    if ($continent === null) {
        return $view->render('not-found', ['title' => 'Not found — Worldly', 'nav' => '', 'what' => 'continent']);
    }

    return $view->render('continent', [
        'title' => $continent['name'] . ' — Worldly',
        'nav' => 'continents',
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
    'countries' => $atlas->countries(),
    'continents' => $atlas->continents(),
]));

$router->get('/country/{iso3}', static function (array $params) use ($atlas, $view): string {
    $country = $atlas->country($params['iso3']);
    if ($country === null) {
        return $view->render('not-found', ['title' => 'Not found — Worldly', 'nav' => '', 'what' => 'country']);
    }

    return $view->render('country', [
        'title' => $country['name'] . ' — Worldly',
        'nav' => 'countries',
        'country' => $country,
        'continent' => $atlas->continent($country['continent']),
        'capital' => $atlas->capitalOf($country['iso3']),
        'cities' => array_slice(array_values(array_filter(
            $atlas->cities(),
            static fn (array $c): bool => strcasecmp($c['iso3'], $country['iso3']) === 0,
        )), 0, 8),
        'mountains' => $atlas->mountainsIn($country['iso3']),
        'places' => $atlas->placesIn($country['iso3']),
        'neighbours' => array_slice(array_values(array_filter(
            $atlas->countriesIn($country['continent']),
            static fn (array $c): bool => $c['iso3'] !== $country['iso3'] && $c['subregion'] === $country['subregion'],
        )), 0, 8),
        'mapPayload' => $atlas->mapPayload(),
    ]);
});

$router->get('/mountains', static fn (): string => $view->render('mountains', [
    'title' => 'Mountains — Worldly',
    'nav' => 'mountains',
    'mountains' => $atlas->mountains(),
    'continents' => $atlas->continents(),
    'mapPayload' => $atlas->mapPayload(),
]));

$router->get('/travel', static fn (): string => $view->render('travel', [
    'title' => 'Travel places — Worldly',
    'nav' => 'travel',
    'places' => $atlas->places(),
    'continents' => $atlas->continents(),
    'mapPayload' => $atlas->mapPayload(),
]));

$router->get('/clocks', static fn (): string => $view->render('clocks', [
    'title' => 'World clock, timer & stopwatch — Worldly',
    'nav' => 'clocks',
    'zones' => Timezones::featured(),
    'wall' => Timezones::defaultWall(),
]));

$router->get('/converter', static fn (): string => $view->render('converter', [
    'title' => 'Time converter — Worldly',
    'nav' => 'converter',
    'zones' => Timezones::featured(),
    'grouped' => Timezones::grouped(),
]));

// ---------------------------------------------------------------------------
// JSON endpoints
// ---------------------------------------------------------------------------

$router->get('/api/country/{iso3}', static function (array $params) use ($atlas): array {
    $country = $atlas->country($params['iso3']);
    if ($country === null) {
        return ['status' => 404, 'body' => ['error' => 'Unknown country']];
    }

    $capital = $atlas->capitalOf($country['iso3']);
    unset($country['path']);

    return ['status' => 200, 'body' => [
        'country' => $country,
        'capital' => $capital,
        'continent' => $atlas->continent($country['continent']),
        'mountains' => $atlas->mountainsIn($country['iso3']),
        'places' => $atlas->placesIn($country['iso3']),
        'cities' => array_slice(array_values(array_filter(
            $atlas->cities(),
            static fn (array $c): bool => strcasecmp($c['iso3'], $country['iso3']) === 0,
        )), 0, 5),
    ]];
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

$router->get('/api/convert', static function () use ($atlas): array {
    $from = (string) ($_GET['from'] ?? 'UTC');
    $to = (string) ($_GET['to'] ?? 'UTC');
    $when = (string) ($_GET['at'] ?? 'now');

    try {
        $source = new DateTimeImmutable($when, new DateTimeZone($from));
        $target = $source->setTimezone(new DateTimeZone($to));
    } catch (Exception $e) {
        return ['status' => 400, 'body' => ['error' => 'Could not convert: ' . $e->getMessage()]];
    }

    $difference = ($target->getOffset() - $source->getOffset()) / 3600;

    return ['status' => 200, 'body' => [
        'from' => ['zone' => $from, 'iso' => $source->format(DateTimeInterface::ATOM), 'label' => $source->format('D, j M Y · H:i'), 'abbr' => $source->format('T')],
        'to' => ['zone' => $to, 'iso' => $target->format(DateTimeInterface::ATOM), 'label' => $target->format('D, j M Y · H:i'), 'abbr' => $target->format('T')],
        'differenceHours' => round($difference, 2),
        'dayShift' => (int) $target->format('z') - (int) $source->format('z'),
    ]];
});

$router->get('/api/search', static function () use ($atlas): array {
    $query = trim((string) ($_GET['q' ] ?? ''));
    if ($query === '') {
        return ['status' => 200, 'body' => ['results' => []]];
    }

    $needle = mb_strtolower($query);
    $results = [];

    foreach ($atlas->countries() as $country) {
        if (str_contains(mb_strtolower($country['name']), $needle)) {
            $results[] = ['type' => 'country', 'label' => $country['flag'] . ' ' . $country['name'], 'detail' => $country['continent'], 'href' => '/country/' . $country['iso3'], 'lat' => $country['lat'], 'lon' => $country['lon']];
        }
    }

    foreach ($atlas->cities() as $city) {
        if (str_contains(mb_strtolower($city['name']), $needle)) {
            $results[] = ['type' => 'city', 'label' => $city['name'], 'detail' => $city['country'], 'href' => '/country/' . $city['iso3'], 'lat' => $city['lat'], 'lon' => $city['lon']];
        }
    }

    foreach ($atlas->mountains() as $mountain) {
        if (str_contains(mb_strtolower($mountain['name']), $needle)) {
            $results[] = ['type' => 'mountain', 'label' => $mountain['name'], 'detail' => Format::number($mountain['elevation']) . ' m', 'href' => '/mountains#' . Format::slug($mountain['name']), 'lat' => $mountain['lat'], 'lon' => $mountain['lon']];
        }
    }

    foreach ($atlas->places() as $place) {
        if (str_contains(mb_strtolower($place['name']), $needle)) {
            $results[] = ['type' => 'place', 'label' => $place['name'], 'detail' => $place['country'], 'href' => '/travel#' . Format::slug($place['name']), 'lat' => $place['lat'], 'lon' => $place['lon']];
        }
    }

    return ['status' => 200, 'body' => ['results' => array_slice($results, 0, 12)]];
});

$router->dispatch(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/',
    static fn (): string => $view->render('not-found', ['title' => 'Not found — Worldly', 'nav' => '', 'what' => 'page']),
);
