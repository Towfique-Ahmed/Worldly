<?php

declare(strict_types=1);

use Worldly\Support\Format;

/** @var array $countries already sorted richest first by GDP per capita */
/** @var array $continents */

$top = array_slice($countries, 0, 3);
$bottom = array_slice($countries, -3);
?>

<section class="wrap" data-filterable>
  <span class="eyebrow">💰 <?= count($countries) ?> countries ranked</span>
  <h1 class="hero__title" style="font-size:clamp(1.9rem,4.4vw,3rem)">Richest Countries in the World</h1>
  <p class="hero__lede">Every country ranked by GDP per person — economic output divided by population, the standard way to compare how wealthy an average resident is, rather than how large the whole economy is. Sort by total GDP instead, filter by continent, or search by name.</p>

  <div class="grid grid--2" style="margin:22px 0">
    <div class="card reveal">
      <span class="eyebrow">🏆 Wealthiest by GDP per person</span>
      <div class="rowlist">
        <?php foreach ($top as $index => $country): ?>
          <a class="row" href="/country/<?= Format::e($country['iso3']) ?>" style="grid-template-columns:auto 1fr auto">
            <span class="row__rank" style="font-size:1.35rem"><?= $country['flag'] ?></span>
            <span class="row__title"><span><?= Format::ordinal($index + 1) ?> · <?= Format::e($country['name']) ?></span></span>
            <span class="row__num">$<?= Format::compact($country['gdpPerCapita']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="card reveal" data-delay="60">
      <span class="eyebrow">📉 Poorest by GDP per person</span>
      <div class="rowlist">
        <?php foreach (array_reverse($bottom) as $index => $country): ?>
          <a class="row" href="/country/<?= Format::e($country['iso3']) ?>" style="grid-template-columns:auto 1fr auto">
            <span class="row__rank" style="font-size:1.35rem"><?= $country['flag'] ?></span>
            <span class="row__title"><span><?= Format::ordinal(count($countries) - $index) ?> · <?= Format::e($country['name']) ?></span></span>
            <span class="row__num">$<?= Format::compact($country['gdpPerCapita']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <h2 style="font-size:1.25rem;margin-bottom:14px">Every country, richest to poorest</h2>

  <?= $this->partial('ranking', [
      'countries' => $countries,
      'continents' => $continents,
      'field' => 'gdpPerCapita',
      'format' => static fn (int|float $v): string => '$' . Format::compact($v),
      'sortOptions' => [
          ['value' => '-value', 'label' => 'GDP per person (high → low)'],
          ['value' => 'value', 'label' => 'GDP per person (low → high)'],
          ['value' => 'name', 'label' => 'Name (A–Z)'],
          ['value' => '-name', 'label' => 'Name (Z–A)'],
      ],
  ]) ?>

  <p style="margin-top:14px;font-size:.82rem;color:var(--text-faint)">
    GDP and population figures are recent estimates, good for comparing countries against each other but a few years old — not for citation.
  </p>
</section>
