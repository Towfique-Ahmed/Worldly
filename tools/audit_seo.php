<?php

declare(strict_types=1);

/**
 * Checks the meta title and description of every page in the sitemap.
 *
 * Usage: php tools/audit_seo.php [--verbose]
 *
 * Runs against the Seo class directly rather than over HTTP, so it is fast and
 * needs no server. Fails with a non-zero exit code if any page is missing
 * metadata, blows the length budget, or duplicates another page - the three
 * things Search Console will otherwise report back at you.
 */

require __DIR__ . '/../src/bootstrap.php';

use Worldly\Atlas;
use Worldly\Support\Seo;

$verbose = in_array('--verbose', $argv, true);
$atlas = new Atlas(__DIR__ . '/../src/Data');

/** @var list<array{path: string, title: string, description: string}> $pages */
$pages = [];

$staticPaths = [
    'explore' => '/',
    'continents' => '/continents',
    'countries' => '/countries',
    'mountains' => '/mountains',
    'waters' => '/waters',
    'travel' => '/travel',
    'compare' => '/compare',
    'quiz' => '/quiz',
    'clocks' => '/clocks',
    'converter' => '/converter',
    'gmt' => '/time-zone/gmt',
    'utc' => '/time-zone/utc',
    'bookmarks' => '/bookmarks',
    'richest' => '/richest-countries',
    'polluted' => '/polluted-countries',
    'safest' => '/safest-countries',
    'peaceful' => '/peaceful-countries',
];

foreach ($staticPaths as $key => $path) {
    $pages[] = ['path' => $path, ...Seo::page($key)];
}

foreach ($atlas->continents() as $name => $continent) {
    $members = $atlas->countriesIn((string) $name);
    $meta = Seo::continent($continent, count($members), array_sum(array_column($members, 'population')));
    $pages[] = ['path' => '/continent/' . Worldly\Support\Format::slug((string) $name), ...$meta];
}

foreach ($atlas->countries() as $country) {
    $meta = Seo::country($country, $atlas->capitalOf($country['iso3']));
    $pages[] = ['path' => '/country/' . $country['iso3'], ...$meta];
}

// ---------------------------------------------------------------------------

$problems = [];
$titles = [];
$descriptions = [];

foreach ($pages as $page) {
    $titleLength = strlen($page['title']);
    $descriptionLength = strlen($page['description']);

    if ($page['title'] === '') {
        $problems[] = "{$page['path']}: empty title";
    } elseif ($titleLength > Seo::TITLE_MAX) {
        $problems[] = "{$page['path']}: title is {$titleLength} chars (max " . Seo::TITLE_MAX . ")";
    } elseif ($titleLength < Seo::TITLE_MIN) {
        $problems[] = "{$page['path']}: title is only {$titleLength} chars (min " . Seo::TITLE_MIN . ")";
    }

    if ($page['description'] === '') {
        $problems[] = "{$page['path']}: empty description";
    } elseif ($descriptionLength > Seo::DESCRIPTION_MAX) {
        $problems[] = "{$page['path']}: description is {$descriptionLength} chars (max " . Seo::DESCRIPTION_MAX . ")";
    } elseif ($descriptionLength < Seo::DESCRIPTION_MIN) {
        $problems[] = "{$page['path']}: description is only {$descriptionLength} chars (min " . Seo::DESCRIPTION_MIN . ")";
    }

    $titles[$page['title']][] = $page['path'];
    $descriptions[$page['description']][] = $page['path'];

    if ($verbose) {
        printf("%-26s T%3d  D%3d  %s\n", $page['path'], $titleLength, $descriptionLength, $page['title']);
    }
}

foreach ($titles as $title => $paths) {
    if (count($paths) > 1) {
        $problems[] = 'duplicate title on ' . implode(', ', array_slice($paths, 0, 4)) . ': "' . $title . '"';
    }
}

foreach ($descriptions as $description => $paths) {
    if (count($paths) > 1) {
        $problems[] = 'duplicate description on ' . implode(', ', array_slice($paths, 0, 4));
    }
}

// ---------------------------------------------------------------------------

$titleLengths = array_map(static fn (array $p): int => strlen($p['title']), $pages);
$descriptionLengths = array_map(static fn (array $p): int => strlen($p['description']), $pages);

printf("\nSEO audit - %d pages\n", count($pages));
printf("  unique titles        %d / %d\n", count($titles), count($pages));
printf("  unique descriptions  %d / %d\n", count($descriptions), count($pages));
printf("  title length         %d–%d (budget %d–%d)\n", min($titleLengths), max($titleLengths), Seo::TITLE_MIN, Seo::TITLE_MAX);
printf("  description length   %d–%d (budget %d–%d)\n", min($descriptionLengths), max($descriptionLengths), Seo::DESCRIPTION_MIN, Seo::DESCRIPTION_MAX);

if ($problems !== []) {
    echo "\n" . count($problems) . " problem(s):\n";
    foreach (array_slice($problems, 0, 40) as $problem) {
        echo "  ✗ {$problem}\n";
    }
    if (count($problems) > 40) {
        echo '  … and ' . (count($problems) - 40) . " more\n";
    }
    exit(1);
}

echo "\n✓ All pages have a unique title and description inside the budgets.\n";
