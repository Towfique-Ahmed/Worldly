<?php

declare(strict_types=1);

use Worldly\Support\Format;

/** @var array $rivers */
/** @var array $lakes */
/** @var array $oceans */
/** @var array $mapPayload */

$namedRivers = array_values(array_filter($rivers, static fn (array $r): bool => $r['length'] > 0));
$namedLakes = array_values(array_filter($lakes, static fn (array $l): bool => $l['area'] > 0));

// Natural Earth splits the Atlantic and Pacific into north and south basins so
// each gets its own map label; the listing below wants one card per ocean.
$uniqueOceans = [];
foreach ($oceans as $ocean) {
    $uniqueOceans[$ocean['name']] ??= $ocean;
}
$uniqueOceans = array_values($uniqueOceans);

$deepest = $uniqueOceans;
usort($deepest, static fn (array $a, array $b): int => $b['maxDepth'] <=> $a['maxDepth']);
$deepest = array_slice(array_values(array_filter($deepest, static fn (array $o): bool => $o['maxDepth'] > 0)), 0, 14);
$maxDepth = $deepest ? (int) $deepest[0]['maxDepth'] : 1;

$chartWidth = 1000.0;
$chartHeight = 300.0;
$slot = $deepest ? $chartWidth / count($deepest) : $chartWidth;
?>

<section class="wrap">
  <span class="eyebrow">🌊 <?= count($rivers) ?> rivers · <?= count($lakes) ?> lakes · <?= count($uniqueOceans) ?> oceans &amp; seas</span>
  <h1 class="hero__title" style="font-size:clamp(1.9rem,4.4vw,3rem)">Longest Rivers, Largest Lakes &amp; Deepest Oceans</h1>
  <p class="hero__lede">
    Every river centreline and lake outline drawn as real geometry on the world map - length, basin size, depth and
    discharge for each. Includes all five oceans and the major seas, with depths drawn to scale.
    Hover any blue line to name it; click a card to fly there.
  </p>

  <div style="margin:24px 0 10px">
    <?= $this->partial('worldmap', [
        'mapId' => 'waterMap',
        'mapPayload' => $mapPayload,
        'variant' => 'full',
        'layers' => ['graticule', 'rivers', 'lakes', 'labels'],
        'style' => 'physical',
    ]) ?>
  </div>
</section>

<section class="wrap" style="padding-top:6px">
  <div class="section-head reveal">
    <div>
      <h2>How deep the oceans go</h2>
      <p>Maximum known depth of each ocean and major sea, drawn to scale. Everest, inverted, would still not reach the bottom of the Mariana Trench.</p>
    </div>
  </div>

  <div class="card reveal" style="padding:16px">
    <svg class="depth-chart" viewBox="0 0 <?= $chartWidth ?> <?= $chartHeight ?>" role="img" aria-label="Maximum depth of the oceans and major seas">
      <defs>
        <linearGradient id="depthGrad" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stop-color="#6fc4f0"/>
          <stop offset="55%" stop-color="#1f5f9e"/>
          <stop offset="100%" stop-color="#07203c"/>
        </linearGradient>
      </defs>

      <line x1="0" y1="28" x2="<?= $chartWidth ?>" y2="28" stroke="rgba(160,210,255,.5)" stroke-width="1"/>
      <text x="4" y="20" class="depth-label">sea level</text>

      <?php foreach ([2000, 4000, 6000, 8000, 10000] as $mark): ?>
        <?php $y = 28 + ($mark / $maxDepth) * 240; ?>
        <line x1="0" y1="<?= round($y, 1) ?>" x2="<?= $chartWidth ?>" y2="<?= round($y, 1) ?>" stroke="rgba(140,190,255,.12)" stroke-width="0.7"/>
        <text x="<?= $chartWidth - 4 ?>" y="<?= round($y - 4, 1) ?>" class="depth-label" text-anchor="end">−<?= Format::number($mark) ?> m</text>
      <?php endforeach; ?>

      <!-- Everest, flipped, as a scale reference. -->
      <?php $everestY = 28 + (8849 / $maxDepth) * 240; ?>
      <line x1="0" y1="<?= round($everestY, 1) ?>" x2="<?= $chartWidth ?>" y2="<?= round($everestY, 1) ?>" stroke="rgba(255,200,87,.45)" stroke-dasharray="5 4" stroke-width="1"/>
      <text x="6" y="<?= round($everestY - 5, 1) ?>" class="depth-label" style="fill:rgba(255,200,87,.8)">Everest upside down - 8,849 m</text>

      <?php foreach ($deepest as $index => $ocean):
          $depth = ($ocean['maxDepth'] / $maxDepth) * 240;
          $x = $index * $slot + $slot * 0.16;
          $barWidth = $slot * 0.68;
      ?>
        <g class="depth-bar" data-fly-to data-lon="<?= $ocean['lon'] ?>" data-lat="<?= $ocean['lat'] ?>" data-zoom="2.4"
           style="animation:land-in .7s var(--ease) both;animation-delay:<?= $index * 55 ?>ms">
          <title><?= Format::e($ocean['name']) ?> - <?= Format::number($ocean['maxDepth']) ?> m deep</title>
          <rect x="<?= round($x, 1) ?>" y="28" width="<?= round($barWidth, 1) ?>" height="<?= round($depth, 1) ?>" rx="3" fill="url(#depthGrad)"/>
        </g>
      <?php endforeach; ?>

      <?php foreach ($deepest as $index => $ocean): $cx = $index * $slot + $slot / 2; ?>
        <text class="depth-label" transform="translate(<?= round($cx + 3, 1) ?> 292) rotate(-40)" text-anchor="start"><?= Format::e($ocean['name']) ?></text>
      <?php endforeach; ?>
    </svg>
  </div>
</section>

<section class="wrap" data-tabs>
  <div class="water-tabs" role="tablist" aria-label="Water bodies">
    <button class="chip is-on" type="button" data-tab="rivers">🏞 Rivers</button>
    <button class="chip" type="button" data-tab="lakes">💧 Lakes</button>
    <button class="chip" type="button" data-tab="oceans">🌊 Oceans &amp; seas</button>
  </div>

  <div data-tab-panel="rivers">
    <div class="section-head reveal">
      <div><h2>The great rivers</h2><p><?= count($namedRivers) ?> rivers with measured length, longest first.</p></div>
    </div>

    <div class="grid grid--3">
      <?php foreach ($namedRivers as $river): ?>
        <article class="card reveal" id="<?= Format::e(Format::slug($river['name'])) ?>">
          <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start">
            <div>
              <h3 style="font-size:1.08rem;margin:0 0 4px">🏞 <?= Format::e($river['name']) ?></h3>
              <?php if ($river['continent']): ?>
                <span class="tag"><?= Format::e($river['continent']) ?></span>
              <?php endif; ?>
            </div>
            <button class="bookmark" type="button" data-bookmark="river:<?= Format::e(Format::slug($river['name'])) ?>" aria-label="Bookmark <?= Format::e($river['name']) ?>">★</button>
          </div>

          <div class="water-card__stat">
            <div><b><?= Format::number($river['length']) ?></b><span>km long</span></div>
            <?php if ($river['basin']): ?><div><b><?= Format::compact($river['basin']) ?></b><span>km² basin</span></div><?php endif; ?>
            <?php if ($river['discharge']): ?><div><b><?= Format::compact($river['discharge']) ?></b><span>m³/s flow</span></div><?php endif; ?>
          </div>

          <?php if ($river['note']): ?>
            <p style="margin:0 0 10px;font-size:.9rem;color:var(--text-dim)"><?= Format::e($river['note']) ?></p>
          <?php endif; ?>

          <div style="font-size:.82rem;color:var(--text-faint)">
            <?php if ($river['outflow']): ?>Flows into <?= Format::e($river['outflow']) ?>.<?php endif; ?>
            <?php if ($river['countries']): ?><br>Through <?= Format::e(implode(', ', $river['countries'])) ?>.<?php endif; ?>
          </div>

          <button class="btn btn--sm" type="button" style="margin-top:12px" data-fly-to data-lon="<?= $river['lon'] ?>" data-lat="<?= $river['lat'] ?>" data-zoom="4">◎ Show on map</button>
        </article>
      <?php endforeach; ?>
    </div>
  </div>

  <div data-tab-panel="lakes" hidden>
    <div class="section-head reveal">
      <div><h2>The great lakes</h2><p><?= count($namedLakes) ?> lakes with measured area, largest first.</p></div>
    </div>

    <div class="grid grid--3">
      <?php foreach ($namedLakes as $lake): ?>
        <article class="card reveal" id="<?= Format::e(Format::slug($lake['name'])) ?>">
          <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start">
            <div>
              <h3 style="font-size:1.08rem;margin:0 0 4px">💧 <?= Format::e($lake['name']) ?></h3>
              <span class="tag"><?= Format::e($lake['type']) ?></span>
            </div>
            <button class="bookmark" type="button" data-bookmark="lake:<?= Format::e(Format::slug($lake['name'])) ?>" aria-label="Bookmark <?= Format::e($lake['name']) ?>">★</button>
          </div>

          <div class="water-card__stat">
            <div><b><?= Format::compact($lake['area']) ?></b><span>km² surface</span></div>
            <?php if ($lake['depth']): ?><div><b><?= Format::number($lake['depth']) ?></b><span>m deep</span></div><?php endif; ?>
            <?php if ($lake['volume']): ?><div><b><?= Format::compact($lake['volume']) ?></b><span>km³ water</span></div><?php endif; ?>
          </div>

          <?php if ($lake['note']): ?>
            <p style="margin:0 0 10px;font-size:.9rem;color:var(--text-dim)"><?= Format::e($lake['note']) ?></p>
          <?php endif; ?>

          <?php if ($lake['countries']): ?>
            <div style="font-size:.82rem;color:var(--text-faint)"><?= Format::e(implode(', ', $lake['countries'])) ?></div>
          <?php endif; ?>

          <button class="btn btn--sm" type="button" style="margin-top:12px" data-fly-to data-lon="<?= $lake['lon'] ?>" data-lat="<?= $lake['lat'] ?>" data-zoom="5">◎ Show on map</button>
        </article>
      <?php endforeach; ?>
    </div>
  </div>

  <div data-tab-panel="oceans" hidden>
    <div class="section-head reveal">
      <div><h2>Oceans &amp; seas</h2><p>Five oceans and the major seas, by surface area.</p></div>
    </div>

    <div class="grid grid--2">
      <?php foreach ($uniqueOceans as $ocean): ?>
        <article class="card reveal">
          <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start">
            <div>
              <h3 style="font-size:1.14rem;margin:0 0 4px">🌊 <?= Format::e($ocean['name']) ?></h3>
              <span class="tag"><?= Format::e($ocean['kind']) ?></span>
            </div>
            <button class="btn btn--sm" type="button" data-fly-to data-lon="<?= $ocean['lon'] ?>" data-lat="<?= $ocean['lat'] ?>" data-zoom="2.2">◎ Map</button>
          </div>

          <div class="water-card__stat">
            <?php if ($ocean['area']): ?><div><b><?= Format::compact($ocean['area']) ?></b><span>km² surface</span></div><?php endif; ?>
            <?php if ($ocean['maxDepth']): ?><div><b><?= Format::number($ocean['maxDepth']) ?></b><span>m at deepest</span></div><?php endif; ?>
            <?php if ($ocean['avgDepth']): ?><div><b><?= Format::number($ocean['avgDepth']) ?></b><span>m average</span></div><?php endif; ?>
          </div>

          <?php if ($ocean['note']): ?>
            <p style="margin:0 0 8px;font-size:.92rem;color:var(--text-dim)"><?= Format::e($ocean['note']) ?></p>
          <?php endif; ?>

          <?php if ($ocean['deepest']): ?>
            <div style="font-size:.82rem;color:var(--text-faint)">Deepest point: <?= Format::e($ocean['deepest']) ?></div>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
