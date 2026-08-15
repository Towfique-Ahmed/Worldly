<?php

declare(strict_types=1);

use Worldly\Support\Format;

/** @var array $countries */

$sorted = $countries;
usort($sorted, static fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

$defaultA = $_GET['a'] ?? 'BGD';
$defaultB = $_GET['b'] ?? 'JPN';
?>

<section class="wrap" data-compare>
  <span class="eyebrow">⚖️ Side by side</span>
  <h1 class="hero__title" style="font-size:clamp(1.9rem,4.4vw,3rem)">Compare countries</h1>
  <p class="hero__lede">
    Put any two of the <?= count($countries) ?> mapped countries next to each other. The winning side of each
    numeric row is marked — bigger is better, except for density, where lower means more room.
  </p>

  <div class="compare-pick" style="margin-top:24px">
    <div class="field">
      <label for="compareA">First country</label>
      <select id="compareA" data-compare-a>
        <?php foreach ($sorted as $country): ?>
          <option value="<?= Format::e($country['iso3']) ?>"<?= $country['iso3'] === $defaultA ? ' selected' : '' ?>>
            <?= $country['flag'] ?> <?= Format::e($country['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <button class="swap-btn" type="button" data-compare-swap title="Swap sides" aria-label="Swap the two countries">⇄</button>

    <div class="field">
      <label for="compareB">Second country</label>
      <select id="compareB" data-compare-b>
        <?php foreach ($sorted as $country): ?>
          <option value="<?= Format::e($country['iso3']) ?>"<?= $country['iso3'] === $defaultB ? ' selected' : '' ?>>
            <?= $country['flag'] ?> <?= Format::e($country['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <h2 style="font-size:1.25rem;margin-bottom:14px">The comparison</h2>

  <div data-compare-output aria-live="polite"></div>
</section>
