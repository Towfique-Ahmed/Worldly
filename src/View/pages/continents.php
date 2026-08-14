<?php

declare(strict_types=1);

use Worldly\Support\Format;

/** @var array $continents */
/** @var array $totals */
/** @var array $mapPayload */

$largestArea = max(array_column($continents, 'area'));
$largestPop = max(array_map(static fn (array $t): int => $t['population'], $totals));
?>

<section class="wrap">
  <span class="eyebrow">🗺 Seven landmasses</span>
  <h1 class="hero__title" style="font-size:clamp(1.9rem,4.4vw,3rem)">Continents</h1>
  <p class="hero__lede">
    Every continent, sized against the others and pinned on the map. Tap a card to open it, or use the map's
    continent chips to isolate one.
  </p>

  <div style="margin:24px 0 30px">
    <?= $this->partial('worldmap', [
        'mapId' => 'continentMap',
        'mapPayload' => $mapPayload,
        'variant' => 'full',
        'layers' => ['graticule'],
        'colorMode' => 'continent',
    ]) ?>
    <div class="chipset" style="margin-top:12px" role="group" aria-label="Isolate a continent">
      <?php foreach ($continents as $key => $continent): ?>
        <button class="chip" type="button" data-continent-focus="<?= Format::e($key) ?>">
          <span class="chip__dot" style="background:<?= Format::e($continent['accent']) ?>"></span><?= Format::e($key) ?>
        </button>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="grid grid--3">
    <?php foreach ($continents as $key => $continent): $total = $totals[$key]; ?>
      <a class="card continent-card reveal" href="/continent/<?= Format::e(Format::slug($key)) ?>" data-delay="<?= array_search($key, array_keys($continents), true) * 60 ?>">
        <span class="continent-card__glow" style="background:linear-gradient(120deg,<?= Format::e($continent['accent']) ?>,<?= Format::e($continent['accent2']) ?>)"></span>
        <div class="continent-card__body">
          <h2 class="continent-card__name"><?= Format::e($continent['name']) ?></h2>
          <p style="color:var(--text-dim);font-size:.9rem;margin:0"><?= Format::e($continent['blurb']) ?></p>

          <div class="continent-card__stats">
            <div><b><?= $total['countries'] ?></b><span>Countries</span></div>
            <div><b><?= Format::compact($total['population']) ?></b><span>People</span></div>
            <div><b><?= Format::compact($continent['area']) ?></b><span>km²</span></div>
          </div>

          <div style="margin-top:16px;display:flex;flex-direction:column;gap:9px">
            <div>
              <div style="display:flex;justify-content:space-between;font-size:.72rem;color:var(--text-faint)"><span>Land area</span><span><?= Format::number($continent['area']) ?> km²</span></div>
              <div class="meter"><span class="meter__fill" data-width="<?= round($continent['area'] / $largestArea * 100) ?>"></span></div>
            </div>
            <div>
              <div style="display:flex;justify-content:space-between;font-size:.72rem;color:var(--text-faint)"><span>Population</span><span><?= Format::compact($total['population']) ?></span></div>
              <div class="meter"><span class="meter__fill" data-width="<?= $largestPop > 0 ? round($total['population'] / $largestPop * 100) : 0 ?>"></span></div>
            </div>
          </div>

          <p style="margin:16px 0 0;font-size:.8rem;color:var(--text-faint)">
            ▲ Highest: <?= Format::e($continent['highest']) ?><br>
            ▼ Lowest: <?= Format::e($continent['lowest']) ?>
          </p>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</section>
