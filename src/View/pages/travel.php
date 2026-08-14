<?php

declare(strict_types=1);

use Worldly\Support\Format;

/** @var array $places */
/** @var array $continents */
/** @var array $mapPayload */

$categoryColors = [
    'Ancient' => '#ffc857',
    'Wonder' => '#ff9f68',
    'Nature' => '#4fe3c1',
    'City' => '#5aa9ff',
    'Beach' => '#59d3ff',
    'Island' => '#7ef0d0',
    'Desert' => '#f0a35e',
    'Wildlife' => '#a3e05b',
    'Adventure' => '#b47cff',
    'Spiritual' => '#ff7a92',
];

$categories = array_values(array_unique(array_column($places, 'category')));
sort($categories);
?>

<section class="wrap">
  <span class="eyebrow">🧭 <?= count($places) ?> destinations · <?= count($categories) ?> kinds of trip</span>
  <h1 class="hero__title" style="font-size:clamp(1.9rem,4.4vw,3rem)">Travel places</h1>
  <p class="hero__lede">
    Ruins, reefs, deserts and cities worth crossing an ocean for — each one pinned on the map with the season
    that actually suits it.
  </p>

  <div style="margin:24px 0 6px">
    <?= $this->partial('worldmap', [
        'mapId' => 'travelMap',
        'mapPayload' => $mapPayload,
        'variant' => 'full',
        'layers' => ['graticule', 'places'],
        'colorMode' => 'plain',
    ]) ?>
  </div>

  <div class="chipset" style="margin-bottom:22px">
    <?php foreach ($categoryColors as $name => $color): ?>
      <span class="chip" style="cursor:default"><span class="chip__dot" style="background:<?= Format::e($color) ?>"></span><?= Format::e($name) ?></span>
    <?php endforeach; ?>
  </div>
</section>

<section class="wrap" data-filterable style="padding-top:0">
  <div class="maptools">
    <div class="searchbox" style="max-width:340px">
      <input type="search" data-filter-search placeholder="Search destinations, countries…" aria-label="Filter destinations">
    </div>
    <div class="field" style="min-width:190px">
      <select data-filter-sort aria-label="Sort destinations">
        <option value="name" selected>Name (A–Z)</option>
        <option value="-name">Name (Z–A)</option>
        <option value="country">Country</option>
        <option value="category">Category</option>
      </select>
    </div>
    <span style="color:var(--text-faint);font-size:.86rem"><strong data-filter-count><?= count($places) ?></strong> places</span>
  </div>

  <div class="chipset" style="margin-bottom:18px" role="group" aria-label="Filter destinations">
    <?php foreach ($categories as $category): ?>
      <button class="chip" type="button" data-filter-value="<?= Format::e($category) ?>">
        <span class="chip__dot" style="background:<?= Format::e($categoryColors[$category] ?? '#5aa9ff') ?>"></span><?= Format::e($category) ?>
      </button>
    <?php endforeach; ?>
    <?php foreach ($continents as $key => $continent): ?>
      <button class="chip" type="button" data-filter-value="<?= Format::e($key) ?>"><?= Format::e($key) ?></button>
    <?php endforeach; ?>
  </div>

  <div class="grid grid--3" data-filter-list>
    <?php foreach ($places as $place): $color = $categoryColors[$place['category']] ?? '#5aa9ff'; ?>
      <article class="card place-card reveal" id="<?= Format::e(Format::slug($place['name'])) ?>"
               data-item
               data-search="<?= Format::e($place['name'] . ' ' . $place['country'] . ' ' . $place['category'] . ' ' . $place['continent']) ?>"
               data-facet="<?= Format::e($place['category'] . '|' . $place['continent']) ?>"
               data-sort-name="<?= Format::e($place['name']) ?>"
               data-sort-country="<?= Format::e($place['country']) ?>"
               data-sort-category="<?= Format::e($place['category']) ?>">
        <div class="place-card__top">
          <h3 style="font-size:1.08rem;margin:0"><?= Format::e($place['name']) ?></h3>
          <span class="place-card__cat" style="background:<?= Format::e($color) ?>"><?= Format::e($place['category']) ?></span>
        </div>

        <p class="place-card__blurb"><?= Format::e($place['blurb']) ?></p>

        <div style="display:flex;gap:8px;flex-wrap:wrap;font-size:.8rem;color:var(--text-faint)">
          <span class="tag"><?= Format::e($place['continent']) ?></span>
          <?php if ($place['built']): ?><span class="tag">Built <?= Format::e($place['built']) ?></span><?php endif; ?>
        </div>

        <div class="place-card__foot">
          <span><a href="/country/<?= Format::e($place['iso3']) ?>" style="color:inherit"><?= Format::e($place['country']) ?></a></span>
          <span>Best: <?= Format::e($place['best']) ?></span>
        </div>

        <button class="btn btn--sm" type="button" data-fly-to data-lon="<?= $place['lon'] ?>" data-lat="<?= $place['lat'] ?>" data-zoom="6.5">◎ Show on map</button>
      </article>
    <?php endforeach; ?>
  </div>
</section>
