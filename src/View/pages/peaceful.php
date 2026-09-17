<?php

declare(strict_types=1);

use Worldly\Support\Format;

/** @var array $countries already sorted most peaceful first */
/** @var array $continents */

$peaceful = array_slice($countries, 0, 3);
$conflicted = array_slice($countries, -3);
?>

<section class="wrap" data-filterable>
  <span class="eyebrow">🕊 <?= count($countries) ?> countries scored</span>
  <h1 class="hero__title" style="font-size:clamp(1.9rem,4.4vw,3rem)">Most Peaceful Countries in the World</h1>
  <p class="hero__lede">Countries scored 0–100 on peacefulness — armed conflict, militarization and societal stability, in the spirit of the Global Peace Index. This is about war and conflict, not street crime: see <a href="/safest-countries" style="color:inherit;text-decoration:underline">the safest countries</a> for everyday safety instead. Sort, filter by continent, or search by name.</p>

  <div class="grid grid--2" style="margin:22px 0">
    <div class="card reveal">
      <span class="eyebrow">🕊 Most peaceful</span>
      <div class="rowlist">
        <?php foreach ($peaceful as $index => $country): ?>
          <a class="row" href="/country/<?= Format::e($country['iso3']) ?>" style="grid-template-columns:auto 1fr auto">
            <span class="row__rank" style="font-size:1.35rem"><?= $country['flag'] ?></span>
            <span class="row__title"><span><?= Format::ordinal($index + 1) ?> · <?= Format::e($country['name']) ?></span></span>
            <span class="row__num"><?= Format::number($country['peace']) ?> / 100</span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="card reveal" data-delay="60">
      <span class="eyebrow">⚔️ Least peaceful</span>
      <div class="rowlist">
        <?php foreach (array_reverse($conflicted) as $index => $country): ?>
          <a class="row" href="/country/<?= Format::e($country['iso3']) ?>" style="grid-template-columns:auto 1fr auto">
            <span class="row__rank" style="font-size:1.35rem"><?= $country['flag'] ?></span>
            <span class="row__title"><span><?= Format::ordinal(count($countries) - $index) ?> · <?= Format::e($country['name']) ?></span></span>
            <span class="row__num"><?= Format::number($country['peace']) ?> / 100</span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <h2 style="font-size:1.25rem;margin-bottom:14px">Every country scored, most to least peaceful</h2>

  <?= $this->partial('ranking', [
      'countries' => $countries,
      'continents' => $continents,
      'field' => 'peace',
      'format' => static fn (int|float $v): string => Format::number($v) . ' / 100',
      'sortOptions' => [
          ['value' => '-value', 'label' => 'Peace score (most → least peaceful)'],
          ['value' => 'value', 'label' => 'Peace score (least → most peaceful)'],
          ['value' => 'name', 'label' => 'Name (A–Z)'],
          ['value' => '-name', 'label' => 'Name (Z–A)'],
      ],
  ]) ?>

  <p style="margin-top:14px;font-size:.82rem;color:var(--text-faint)">
    Scores are a curated, indicative composite in the spirit of the Institute for Economics &amp; Peace's Global Peace Index — good for comparison, not a live feed or a citation-grade source.
  </p>
</section>
