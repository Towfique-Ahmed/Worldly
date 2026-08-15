<?php

declare(strict_types=1);

use Worldly\Atlas;
use Worldly\Support\Format;
use Worldly\Support\Projection;

/**
 * The interactive world map.
 *
 * Coastlines, rivers, lakes and terrain are all projected server-side into SVG
 * paths; only point data (capitals, peaks, destinations, ocean labels) is handed
 * to the browser as JSON.
 *
 * @var Atlas       $atlas
 * @var string      $mapId       unique DOM id
 * @var array       $mapPayload  point data for the client
 * @var string      $variant     'full' or 'mini'
 * @var array       $layers      layers switched on at load
 * @var string      $style       physical|political|population|density|night
 * @var array|null  $focus       ['lon','lat','zoom'] initial camera
 * @var string|null $highlight   ISO3 to pre-select
 * @var bool        $detail      include rivers, lakes and terrain geometry
 * @var bool        $controls    show the control cluster
 */

$mapId ??= 'worldmap';
$variant ??= 'full';
$layers ??= ['graticule', 'daynight', 'capitals', 'rivers', 'labels'];
$style ??= 'physical';
$focus ??= null;
$highlight ??= null;
$detail ??= true;
$controls ??= $variant === 'full';
$globe ??= $variant === 'full';

$width = 1000.0;
$height = round(Projection::height($width), 2);
$continents = $atlas->continents();
$id = Format::e($mapId);

$terrainOrder = ['plain', 'basin', 'plateau', 'tundra', 'desert', 'range'];
?>
<div class="mapstage mapstage--<?= Format::e($variant) ?>"
     id="<?= $id ?>"
     data-worldmap
     data-layers="<?= Format::e(implode(',', $layers)) ?>"
     data-style="<?= Format::e($style) ?>"
     <?= $focus ? 'data-focus="' . Format::e(json_encode($focus, JSON_THROW_ON_ERROR)) . '"' : '' ?>
     <?= $highlight ? 'data-highlight="' . Format::e($highlight) . '"' : '' ?>>

  <script type="application/json" data-worldmap-payload><?= json_encode($mapPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) ?></script>

  <svg class="worldmap" viewBox="0 0 <?= $width ?> <?= $height ?>" role="img"
       aria-label="Interactive world map. Country, peak and destination lists elsewhere on the site carry the same information as text.">
    <defs>
      <radialGradient id="<?= $id ?>-sea" cx="50%" cy="44%" r="78%">
        <stop class="wm-sea-1" offset="0%"/>
        <stop class="wm-sea-2" offset="55%"/>
        <stop class="wm-sea-3" offset="100%"/>
      </radialGradient>

      <!-- Faint swell across the water, so the ocean is not a flat fill. -->
      <filter id="<?= $id ?>-swell" x="0" y="0" width="100%" height="100%">
        <feTurbulence type="fractalNoise" baseFrequency="0.9 0.35" numOctaves="3" seed="7" result="noise"/>
        <feColorMatrix in="noise" type="saturate" values="0"/>
        <feComponentTransfer><feFuncA type="linear" slope="0.09"/></feComponentTransfer>
      </filter>

      <!-- Relief: a soft offset shadow under the land mass. -->
      <filter id="<?= $id ?>-relief" x="-8%" y="-8%" width="116%" height="116%">
        <feDropShadow dx="0" dy="1.6" stdDeviation="1.9" flood-color="#02060f" flood-opacity="0.55"/>
      </filter>

      <filter id="<?= $id ?>-glow" x="-60%" y="-60%" width="220%" height="220%">
        <feGaussianBlur stdDeviation="2.4" result="blur"/>
        <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
      </filter>

      <linearGradient id="<?= $id ?>-land" x1="0" y1="0" x2="0.35" y2="1">
        <stop class="wm-land-1" offset="0%"/>
        <stop class="wm-land-2" offset="100%"/>
      </linearGradient>

      <?php foreach ($continents as $key => $continent): ?>
      <linearGradient id="<?= $id ?>-c-<?= Format::slug($key) ?>" x1="0" y1="0" x2="0.7" y2="1">
        <stop offset="0%" stop-color="<?= Format::e($continent['accent']) ?>"/>
        <stop offset="100%" stop-color="<?= Format::e($continent['accent2']) ?>"/>
      </linearGradient>
      <?php endforeach; ?>
    </defs>

    <rect class="wm-ocean" x="0" y="0" width="<?= $width ?>" height="<?= $height ?>" fill="url(#<?= $id ?>-sea)"/>
    <rect class="wm-swell" x="0" y="0" width="<?= $width ?>" height="<?= $height ?>" filter="url(#<?= $id ?>-swell)"/>

    <g class="wm-camera">
      <g class="wm-graticule" data-graticule></g>

      <g class="wm-land" filter="url(#<?= $id ?>-relief)">
        <g class="wm-countries">
          <?php foreach ($atlas->countryPaths() as $iso3 => $path): ?>
          <path class="wm-country"
                d="<?= $path ?>"
                data-iso3="<?= Format::e($iso3) ?>"
                tabindex="0"
                role="button"></path>
          <?php endforeach; ?>
        </g>
      </g>

      <?php if ($detail): ?>
      <g class="wm-terrain" data-terrain aria-hidden="true">
        <?php
        $terrain = $atlas->terrain();
        usort($terrain, static fn (array $a, array $b): int => array_search($a['kind'], $terrainOrder, true) <=> array_search($b['kind'], $terrainOrder, true));
        foreach ($terrain as $region): ?>
        <path class="wm-terrain__area wm-terrain--<?= Format::e($region['kind']) ?>" d="<?= $region['path'] ?>"><title><?= Format::e($region['name']) ?></title></path>
        <?php endforeach; ?>
      </g>

      <g class="wm-lakes" data-lakes aria-hidden="true">
        <?php foreach ($atlas->lakes() as $lake): ?>
        <path class="wm-lake" d="<?= $lake['path'] ?>"><title><?= Format::e($lake['name']) ?></title></path>
        <?php endforeach; ?>
      </g>

      <g class="wm-rivers" data-rivers aria-hidden="true">
        <?php foreach ($atlas->rivers() as $river): ?>
        <path class="wm-river wm-river--r<?= min(7, (int) $river['rank']) ?>" d="<?= $river['path'] ?>"><title><?= Format::e($river['name']) ?></title></path>
        <?php endforeach; ?>
      </g>
      <?php endif; ?>

      <g class="wm-night" data-night aria-hidden="true">
        <path class="wm-night__shade" data-night-shade d=""/>
        <circle class="wm-night__sun" data-sun r="6" cx="-100" cy="-100"/>
      </g>

      <g class="wm-labels" data-labels aria-hidden="true"></g>
      <g class="wm-arcs" data-arcs aria-hidden="true"></g>
      <g class="wm-markers" data-markers aria-hidden="true"></g>
      <g class="wm-pulse" data-pulse aria-hidden="true"></g>
    </g>
  </svg>

  <?php if ($globe): ?>
  <canvas class="wm-globe" data-globe hidden aria-hidden="true"></canvas>
  <?php endif; ?>

  <div class="wm-tooltip" data-tooltip hidden>
    <span class="wm-tooltip__flag" data-tooltip-flag></span>
    <span class="wm-tooltip__body">
      <strong data-tooltip-title></strong>
      <small data-tooltip-meta></small>
    </span>
  </div>

  <?php if ($controls): ?>
  <div class="wm-controls" role="group" aria-label="Map controls">
    <button class="wm-btn" type="button" data-map-action="zoom-in" title="Zoom in" aria-label="Zoom in">+</button>
    <button class="wm-btn" type="button" data-map-action="zoom-out" title="Zoom out" aria-label="Zoom out">−</button>
    <button class="wm-btn" type="button" data-map-action="reset" title="Reset view" aria-label="Reset view">⟲</button>
    <?php if ($globe): ?>
    <button class="wm-btn wm-btn--globe" type="button" data-map-action="globe" title="Switch to globe" aria-label="Switch between flat map and globe" aria-pressed="false">🌐</button>
    <?php endif; ?>
    <button class="wm-btn" type="button" data-map-action="random" title="Surprise me" aria-label="Jump to a random country">🎲</button>
  </div>

  <div class="wm-hud" aria-hidden="true">
    <span class="wm-hud__pill" data-scale>1.0×</span>
    <span class="wm-hud__pill" data-coords>—</span>
  </div>
  <?php endif; ?>
</div>
