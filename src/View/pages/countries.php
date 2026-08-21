<?php

declare(strict_types=1);

use Worldly\Support\Format;

/** @var array $countries */
/** @var array $continents */

$maxPopulation = max(array_column($countries, 'population'));
?>

<section class="wrap" data-filterable>
  <span class="eyebrow">🏳 <?= count($countries) ?> countries &amp; territories · 7 continents</span>
  <h1 class="hero__title" style="font-size:clamp(1.9rem,4.4vw,3rem)">All Countries of the World</h1>
  <p class="hero__lede">Browse every country and territory on Earth. Sort by population, land area or GDP per person, and filter by continent. Every row opens a full profile with ten facts, capital, languages, currency and local time.</p>

  <div class="maptools" style="margin-top:22px">
    <div class="searchbox" style="max-width:340px">
      <input type="search" data-filter-search placeholder="Filter by name…" aria-label="Filter countries by name">
    </div>

    <div class="field" style="min-width:190px">
      <select data-filter-sort aria-label="Sort countries">
        <option value="name">Name (A–Z)</option>
        <option value="-name">Name (Z–A)</option>
        <option value="-population" selected>Population (high → low)</option>
        <option value="population">Population (low → high)</option>
        <option value="-gdppc">GDP per person (high → low)</option>
        <option value="gdppc">GDP per person (low → high)</option>
      </select>
    </div>

    <span style="color:var(--text-faint);font-size:.86rem"><strong data-filter-count><?= count($countries) ?></strong> shown</span>
  </div>

  <div class="chipset" style="margin-bottom:18px" role="group" aria-label="Filter by continent">
    <?php foreach ($continents as $key => $continent): ?>
      <button class="chip" type="button" data-filter-value="<?= Format::e($key) ?>">
        <span class="chip__dot" style="background:<?= Format::e($continent['accent']) ?>"></span><?= Format::e($key) ?>
      </button>
    <?php endforeach; ?>
  </div>

  <h2 style="font-size:1.25rem;margin-bottom:14px">Every country, sorted and filtered</h2>

  <div class="card table-card">
    <div class="rowlist" data-filter-list>
      <?php foreach ($countries as $country): ?>
        <a class="row" href="/country/<?= Format::e($country['iso3']) ?>"
           data-item
           data-search="<?= Format::e($country['name'] . ' ' . $country['longName'] . ' ' . $country['iso2'] . ' ' . $country['iso3'] . ' ' . $country['subregion']) ?>"
           data-facet="<?= Format::e($country['continent']) ?>"
           data-sort-name="<?= Format::e($country['name']) ?>"
           data-sort-population="<?= (int) $country['population'] ?>"
           data-sort-gdppc="<?= (int) $country['gdpPerCapita'] ?>">
          <span class="row__rank" style="font-size:1.35rem"><?= $country['flag'] ?></span>
          <span class="row__title"><span><?= Format::e($country['name']) ?></span></span>
          <span class="row__sub"><?= Format::e($country['continent']) ?> · <?= Format::e($country['subregion']) ?></span>
          <span class="row__sub">
            <span class="meter"><span class="meter__fill" data-width="<?= $maxPopulation > 0 ? round($country['population'] / $maxPopulation * 100) : 0 ?>"></span></span>
          </span>
          <span class="row__num"><?= Format::compact($country['population']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
