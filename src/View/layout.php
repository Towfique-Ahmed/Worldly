<?php

declare(strict_types=1);

use Worldly\Support\Format;
use Worldly\Support\Site;

/** @var string $title */
/** @var string $content */
/** @var string $nav */
/** @var string $assetVersion */
/** @var string|null $description */

$canonical = Site::canonical();
$description ??= 'Worldly is an interactive atlas: a live physical world map and 3D globe, ten facts for every country, rivers, lakes and oceans, mountains, travel places, world clocks and a time converter.';

// The bookmarks page is personal to the visitor and has nothing to index.
$indexable = $nav !== 'bookmarks';

$links = [
    ['href' => '/', 'key' => 'explore', 'label' => 'Explore', 'icon' => '🌍'],
    ['href' => '/continents', 'key' => 'continents', 'label' => 'Continents', 'icon' => '🗺'],
    ['href' => '/countries', 'key' => 'countries', 'label' => 'Countries', 'icon' => '🏳'],
    ['href' => '/mountains', 'key' => 'mountains', 'label' => 'Mountains', 'icon' => '🏔'],
    ['href' => '/waters', 'key' => 'waters', 'label' => 'Waters', 'icon' => '🌊'],
    ['href' => '/travel', 'key' => 'travel', 'label' => 'Travel', 'icon' => '🧭'],
    ['href' => '/clocks', 'key' => 'clocks', 'label' => 'Clocks', 'icon' => '⏱'],
];

$moreLinks = [
    ['href' => '/compare', 'key' => 'compare', 'label' => 'Compare countries', 'icon' => '⚖️'],
    ['href' => '/quiz', 'key' => 'quiz', 'label' => 'Atlas quiz', 'icon' => '🎯'],
    ['href' => '/converter', 'key' => 'converter', 'label' => 'Time converter', 'icon' => '🔁'],
    ['href' => '/bookmarks', 'key' => 'bookmarks', 'label' => 'Bookmarks', 'icon' => '★'],
];
?>
<!doctype html>
<html lang="en" data-theme="night">
<head>
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
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="<?= Format::e($title) ?>">
<meta name="twitter:description" content="<?= Format::e($description) ?>">

<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🌍</text></svg>">
<link rel="stylesheet" href="/assets/css/app.css?v=<?= Format::e($assetVersion) ?>">
</head>
<body>

<div class="sky" aria-hidden="true">
  <div class="sky__aurora sky__aurora--a"></div>
  <div class="sky__aurora sky__aurora--b"></div>
  <div class="sky__aurora sky__aurora--c"></div>
  <canvas class="sky__stars" id="starfield"></canvas>
  <div class="sky__grain"></div>
</div>

<a class="skip" href="#main">Skip to content</a>

<header class="topbar">
  <a class="brand" href="/">
    <span class="brand__globe" aria-hidden="true">
      <svg viewBox="0 0 48 48" width="34" height="34">
        <defs>
          <linearGradient id="brandGrad" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#7ef0d0"/><stop offset="55%" stop-color="#5aa9ff"/><stop offset="100%" stop-color="#b47cff"/>
          </linearGradient>
        </defs>
        <circle cx="24" cy="24" r="21" fill="none" stroke="url(#brandGrad)" stroke-width="2.5"/>
        <ellipse cx="24" cy="24" rx="9" ry="21" fill="none" stroke="url(#brandGrad)" stroke-width="1.6" opacity=".8"/>
        <path d="M3.6 17.5h40.8M3.6 30.5h40.8" stroke="url(#brandGrad)" stroke-width="1.6" opacity=".8"/>
      </svg>
    </span>
    <span class="brand__text">
      <strong>Worldly</strong>
      <small>interactive atlas</small>
    </span>
  </a>

  <nav class="nav" aria-label="Primary">
    <?php foreach ($links as $link): ?>
      <a class="nav__link<?= $nav === $link['key'] ? ' is-active' : '' ?>" href="<?= Format::e($link['href']) ?>">
        <span class="nav__icon" aria-hidden="true"><?= $link['icon'] ?></span><?= Format::e($link['label']) ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="topbar__right">
    <button class="ghost-btn" type="button" data-palette-open title="Search everything (Ctrl/⌘ + K)" aria-label="Open the command palette">⌕</button>

    <a class="ghost-btn" href="/bookmarks" title="Your bookmarks" aria-label="Your bookmarks" style="position:relative;text-decoration:none">
      ★<span class="bookmark-count" data-bookmark-count style="position:absolute;top:-6px;right:-6px" hidden>0</span>
    </a>

    <div class="utc-badge" id="utcBadge" title="Current Coordinated Universal Time">
      <span class="utc-badge__dot" aria-hidden="true"></span>
      <span class="utc-badge__time" data-utc-clock>--:--:--</span>
      <span class="utc-badge__label">UTC</span>
    </div>

    <button class="ghost-btn" type="button" data-theme-toggle aria-label="Switch colour theme">
      <span data-theme-icon>◐</span>
    </button>
  </div>
</header>

<main id="main"><?= $content ?></main>

<footer class="footer">
  <div class="footer__inner">
    <p class="footer__brand">🌍 <strong>Worldly</strong> — an interactive atlas built in plain PHP, with no framework and no third-party JavaScript.</p>
    <p class="footer__meta">
      Coastlines, rivers, lakes and terrain come from <a href="https://www.naturalearthdata.com/" rel="noopener">Natural Earth</a> (public domain) at 1:50m,
      simplified and projected into a Robinson projection server-side. Country attributes come from
      <a href="https://github.com/mledoze/countries" rel="noopener">mledoze/countries</a>, and time zone data from PHP's bundled IANA database.
      Population and GDP figures are estimates a few years old — good for comparison, not for citation.
    </p>
    <nav class="footer__links" aria-label="Footer">
      <?php foreach (array_merge($links, $moreLinks) as $link): ?>
        <a href="<?= Format::e($link['href']) ?>"><?= Format::e($link['label']) ?></a>
      <?php endforeach; ?>
    </nav>
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
</body>
</html>
