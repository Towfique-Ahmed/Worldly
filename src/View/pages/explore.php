<?php

declare(strict_types=1);

use Worldly\Atlas;
use Worldly\Support\Format;

/** @var Atlas $atlas */
/** @var array $summary */
/** @var array $mapPayload */
/** @var array $continents */
/** @var array $featuredZones */

$peaks = array_slice($atlas->mountains(), 0, 3);
$trips = array_slice($atlas->places(), 0, 3);
?>

<section class="hero wrap">
  <div class="hero__intro">
    <div>
      <span class="eyebrow">🛰 Live · <?= Format::number($summary['countries']) ?> countries mapped</span>
      <h1 class="hero__title">The world map, <span class="grad-text">alive and interactive.</span></h1>
      <p class="hero__lede">
        Drag the map, spin the globe, chase the sunlight around it, and open any country for ten facts about it.
        Rivers, lakes, deserts and mountain ranges are all drawn from public-domain Natural Earth data at 1:50m and projected
        server-side — no map tiles, no tracking, no third-party scripts.
      </p>

      <div class="searchbox" data-global-search style="margin-top:20px;max-width:460px">
        <input type="search" placeholder="Search countries, cities, peaks, rivers, lakes, places…  (press /)" aria-label="Search the atlas" autocomplete="off">
        <div class="suggest" data-suggest hidden></div>
      </div>
    </div>

    <div>
      <div class="hero__stats">
        <div class="stat">
          <span class="stat__value" data-count-to="<?= (int) $summary['population'] ?>" data-count-format="compact">0</span>
          <span class="stat__label">People alive</span>
        </div>
        <div class="stat">
          <span class="stat__value" data-count-to="<?= (int) $summary['countries'] ?>">0</span>
          <span class="stat__label">Countries</span>
        </div>
        <div class="stat">
          <span class="stat__value" data-count-to="<?= (int) $summary['facts'] ?>">0</span>
          <span class="stat__label">Country facts</span>
        </div>
        <div class="stat">
          <span class="stat__value" data-count-to="<?= (int) $summary['rivers'] + (int) $summary['lakes'] ?>">0</span>
          <span class="stat__label">Rivers &amp; lakes</span>
        </div>
      </div>

      <div class="hero__actions">
        <a class="btn btn--primary" href="/clocks">⏱ World clock &amp; timer</a>
        <a class="btn" href="/travel">🧭 Travel places</a>
        <a class="btn" href="/quiz">🎯 Play the quiz</a>
      </div>
    </div>
  </div>

  <div class="maptools" style="margin-top:28px">
    <div class="chipset" role="group" aria-label="Map style">
      <button class="chip is-on" type="button" data-map-style="physical">🏔 Physical</button>
      <button class="chip" type="button" data-map-style="political">🎨 Political</button>
      <button class="chip" type="button" data-map-style="population">👥 Population</button>
      <button class="chip" type="button" data-map-style="density">📊 Density</button>
      <button class="chip" type="button" data-map-style="gdp">💵 GDP / person</button>
      <button class="chip" type="button" data-map-style="night">🌃 Night lights</button>
    </div>
  </div>

  <div class="maptools">
    <div class="chipset" role="group" aria-label="Map layers">
      <button class="chip is-on" type="button" data-layer-toggle="rivers" aria-pressed="true">Rivers</button>
      <button class="chip is-on" type="button" data-layer-toggle="lakes" aria-pressed="true">Lakes</button>
      <button class="chip is-on" type="button" data-layer-toggle="terrain" aria-pressed="true">Terrain</button>
      <button class="chip is-on" type="button" data-layer-toggle="labels" aria-pressed="true">Sea names</button>
      <button class="chip is-on" type="button" data-layer-toggle="graticule" aria-pressed="true">Grid</button>
      <button class="chip is-on" type="button" data-layer-toggle="daynight" aria-pressed="true">Day &amp; night</button>
      <button class="chip is-on" type="button" data-layer-toggle="capitals" aria-pressed="true">Capitals</button>
      <button class="chip" type="button" data-layer-toggle="cities" aria-pressed="false">Cities</button>
      <button class="chip" type="button" data-layer-toggle="mountains" aria-pressed="false">Peaks</button>
      <button class="chip" type="button" data-layer-toggle="places" aria-pressed="false">Travel</button>
    </div>
    <button class="chip" type="button" data-measure-toggle aria-pressed="false">📏 Measure distance</button>
  </div>

  <div class="measure-readout" data-measure-readout hidden style="margin-bottom:12px"></div>

  <div class="explore__stage">
    <div>
      <?= $this->partial('worldmap', [
          'mapId' => 'exploreMap',
          'mapPayload' => $mapPayload,
          'variant' => 'full',
          'layers' => ['graticule', 'daynight', 'capitals', 'rivers', 'lakes', 'terrain', 'labels'],
          'style' => 'physical',
      ]) ?>

      <div class="chipset" style="margin-top:12px" role="group" aria-label="Jump to a continent">
        <?php foreach ($continents as $key => $continent): ?>
          <button class="chip" type="button" data-continent-focus="<?= Format::e($key) ?>">
            <span class="chip__dot" style="background:<?= Format::e($continent['accent']) ?>"></span><?= Format::e($key) ?>
          </button>
        <?php endforeach; ?>
      </div>
    </div>

    <aside class="detail" data-detail aria-live="polite">
      <div class="empty-state">
        <span class="empty-state__icon">🌍</span>
        <p><strong>Pick a country</strong><br>Click any landmass — or hit the dice — to pull up its profile.</p>
      </div>
    </aside>
  </div>
</section>

<section class="wrap">
  <div class="section-head reveal">
    <div>
      <h2>Right now, around the world</h2>
      <p>The map's shadow is the real terminator: the line between day and night, recomputed every minute from the sun's position.</p>
    </div>
    <a class="btn btn--sm" href="/clocks">Open the clock wall →</a>
  </div>

  <div class="grid grid--4">
    <?php foreach ($featuredZones as $index => $zone): ?>
      <div class="card reveal" data-delay="<?= $index * 55 ?>">
        <div style="font-size:.78rem;color:var(--text-faint)"><?= $zone['flag'] ?> <?= Format::e($zone['city']) ?></div>
        <div style="font-size:1.7rem;font-weight:740;letter-spacing:-.03em;font-variant-numeric:tabular-nums"
             data-live-clock="<?= Format::e($zone['zone']) ?>">--:--</div>
        <div style="font-size:.74rem;color:var(--text-faint)">UTC<?= Format::e($zone['offset']) ?> · <?= Format::e($zone['abbr']) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="wrap">
  <div class="grid grid--2">
    <div class="card reveal">
      <span class="eyebrow">🏔 Roof of the world</span>
      <h2 style="font-size:1.4rem">The highest ground on Earth</h2>
      <div class="rowlist">
        <?php foreach ($peaks as $index => $peak): ?>
          <a class="row" href="/mountains#<?= Format::e(Format::slug($peak['name'])) ?>"
             data-fly-to data-lon="<?= $peak['lon'] ?>" data-lat="<?= $peak['lat'] ?>" data-zoom="6">
            <span class="row__rank"><?= Format::ordinal($index + 1) ?></span>
            <span class="row__title"><span>🏔 <?= Format::e($peak['name']) ?></span></span>
            <span class="row__sub"><?= Format::e($peak['range']) ?></span>
            <span class="row__num"><?= Format::number($peak['elevation']) ?> m</span>
          </a>
        <?php endforeach; ?>
      </div>
      <a class="btn btn--sm" href="/mountains" style="margin-top:12px">All <?= count($atlas->mountains()) ?> peaks →</a>
    </div>

    <div class="card reveal" data-delay="90">
      <span class="eyebrow">🧭 Worth the flight</span>
      <h2 style="font-size:1.4rem">Places that justify the jet lag</h2>
      <div class="rowlist">
        <?php foreach ($trips as $trip): ?>
          <a class="row" href="/travel#<?= Format::e(Format::slug($trip['name'])) ?>"
             data-fly-to data-lon="<?= $trip['lon'] ?>" data-lat="<?= $trip['lat'] ?>" data-zoom="6">
            <span class="row__rank">📍</span>
            <span class="row__title"><span><?= Format::e($trip['name']) ?></span></span>
            <span class="row__sub"><?= Format::e($trip['country']) ?></span>
            <span class="row__num" style="font-size:.82rem;color:var(--text-faint)"><?= Format::e($trip['best']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
      <a class="btn btn--sm" href="/travel" style="margin-top:12px">All <?= count($atlas->places()) ?> destinations →</a>
    </div>
  </div>
</section>

<section class="wrap">
  <div class="section-head reveal">
    <div>
      <h2>What is inside this atlas</h2>
      <p>Everything below is served from this site itself — no map tiles, no CDN, no analytics.</p>
    </div>
  </div>

  <div class="card reveal" style="padding:26px">
    <div class="prose">
      <p>
        Worldly is an interactive world map and reference atlas. The map you can drag above is drawn from
        public-domain <a href="https://www.naturalearthdata.com/" rel="noopener">Natural Earth</a> vector data
        at 1:50,000,000 scale, simplified and projected into a Robinson projection on the server, then sent to
        your browser as plain SVG. Nothing is fetched from a tile service, so the whole thing works offline and
        nothing about your visit is sent anywhere.
      </p>
      <p>
        Six map styles sit on the same geometry. <strong>Physical</strong> tints deserts, mountain ranges,
        plateaus and tundra so the map reads like a school atlas; <strong>political</strong> colours countries by
        continent; and three data styles shade every country by population, population density or GDP per person.
        <strong>Night lights</strong> darkens the land and lights the cities. The shadow crossing the map is a real
        terminator, recalculated every minute from the position of the sun, so the line between day and night is
        where it actually is right now.
      </p>
    </div>

    <div class="prose-grid" style="margin-top:26px">
      <div>
        <h3><a href="/countries">Countries and territories</a></h3>
        <p>
          All <?= Format::number($summary['countries']) ?> of them, each with ten facts, its capital, languages,
          currency, dialling code, borders and live local time in every zone it keeps.
        </p>
      </div>
      <div>
        <h3><a href="/continents">Continents</a></h3>
        <p>
          Seven profiles comparing land area, population and country count, with the highest and lowest point on each.
        </p>
      </div>
      <div>
        <h3><a href="/mountains">Mountains</a></h3>
        <p>
          All fourteen eight-thousanders and the Seven Summits, drawn to scale, with elevation, prominence,
          range and first ascent.
        </p>
      </div>
      <div>
        <h3><a href="/waters">Rivers, lakes and oceans</a></h3>
        <p>
          <?= Format::number($summary['rivers']) ?> river centrelines and <?= Format::number($summary['lakes']) ?>
          lake outlines drawn as real geometry, plus ocean depths to scale.
        </p>
      </div>
      <div>
        <h3><a href="/travel">Places worth the trip</a></h3>
        <p>
          <?= Format::number($summary['places']) ?> destinations pinned by category, each with the season that
          actually suits it.
        </p>
      </div>
      <div>
        <h3><a href="/clocks">Time tools</a></h3>
        <p>
          A world clock wall, a countdown timer, a stopwatch, and a
          <a href="/converter">converter</a> covering all <?= Format::number($summary['timezones']) ?> IANA zones.
        </p>
      </div>
    </div>
  </div>
</section>

<script>
/* Live clocks in the "right now" strip, driven by the browser's tz database. */
(function () {
  var nodes = Array.prototype.slice.call(document.querySelectorAll('[data-live-clock]'));
  if (!nodes.length) { return; }

  function tick() {
    var now = new Date();
    nodes.forEach(function (node) {
      try {
        node.textContent = new Intl.DateTimeFormat('en-GB', {
          timeZone: node.dataset.liveClock, hour: '2-digit', minute: '2-digit', hour12: false
        }).format(now);
      } catch (e) {
        node.textContent = '--:--';
      }
    });
  }

  tick();
  setInterval(tick, 1000);
}());
</script>
