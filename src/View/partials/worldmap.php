<?php

declare(strict_types=1);

use Worldly\Atlas;
use Worldly\Support\Format;
use Worldly\Support\Projection;

/**
 * The interactive world map.
 *
 * @var Atlas  $atlas
 * @var string $mapId       unique DOM id
 * @var array  $mapPayload  countries/capitals/mountains/places for the client
 * @var string $variant     'full' (explore page) or 'mini'
 * @var array  $layers      layers switched on at load
 * @var string $colorMode   continent|population|gdp|plain
 * @var array|null $focus   ['lon' =>, 'lat' =>, 'zoom' =>] initial camera
 * @var string|null $highlight  ISO3 to pre-select
 */

$mapId ??= 'worldmap';
$variant ??= 'full';
$layers ??= ['graticule', 'daynight', 'capitals'];
$colorMode ??= 'continent';
$focus ??= null;
$highlight ??= null;
$controls ??= $variant === 'full';

$width = 1000.0;
$height = round(Projection::height($width), 2);
$continents = $atlas->continents();
?>
<div class="mapstage mapstage--<?= Format::e($variant) ?>"
     id="<?= Format::e($mapId) ?>"
     data-worldmap
     data-layers="<?= Format::e(implode(',', $layers)) ?>"
     data-color-mode="<?= Format::e($colorMode) ?>"
     <?= $focus ? 'data-focus="' . Format::e(json_encode($focus, JSON_THROW_ON_ERROR)) . '"' : '' ?>
     <?= $highlight ? 'data-highlight="' . Format::e($highlight) . '"' : '' ?>>

  <script type="application/json" data-worldmap-payload><?= json_encode($mapPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) ?></script>

  <svg class="worldmap" viewBox="0 0 <?= $width ?> <?= $height ?>" role="img"
       aria-label="Interactive world map. Use the country list below the map for a text alternative.">
    <defs>
      <radialGradient id="<?= Format::e($mapId) ?>-ocean" cx="50%" cy="42%" r="72%">
        <stop class="wm-sea-1" offset="0%"/>
        <stop class="wm-sea-2" offset="60%"/>
        <stop class="wm-sea-3" offset="100%"/>
      </radialGradient>
      <linearGradient id="<?= Format::e($mapId) ?>-land" x1="0" y1="0" x2="0.4" y2="1">
        <stop offset="0%" stop-color="#2f6f8f"/>
        <stop offset="100%" stop-color="#1d4468"/>
      </linearGradient>
      <filter id="<?= Format::e($mapId) ?>-glow" x="-60%" y="-60%" width="220%" height="220%">
        <feGaussianBlur stdDeviation="2.4" result="blur"/>
        <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
      </filter>
      <filter id="<?= Format::e($mapId) ?>-softglow" x="-40%" y="-40%" width="180%" height="180%">
        <feGaussianBlur stdDeviation="6"/>
      </filter>
      <?php foreach ($continents as $key => $continent): ?>
      <linearGradient id="<?= Format::e($mapId) ?>-c-<?= Format::slug($key) ?>" x1="0" y1="0" x2="0.7" y2="1">
        <stop offset="0%" stop-color="<?= Format::e($continent['accent']) ?>"/>
        <stop offset="100%" stop-color="<?= Format::e($continent['accent2']) ?>"/>
      </linearGradient>
      <?php endforeach; ?>
    </defs>

    <rect class="wm-ocean" x="0" y="0" width="<?= $width ?>" height="<?= $height ?>" fill="url(#<?= Format::e($mapId) ?>-ocean)"/>

    <g class="wm-camera">
      <g class="wm-graticule" data-graticule></g>

      <g class="wm-countries">
        <?php foreach ($atlas->countries() as $index => $country): ?>
        <path class="wm-country"
              d="<?= $country['path'] ?>"
              data-iso3="<?= Format::e($country['iso3']) ?>"
              data-iso2="<?= Format::e($country['iso2']) ?>"
              data-name="<?= Format::e($country['name']) ?>"
              data-continent="<?= Format::e($country['continent']) ?>"
              data-population="<?= (int) $country['population'] ?>"
              data-gdppc="<?= (int) $country['gdpPerCapita'] ?>"
              data-flag="<?= Format::e($country['flag']) ?>"
              style="--i:<?= $index ?>"
              tabindex="0"
              role="button"
              aria-label="<?= Format::e($country['name']) ?>"></path>
        <?php endforeach; ?>
      </g>

      <g class="wm-night" data-night aria-hidden="true">
        <path class="wm-night__shade" data-night-shade d=""/>
        <circle class="wm-night__sun" data-sun r="7" cx="-100" cy="-100"/>
      </g>

      <g class="wm-arcs" data-arcs aria-hidden="true"></g>
      <g class="wm-markers" data-markers aria-hidden="true"></g>
      <g class="wm-pulse" data-pulse aria-hidden="true"></g>
    </g>
  </svg>

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
    <button class="wm-btn" type="button" data-map-action="random" title="Surprise me" aria-label="Jump to a random country">🎲</button>
  </div>

  <div class="wm-scale" data-scale aria-hidden="true">1.0×</div>
  <?php endif; ?>
</div>
