<?php

declare(strict_types=1);

use Worldly\Support\Format;

/** @var array $countries already sorted worst air first, by average PM2.5 */
/** @var array $continents */

$worst = array_slice($countries, 0, 3);
$cleanest = array_slice($countries, -3);
?>

<section class="wrap" data-filterable>
  <span class="eyebrow">🏭 <?= count($countries) ?> countries with air-quality data</span>
  <h1 class="hero__title" style="font-size:clamp(1.9rem,4.4vw,3rem)">Most Polluted Countries in the World</h1>
  <p class="hero__lede">Countries ranked by average PM2.5 — fine particulate matter, the pollutant most tied to respiratory and heart disease — measured in micrograms per cubic metre of air. The World Health Organization's guideline is 5 µg/m³ or below. Sort, filter by continent, or search by name.</p>

  <div class="grid grid--2" style="margin:22px 0">
    <div class="card reveal">
      <span class="eyebrow">😷 Worst air quality</span>
      <div class="rowlist">
        <?php foreach ($worst as $index => $country): ?>
          <a class="row" href="/country/<?= Format::e($country['iso3']) ?>" style="grid-template-columns:auto 1fr auto">
            <span class="row__rank" style="font-size:1.35rem"><?= $country['flag'] ?></span>
            <span class="row__title"><span><?= Format::ordinal($index + 1) ?> · <?= Format::e($country['name']) ?></span></span>
            <span class="row__num"><?= Format::number($country['pollution']) ?> µg/m³</span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="card reveal" data-delay="60">
      <span class="eyebrow">🌬 Cleanest air</span>
      <div class="rowlist">
        <?php foreach (array_reverse($cleanest) as $index => $country): ?>
          <a class="row" href="/country/<?= Format::e($country['iso3']) ?>" style="grid-template-columns:auto 1fr auto">
            <span class="row__rank" style="font-size:1.35rem"><?= $country['flag'] ?></span>
            <span class="row__title"><span><?= Format::ordinal(count($countries) - $index) ?> · <?= Format::e($country['name']) ?></span></span>
            <span class="row__num"><?= Format::number($country['pollution']) ?> µg/m³</span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <h2 style="font-size:1.25rem;margin-bottom:14px">Every country with data, worst air to cleanest</h2>

  <?= $this->partial('ranking', [
      'countries' => $countries,
      'continents' => $continents,
      'field' => 'pollution',
      'format' => static fn (int|float $v): string => Format::number($v) . ' µg/m³',
      'sortOptions' => [
          ['value' => '-value', 'label' => 'PM2.5 (worst → cleanest)'],
          ['value' => 'value', 'label' => 'PM2.5 (cleanest → worst)'],
          ['value' => 'name', 'label' => 'Name (A–Z)'],
          ['value' => '-name', 'label' => 'Name (Z–A)'],
      ],
  ]) ?>

  <p style="margin-top:14px;font-size:.82rem;color:var(--text-faint)">
    Figures are indicative multi-year averages compiled from public air-quality reporting (IQAir and WHO ambient air quality summaries) for countries with reasonably reported monitoring — good for comparison, not a live feed or a citation-grade source.
  </p>
</section>
