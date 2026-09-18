<?php

declare(strict_types=1);

use Worldly\Support\Format;
use Worldly\Support\Site;
use Worldly\Support\StructuredData;

/** @var string $title */
/** @var string $content */
/** @var string $nav */
/** @var string $assetVersion */
/** @var string|null $description */

$canonical = Site::canonical();
$description ??= 'Worldly is an interactive atlas: a live physical world map and 3D globe, ten facts for every country, rivers, lakes and oceans, mountains, travel places, world clocks and a time converter.';

// The bookmarks page is personal to the visitor and has nothing to index.
$indexable = $nav !== 'bookmarks';

/** @var list<array>|null $jsonLd  extra schema.org blocks from the page */
$jsonLd ??= [];

/** @var list<array{name: string, path: string}>|null $breadcrumbs */
$breadcrumbs ??= [];
if ($breadcrumbs !== []) {
    $jsonLd[] = StructuredData::breadcrumbs(array_merge([['name' => 'Home', 'path' => '/']], $breadcrumbs));
}

/** Main menu: single links, or groups that open a dropdown. */
$menu = [
    ['label' => 'Explore', 'href' => '/', 'key' => 'explore'],
    ['label' => 'Countries', 'items' => [
        ['href' => '/countries', 'key' => 'countries', 'label' => 'All countries', 'hint' => 'Browse, sort and filter every country'],
        ['href' => '/continents', 'key' => 'continents', 'label' => 'Continents', 'hint' => 'The seven landmasses compared'],
        ['href' => '/compare', 'key' => 'compare', 'label' => 'Compare countries', 'hint' => 'Any two countries side by side'],
    ]],
    ['label' => 'Nature', 'items' => [
        ['href' => '/mountains', 'key' => 'mountains', 'label' => 'Mountains', 'hint' => 'Highest peaks, drawn to scale'],
        ['href' => '/waters', 'key' => 'waters', 'label' => 'Waters', 'hint' => 'Rivers, lakes and oceans'],
    ]],
    ['label' => 'Travel', 'href' => '/travel', 'key' => 'travel'],
    ['label' => 'World time', 'items' => [
        ['href' => '/clocks', 'key' => 'clocks', 'label' => 'World clock', 'hint' => 'Live clocks, timer and stopwatch'],
        ['href' => '/time-zone/gmt', 'key' => 'gmt', 'label' => 'GMT time', 'hint' => 'Current Greenwich Mean Time'],
        ['href' => '/time-zone/utc', 'key' => 'utc', 'label' => 'UTC time', 'hint' => 'Current Coordinated Universal Time'],
        ['href' => '/converter', 'key' => 'converter', 'label' => 'Time converter', 'hint' => 'Convert between any two zones'],
    ]],
    ['label' => 'Rankings', 'items' => [
        ['href' => '/richest-countries', 'key' => 'richest', 'label' => 'Richest countries', 'hint' => 'By GDP per person'],
        ['href' => '/polluted-countries', 'key' => 'polluted', 'label' => 'Most polluted countries', 'hint' => 'By average PM2.5'],
        ['href' => '/safest-countries', 'key' => 'safest', 'label' => 'Safest countries', 'hint' => 'Everyday safety from crime'],
        ['href' => '/peaceful-countries', 'key' => 'peaceful', 'label' => 'Most peaceful countries', 'hint' => 'Conflict and stability'],
    ]],
    ['label' => 'Quiz', 'href' => '/quiz', 'key' => 'quiz'],
];

/** Footer columns, one per menu theme. Every href already exists as a route. */
$footerColumns = [
    'Atlas' => [['/', 'Explore the map'], ['/countries', 'Countries'], ['/continents', 'Continents'], ['/compare', 'Compare countries']],
    'Nature & travel' => [['/mountains', 'Mountains'], ['/waters', 'Rivers, lakes & oceans'], ['/travel', 'Travel places'], ['/quiz', 'Atlas quiz']],
    'World time' => [['/clocks', 'World clock'], ['/time-zone/gmt', 'GMT time'], ['/time-zone/utc', 'UTC time'], ['/converter', 'Time converter'], ['/bookmarks', 'Bookmarks']],
    'Rankings' => [['/richest-countries', 'Richest countries'], ['/polluted-countries', 'Most polluted countries'], ['/safest-countries', 'Safest countries'], ['/peaceful-countries', 'Most peaceful countries']],
];
?>
<!doctype html>
<html lang="en" data-theme="day">
<head>
<?php $analyticsId = Site::analyticsId(); ?>
<?php if ($analyticsId !== null): ?>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= rawurlencode($analyticsId) ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', <?= json_encode($analyticsId, JSON_UNESCAPED_SLASHES) ?>);
</script>
<?php endif; ?>

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= Format::e($title) ?></title>
<meta name="description" content="<?= Format::e($description) ?>">
<link rel="canonical" href="<?= Format::e($canonical) ?>">
<?php if (!$indexable): ?>
<meta name="robots" content="noindex, follow">
<?php endif; ?>

<meta property="og:type" content="website">
<meta property="og:site_name" content="Worldly">
<meta property="og:title" content="<?= Format::e($title) ?>">
<meta property="og:description" content="<?= Format::e($description) ?>">
<meta property="og:url" content="<?= Format::e($canonical) ?>">
<meta property="og:image" content="<?= Format::e(Site::url('/assets/og-cover.png')) ?>">
<meta property="og:image:type" content="image/png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="Worldly — an interactive world map and atlas">
<meta property="og:locale" content="en">
<meta name="theme-color" content="#ffffff">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:image" content="<?= Format::e(Site::url('/assets/og-cover.png')) ?>">
<meta name="twitter:title" content="<?= Format::e($title) ?>">
<meta name="twitter:description" content="<?= Format::e($description) ?>">

<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🌍</text></svg>">
<link rel="stylesheet" href="/assets/css/app.css?v=<?= Format::e($assetVersion) ?>">
<?= StructuredData::render(...$jsonLd) ?>
</head>
<body>

<a class="skip" href="#main">Skip to content</a>

<header class="topbar">
  <a class="brand" href="/">
    <span class="brand__globe" aria-hidden="true">
      <svg viewBox="0 0 48 48" width="34" height="34">
        <circle cx="24" cy="24" r="21" fill="none" stroke="#006dca" stroke-width="3"/>
        <ellipse cx="24" cy="24" rx="9" ry="21" fill="none" stroke="#006dca" stroke-width="2"/>
        <path d="M3.6 17.5h40.8M3.6 30.5h40.8" stroke="#006dca" stroke-width="2"/>
        <circle cx="38" cy="10" r="5" fill="#ff642d"/>
      </svg>
    </span>
    <span class="brand__text">
      <strong>Worldly</strong>
      <small>interactive atlas</small>
    </span>
  </a>

  <button class="topsearch" type="button" data-palette-open aria-label="Search the atlas">
    <span aria-hidden="true">⌕</span>
    <span>Search countries, cities, peaks, rivers…</span>
    <span class="topsearch__hint" aria-hidden="true">Ctrl K</span>
  </button>

  <div class="topbar__right">
    <a class="ghost-btn" href="/bookmarks" title="Your bookmarks" aria-label="Your bookmarks" style="position:relative;text-decoration:none">
      ★<span class="bookmark-count" data-bookmark-count style="position:absolute;top:-6px;right:-6px" hidden>0</span>
    </a>

    <div class="utc-badge" id="utcBadge" title="Current Coordinated Universal Time">
      <span class="utc-badge__dot" aria-hidden="true"></span>
      <span class="utc-badge__time" data-utc-clock>--:--:--</span>
      <span class="utc-badge__label">UTC</span>
    </div>

    <button class="ghost-btn" type="button" data-theme-toggle aria-label="Switch colour theme">
      <span data-theme-icon>☀</span>
    </button>
  </div>
</header>

<nav class="mainnav" aria-label="Primary">
  <div class="mainnav__inner">
    <?php foreach ($menu as $entry): ?>
      <?php if (isset($entry['items'])): ?>
        <?php $groupActive = in_array($nav, array_column($entry['items'], 'key'), true); ?>
        <div class="menu<?= $groupActive ? ' is-active' : '' ?>" data-menu>
          <button class="menu__trigger" type="button" aria-expanded="false" aria-haspopup="true">
            <?= Format::e($entry['label']) ?>
            <svg class="menu__caret" viewBox="0 0 10 10" aria-hidden="true"><path d="M1.5 3.5 5 7l3.5-3.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
          <div class="menu__panel">
            <?php foreach ($entry['items'] as $item): ?>
              <a class="menu__item<?= $nav === $item['key'] ? ' is-active' : '' ?>" href="<?= Format::e($item['href']) ?>">
                <strong><?= Format::e($item['label']) ?></strong>
                <span><?= Format::e($item['hint']) ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php else: ?>
        <div class="menu<?= $nav === $entry['key'] ? ' is-active' : '' ?>">
          <a class="menu__trigger" href="<?= Format::e($entry['href']) ?>"><?= Format::e($entry['label']) ?></a>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
</nav>

<?php if ($breadcrumbs !== []): ?>
<nav class="crumbs wrap" aria-label="Breadcrumb">
  <ol>
    <li><a href="/">Home</a></li>
    <?php foreach ($breadcrumbs as $index => $crumb): ?>
      <li<?= $index === count($breadcrumbs) - 1 ? ' aria-current="page"' : '' ?>>
        <?php if ($index === count($breadcrumbs) - 1): ?>
          <span><?= Format::e($crumb['name']) ?></span>
        <?php else: ?>
          <a href="<?= Format::e($crumb['path']) ?>"><?= Format::e($crumb['name']) ?></a>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ol>
</nav>
<?php endif; ?>

<main id="main"><?= $content ?></main>

<footer class="footer">
  <div class="footer__inner">
    <div class="footer__cols">
      <div>
        <p class="footer__brand"><strong>Worldly</strong></p>
        <p class="footer__about">An interactive world atlas: a live map and 3D globe, country facts, mountains, rivers, travel places, world clocks and a time converter.</p>
      </div>
      <?php foreach ($footerColumns as $heading => $items): ?>
        <div class="footer__col">
          <h3><?= Format::e($heading) ?></h3>
          <ul>
            <?php foreach ($items as [$href, $label]): ?>
              <li><a href="<?= Format::e($href) ?>"><?= Format::e($label) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
    </div>

    <p class="footer__meta">
      Coastlines, rivers, lakes and terrain come from <a href="https://www.naturalearthdata.com/" rel="noopener">Natural Earth</a> (public domain) at 1:50m,
      simplified and projected into a Robinson projection server-side. Country attributes come from
      <a href="https://github.com/mledoze/countries" rel="noopener">mledoze/countries</a>, and time zone data from PHP's bundled IANA database.
      Population and GDP figures are estimates a few years old — good for comparison, not for citation.
      &copy; <?= date('Y') ?> All rights reserved by <a href="https://towfique.com" rel="noopener">towfique.com</a>
    </p>
  </div>
</footer>

<div class="palette" data-palette role="dialog" aria-modal="true" aria-label="Command palette" aria-hidden="true">
  <div class="palette__box">
    <input class="palette__input" type="text" data-palette-input placeholder="Search countries, cities, peaks, rivers, lakes, places…" aria-label="Search the atlas">
    <div class="palette__list" data-palette-list></div>
    <div class="palette__hint">
      <span><span class="palette__kbd">↑↓</span> navigate</span>
      <span><span class="palette__kbd">↵</span> open</span>
      <span><span class="palette__kbd">esc</span> close</span>
    </div>
  </div>
</div>

<div class="toast" id="toast" role="status" aria-live="polite"></div>

<script src="/assets/js/projection.js?v=<?= Format::e($assetVersion) ?>"></script>
<script src="/assets/js/worldmap.js?v=<?= Format::e($assetVersion) ?>"></script>
<script src="/assets/js/app.js?v=<?= Format::e($assetVersion) ?>"></script>
<script src="/assets/js/atlas.js?v=<?= Format::e($assetVersion) ?>"></script>
<?php if (in_array($nav, ['clocks', 'converter'], true)): ?>
<script src="/assets/js/time.js?v=<?= Format::e($assetVersion) ?>"></script>
<?php endif; ?>
<?php if (in_array($nav, ['clocks', 'gmt', 'utc'], true)): ?>
<script src="/assets/js/livetime.js?v=<?= Format::e($assetVersion) ?>"></script>
<?php endif; ?>
</body>
</html>
