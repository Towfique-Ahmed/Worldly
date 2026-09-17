<?php

declare(strict_types=1);

use Worldly\Support\Format;

/** @var list<array<string, mixed>> $countries */
/** @var array<string, array<string, mixed>> $continents */
/** @var string $field key on each country holding the ranked metric */
/** @var callable $format formats one metric value for display */
/** @var list<array{value: string, label: string}> $sortOptions first is the default */

$max = $countries === [] ? 0 : max(array_column($countries, $field));
?>

<div class="maptools" style="margin-top:22px">
  <div class="searchbox" style="max-width:340px">
    <input type="search" data-filter-search placeholder="Filter by name…" aria-label="Filter countries by name">
  </div>

  <div class="field" style="min-width:230px">
    <select data-filter-sort aria-label="Sort countries">
      <?php foreach ($sortOptions as $index => $option): ?>
        <option value="<?= Format::e($option['value']) ?>"<?= $index === 0 ? ' selected' : '' ?>><?= Format::e($option['label']) ?></option>
      <?php endforeach; ?>
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

<div class="card table-card">
  <div class="rowlist" data-filter-list>
    <?php foreach ($countries as $country): ?>
      <a class="row" href="/country/<?= Format::e($country['iso3']) ?>"
         data-item
         data-search="<?= Format::e($country['name'] . ' ' . $country['iso2'] . ' ' . $country['iso3'] . ' ' . $country['subregion']) ?>"
         data-facet="<?= Format::e($country['continent']) ?>"
         data-sort-name="<?= Format::e($country['name']) ?>"
         data-sort-value="<?= (float) $country[$field] ?>">
        <span class="row__rank" style="font-size:1.35rem"><?= $country['flag'] ?></span>
        <span class="row__title"><span><?= Format::e($country['name']) ?></span></span>
        <span class="row__sub"><?= Format::e($country['continent']) ?> · <?= Format::e($country['subregion']) ?></span>
        <span class="row__sub">
          <span class="meter"><span class="meter__fill" data-width="<?= $max > 0 ? round((float) $country[$field] / $max * 100) : 0 ?>"></span></span>
        </span>
        <span class="row__num"><?= Format::e($format($country[$field])) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</div>
