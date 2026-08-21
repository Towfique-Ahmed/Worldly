<?php

declare(strict_types=1);

use Worldly\Support\Format;

/** @var array $mountains */
/** @var array $continents */
/** @var array $mapPayload */

usort($mountains, static fn (array $a, array $b): int => $b['elevation'] <=> $a['elevation']);

$chart = array_slice($mountains, 0, 26);
$maxElevation = (int) $chart[0]['elevation'];

$chartWidth = 1000.0;
$chartHeight = 340.0;
$baseline = 296.0;
$slot = $chartWidth / count($chart);
$snowline = $baseline - (4200 / $maxElevation) * 250;
?>

<section class="wrap">
  <span class="eyebrow">🏔 <?= count($mountains) ?> peaks · all 14 eight-thousanders · Seven Summits</span>
  <h1 class="hero__title" style="font-size:clamp(1.9rem,4.4vw,3rem)">Highest Mountains in the World</h1>
  <p class="hero__lede">
    The highest ground on the planet, drawn to scale. All 14 eight-thousanders and the Seven Summits — elevation,
    prominence, mountain range and first ascent for every peak. Hover any triangle for its name, click to fly the
    map there.
  </p>

  <div class="card reveal" style="margin:24px 0 30px;padding:16px">
    <svg class="peak-chart" viewBox="0 0 <?= $chartWidth ?> <?= $chartHeight ?>" role="img" aria-label="Elevation profile of the 26 highest featured peaks">
      <defs>
        <linearGradient id="skyGrad" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stop-color="rgba(120,180,255,.20)"/>
          <stop offset="100%" stop-color="rgba(120,180,255,0)"/>
        </linearGradient>
        <linearGradient id="rockGrad" x1="0" y1="0" x2="0.3" y2="1">
          <stop offset="0%" stop-color="#8fc9f2"/>
          <stop offset="42%" stop-color="#3f7fb5"/>
          <stop offset="100%" stop-color="#1d3f66"/>
        </linearGradient>
      </defs>

      <rect x="0" y="0" width="<?= $chartWidth ?>" height="<?= $baseline ?>" fill="url(#skyGrad)"/>

      <line class="snowline" x1="0" y1="<?= round($snowline, 1) ?>" x2="<?= $chartWidth ?>" y2="<?= round($snowline, 1) ?>"/>
      <text x="6" y="<?= round($snowline - 5, 1) ?>" class="peak-label">permanent snowline ≈ 4,200 m</text>

      <?php foreach ([2000, 4000, 6000, 8000] as $mark): ?>
        <?php $y = $baseline - ($mark / $maxElevation) * 250; ?>
        <line x1="0" y1="<?= round($y, 1) ?>" x2="<?= $chartWidth ?>" y2="<?= round($y, 1) ?>" stroke="rgba(140,190,255,.12)" stroke-width="0.7"/>
        <text x="<?= $chartWidth - 4 ?>" y="<?= round($y - 4, 1) ?>" class="peak-label" text-anchor="end"><?= Format::number($mark) ?> m</text>
      <?php endforeach; ?>

      <?php foreach ($chart as $index => $peak):
          $height = ($peak['elevation'] / $maxElevation) * 250;
          $cx = $index * $slot + $slot / 2;
          $half = $slot * 0.92;
          $apexY = $baseline - $height;
          $capHeight = min($height * 0.34, 46);
          $capHalf = $half * ($capHeight / $height);
      ?>
        <g class="peak-bar" data-fly-to data-lon="<?= $peak['lon'] ?>" data-lat="<?= $peak['lat'] ?>" data-zoom="6"
           style="animation:land-in .7s var(--ease) both;animation-delay:<?= $index * 45 ?>ms">
          <title><?= Format::e($peak['name']) ?> — <?= Format::number($peak['elevation']) ?> m</title>
          <path d="M<?= round($cx - $half, 1) ?> <?= $baseline ?> L<?= round($cx, 1) ?> <?= round($apexY, 1) ?> L<?= round($cx + $half, 1) ?> <?= $baseline ?> Z"
                fill="url(#rockGrad)" stroke="rgba(180,225,255,.5)" stroke-width="0.6"/>
          <path d="M<?= round($cx - $capHalf, 1) ?> <?= round($apexY + $capHeight, 1) ?> L<?= round($cx, 1) ?> <?= round($apexY, 1) ?> L<?= round($cx + $capHalf, 1) ?> <?= round($apexY + $capHeight, 1) ?> Z"
                fill="rgba(245,252,255,.92)"/>
        </g>
      <?php endforeach; ?>

      <line x1="0" y1="<?= $baseline ?>" x2="<?= $chartWidth ?>" y2="<?= $baseline ?>" stroke="rgba(160,210,255,.4)" stroke-width="1"/>

      <?php foreach ($chart as $index => $peak): $cx = $index * $slot + $slot / 2; ?>
        <text class="peak-label" transform="translate(<?= round($cx + 3, 1) ?> <?= $baseline + 6 ?>) rotate(56)"><?= Format::e($peak['name']) ?></text>
      <?php endforeach; ?>
    </svg>
  </div>

  <?= $this->partial('worldmap', [
      'mapId' => 'mountainMap',
      'mapPayload' => $mapPayload,
      'variant' => 'full',
      'layers' => ['graticule', 'mountains'],
      'style' => 'physical',
  ]) ?>
</section>

<section class="wrap" data-filterable>
  <div class="maptools">
    <div class="searchbox" style="max-width:320px">
      <input type="search" data-filter-search placeholder="Find a peak, range or country…" aria-label="Filter peaks">
    </div>
    <div class="field" style="min-width:200px">
      <select data-filter-sort aria-label="Sort peaks">
        <option value="-elevation" selected>Highest first</option>
        <option value="elevation">Lowest first</option>
        <option value="name">Name (A–Z)</option>
        <option value="-prominence">Most prominent</option>
        <option value="ascent">First climbed (earliest)</option>
      </select>
    </div>
    <span style="color:var(--text-faint);font-size:.86rem"><strong data-filter-count><?= count($mountains) ?></strong> peaks</span>
  </div>

  <div class="chipset" style="margin-bottom:18px" role="group" aria-label="Filter peaks">
    <button class="chip" type="button" data-filter-value="8000">Eight-thousanders</button>
    <button class="chip" type="button" data-filter-value="seven">Seven Summits</button>
    <?php foreach ($continents as $key => $continent): ?>
      <button class="chip" type="button" data-filter-value="<?= Format::e($key) ?>">
        <span class="chip__dot" style="background:<?= Format::e($continent['accent']) ?>"></span><?= Format::e($key) ?>
      </button>
    <?php endforeach; ?>
  </div>

  <h2 style="font-size:1.25rem;margin-bottom:14px">Every peak in the atlas</h2>

  <div class="grid grid--3" data-filter-list>
    <?php foreach ($mountains as $peak):
        $facets = [$peak['continent']];
        if ($peak['eightThousander']) { $facets[] = '8000'; }
        if ($peak['sevenSummit']) { $facets[] = 'seven'; }
    ?>
      <article class="card reveal" id="<?= Format::e(Format::slug($peak['name'])) ?>"
               data-item
               data-search="<?= Format::e($peak['name'] . ' ' . ($peak['localName'] ?? '') . ' ' . $peak['range'] . ' ' . $peak['system'] . ' ' . implode(' ', $peak['countries'])) ?>"
               data-facet="<?= Format::e(implode('|', $facets)) ?>"
               data-sort-elevation="<?= (int) $peak['elevation'] ?>"
               data-sort-prominence="<?= (int) $peak['prominence'] ?>"
               data-sort-name="<?= Format::e($peak['name']) ?>"
               data-sort-ascent="<?= (int) $peak['firstAscent'] ?>">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px">
          <div>
            <div class="mountain-card__peak"><?= Format::number($peak['elevation']) ?><small style="font-size:.78rem;font-weight:500;color:var(--text-faint)"> m</small></div>
            <h3 style="font-size:1.1rem;margin:2px 0 2px"><?= Format::e($peak['name']) ?></h3>
            <?php if ($peak['localName']): ?>
              <p style="margin:0;font-size:.82rem;color:var(--text-faint)"><?= Format::e($peak['localName']) ?></p>
            <?php endif; ?>
          </div>
          <div style="display:flex;flex-direction:column;gap:6px">
            <button class="wm-btn" type="button" data-fly-to data-lon="<?= $peak['lon'] ?>" data-lat="<?= $peak['lat'] ?>" data-zoom="6.5" title="Show on the map" aria-label="Show <?= Format::e($peak['name']) ?> on the map">◎</button>
            <button class="bookmark" type="button" data-bookmark="mountain:<?= Format::e(Format::slug($peak['name'])) ?>" aria-label="Bookmark <?= Format::e($peak['name']) ?>">★</button>
          </div>
        </div>

        <div style="display:flex;gap:6px;flex-wrap:wrap;margin:10px 0">
          <?php if ($peak['eightThousander']): ?><span class="badge badge--8k">8000 m club</span><?php endif; ?>
          <?php if ($peak['sevenSummit']): ?><span class="badge badge--seven">Seven Summits</span><?php endif; ?>
          <span class="badge"><?= Format::e($peak['continent']) ?></span>
        </div>

        <p style="margin:0 0 10px;font-size:.9rem;color:var(--text-dim)"><?= Format::e($peak['note']) ?></p>

        <dl class="kv" style="font-size:.85rem">
          <dt>Range</dt><dd style="font-weight:500"><?= Format::e($peak['range']) ?></dd>
          <dt>Countries</dt><dd style="font-weight:500"><?= Format::e(implode(', ', $peak['countries'])) ?></dd>
          <dt>Prominence</dt><dd><?= Format::number($peak['prominence']) ?> m</dd>
          <dt>First ascent</dt><dd><?= Format::e(Format::year((int) $peak['firstAscent'])) ?></dd>
        </dl>
      </article>
    <?php endforeach; ?>
  </div>
</section>
