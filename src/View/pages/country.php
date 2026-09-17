<?php

declare(strict_types=1);

use Worldly\Support\Format;

/** @var array $country */
/** @var list<string> $facts */
/** @var array|null $continent */
/** @var array|null $capital */
/** @var array $cities */
/** @var array $mountains */
/** @var array $places */
/** @var array $neighbours */
/** @var array $mapPayload */
/** @var array $worldRanks */

$accent = $continent['accent'] ?? '#5aa9ff';
?>

<section class="wrap">
  <div style="display:flex;gap:10px;align-items:center;margin-bottom:18px;flex-wrap:wrap">
    <a class="btn btn--sm" href="/countries">← All countries</a>
    <a class="btn btn--sm" href="/compare?a=<?= Format::e($country['iso3']) ?>">⚖️ Compare</a>
    <button class="bookmark" type="button" data-bookmark="country:<?= Format::e($country['iso3']) ?>" aria-label="Bookmark <?= Format::e($country['name']) ?>">★</button>
  </div>

  <div class="hero__grid">
    <div>
      <div style="font-size:4.4rem;line-height:1;animation:pop .5s var(--ease)"><?= $country['flag'] ?></div>
      <h1 class="hero__title" style="font-size:clamp(2rem,4.6vw,3rem);margin-top:10px"><?= Format::e($country['name']) ?></h1>
      <p class="hero__lede" style="margin-bottom:18px">
        <?= Format::e($country['formalName'] ?? $country['longName']) ?>
        <?php if ($country['nativeName'] && $country['nativeName'] !== $country['name']): ?>
          · <span style="color:var(--text-faint)"><?= Format::e($country['nativeName']) ?></span>
        <?php endif; ?>
        <br>
        <a href="/continent/<?= Format::e(Format::slug($country['continent'])) ?>" style="color:<?= Format::e($accent) ?>"><?= Format::e($country['continent']) ?></a>
        · <?= Format::e($country['subregion']) ?>
      </p>

      <div class="hero__stats">
        <div class="stat"><span class="stat__value" data-count-to="<?= (int) $country['population'] ?>" data-count-format="compact">0</span><span class="stat__label">Population</span></div>
        <div class="stat"><span class="stat__value"><?= $country['area'] ? Format::compact($country['area']) : '—' ?></span><span class="stat__label">km² of land</span></div>
        <div class="stat"><span class="stat__value">$<?= Format::compact($country['gdpPerCapita']) ?></span><span class="stat__label">GDP / person</span></div>
        <div class="stat"><span class="stat__value" style="font-size:1.1rem"><?= Format::e($capital['name'] ?? '—') ?></span><span class="stat__label">Capital</span></div>
      </div>

      <div class="card" style="margin-top:20px">
        <dl class="kv">
          <dt>ISO codes</dt><dd><?= Format::e($country['iso2']) ?> · <?= Format::e($country['iso3']) ?></dd>
          <dt>Density</dt><dd><?= $country['density'] ? Format::number($country['density']) . ' /km²' : '—' ?></dd>
          <dt>Languages</dt><dd style="font-weight:500"><?= Format::e(implode(', ', $country['languages']) ?: '—') ?></dd>
          <dt>Currenc<?= count($country['currencies']) === 1 ? 'y' : 'ies' ?></dt>
          <dd style="font-weight:500"><?= Format::e($country['currencies']
              ? implode(', ', array_map(static fn (array $c): string => $c['name'] . ' (' . $c['code'] . ')', $country['currencies']))
              : '—') ?></dd>
          <dt>Dial code</dt><dd><?= Format::e($country['calling'] ?: '—') ?></dd>
          <dt>Internet domain</dt><dd><?= Format::e($country['tld'] ?: '—') ?></dd>
          <dt>Time zones</dt><dd><?= count($country['timezones']) ?: '—' ?></dd>
          <dt>Coastline</dt><dd><?= $country['landlocked'] ? 'Landlocked' : 'Has a coast' ?></dd>
          <dt>Map centre</dt><dd><?= Format::e(Format::coords((float) $country['lat'], (float) $country['lon'])) ?></dd>
          <dt>Demonym</dt><dd><?= Format::e($country['demonym'] ?: '—') ?></dd>
          <dt>UN region</dt><dd><?= Format::e($country['region'] !== $country['continent'] ? $country['region'] . ' · ' . $country['subregion'] : $country['subregion']) ?></dd>
          <dt>Economy</dt><dd style="font-weight:500"><?= Format::e($country['economy'] ?: '—') ?></dd>
          <dt>Income group</dt><dd style="font-weight:500"><?= Format::e($country['income'] ?: '—') ?></dd>
          <dt>Total GDP</dt><dd><?= $country['gdp'] ? Format::money($country['gdp'] * 1_000_000) : '—' ?></dd>
          <dt>Sovereignty</dt><dd><?= $country['independent'] ? 'Independent' : 'Not independent' ?><?= $country['unMember'] ? ' · UN member' : ' · not a UN member' ?></dd>
          <dt>Borders</dt><dd><?= count($country['borders']) ?: 'None' ?></dd>
        </dl>
      </div>
    </div>

    <div>
      <?= $this->partial('worldmap', [
          'mapId' => 'countryMap',
          'mapPayload' => $mapPayload,
          'variant' => 'full',
          'layers' => ['graticule', 'capitals', 'rivers', 'lakes', 'terrain', 'places', 'mountains'],
          'style' => 'physical',
          'highlight' => $country['iso3'],
      ]) ?>
      <p style="margin-top:10px;font-size:.82rem;color:var(--text-faint)">
        Highlighted in teal. Scroll to zoom, drag to pan, or hit 🌐 for the globe.
      </p>
    </div>
  </div>
</section>

<section class="wrap" style="padding-top:0">
  <div class="section-head reveal">
    <h2>By the numbers</h2>
    <p>How <?= Format::e($country['name']) ?> stacks up against all <?= $worldRanks['of'] ?> mapped countries.</p>
  </div>
  <div class="grid grid--4">
    <div class="card reveal">
      <span class="eyebrow">Population</span>
      <div class="stat__value"><?= Format::ordinal($worldRanks['ranks']['population']) ?></div>
      <div style="font-size:.78rem;color:var(--text-faint);margin-bottom:8px">of <?= $worldRanks['of'] ?> · <?= sprintf('%.2f', $worldRanks['populationShare'] * 100) ?>% of world population</div>
      <div class="meter"><div class="meter__fill" style="width:<?= min(100, $worldRanks['populationShare'] * 100 * 8) ?>%"></div></div>
    </div>
    <div class="card reveal" data-delay="45">
      <span class="eyebrow">Land area</span>
      <div class="stat__value"><?= Format::ordinal($worldRanks['ranks']['area']) ?></div>
      <div style="font-size:.78rem;color:var(--text-faint);margin-bottom:8px">of <?= $worldRanks['of'] ?> · <?= sprintf('%.2f', $worldRanks['areaShare'] * 100) ?>% of world land</div>
      <div class="meter"><div class="meter__fill" style="width:<?= min(100, $worldRanks['areaShare'] * 100 * 8) ?>%"></div></div>
    </div>
    <div class="card reveal" data-delay="90">
      <span class="eyebrow">GDP per person</span>
      <div class="stat__value"><?= Format::ordinal($worldRanks['ranks']['gdpPerCapita']) ?></div>
      <div style="font-size:.78rem;color:var(--text-faint)">richest of <?= $worldRanks['of'] ?> by output per person</div>
    </div>
    <div class="card reveal" data-delay="135">
      <span class="eyebrow">Population density</span>
      <div class="stat__value"><?= Format::ordinal($worldRanks['ranks']['density']) ?></div>
      <div style="font-size:.78rem;color:var(--text-faint)">most crowded of <?= $worldRanks['of'] ?></div>
    </div>
  </div>
</section>

<section class="wrap">
  <div class="section-head reveal">
    <div>
      <h2>Ten facts about <?= Format::e($country['name']) ?></h2>
      <p>The first is hand-written; the rest are worked out from this country's own figures.</p>
    </div>
  </div>

  <div class="facts">
    <?php foreach ($facts as $index => $fact): ?>
      <article class="fact reveal" data-delay="<?= $index * 45 ?>">
        <span class="fact__n"><?= $index + 1 ?></span>
        <p><?= Format::e($fact) ?></p>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<?php if ($country['timezones']): ?>
<section class="wrap" style="padding-top:0">
  <div class="section-head reveal"><h2>Local time</h2><p>Live, from PHP's IANA database.</p></div>
  <div class="grid grid--4">
    <?php foreach ($country['timezones'] as $zone): ?>
      <div class="card reveal">
        <div style="font-size:.76rem;color:var(--text-faint)"><?= Format::e(str_replace('_', ' ', $zone)) ?></div>
        <div style="font-size:1.6rem;font-weight:740;font-variant-numeric:tabular-nums" data-live-clock="<?= Format::e($zone) ?>">--:--:--</div>
        <div style="font-size:.72rem;color:var(--text-faint)">UTC<?= Format::e(Format::offset($zone)) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($cities): ?>
<section class="wrap">
  <div class="section-head reveal"><h2>Cities</h2><p>The largest places we hold coordinates for. Click a row to fly the map there.</p></div>
  <div class="card table-card reveal">
    <div class="rowlist">
      <?php foreach ($cities as $index => $city): ?>
        <div class="row" data-fly-to data-lon="<?= $city['lon'] ?>" data-lat="<?= $city['lat'] ?>" data-zoom="7" style="cursor:pointer">
          <span class="row__rank"><?= $index + 1 ?></span>
          <span class="row__title"><span><?= $city['capital'] ? '★ ' : '' ?><?= Format::e($city['name']) ?></span></span>
          <span class="row__sub"><?= Format::e($city['admin'] ?? '') ?></span>
          <span class="row__sub"><?= Format::e(Format::coords((float) $city['lat'], (float) $city['lon'])) ?></span>
          <span class="row__num"><?= Format::compact($city['population']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($mountains || $places): ?>
<section class="wrap">
  <div class="grid grid--2">
    <?php if ($mountains): ?>
    <div class="card reveal">
      <span class="eyebrow">🏔 Peaks</span>
      <div class="rowlist">
        <?php foreach ($mountains as $peak): ?>
          <div class="row" style="grid-template-columns:1fr auto auto">
            <span class="row__title"><span><?= Format::e($peak['name']) ?></span></span>
            <span class="row__num"><?= Format::number($peak['elevation']) ?> m</span>
            <button class="bookmark" type="button" data-bookmark="mountain:<?= Format::e(Format::slug($peak['name'])) ?>" aria-label="Bookmark <?= Format::e($peak['name']) ?>">★</button>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($places): ?>
    <div class="card reveal" data-delay="80">
      <span class="eyebrow">🧭 Worth the trip</span>
      <?php foreach ($places as $place): ?>
        <div style="padding:11px 0;border-bottom:1px solid var(--line);display:flex;gap:12px;align-items:flex-start">
          <div style="flex:1;min-width:0">
            <strong><?= Format::e($place['name']) ?></strong>
            <span class="tag" style="margin-left:6px"><?= Format::e($place['category']) ?></span>
            <p style="margin:5px 0 0;font-size:.88rem;color:var(--text-dim)"><?= Format::e($place['blurb']) ?></p>
          </div>
          <button class="bookmark" type="button" data-bookmark="place:<?= Format::e(Format::slug($place['name'])) ?>" aria-label="Bookmark <?= Format::e($place['name']) ?>">★</button>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($neighbours): ?>
<section class="wrap">
  <div class="section-head reveal">
    <h2>Land neighbours</h2>
    <p><?= count($neighbours) ?> <?= count($neighbours) === 1 ? 'country shares' : 'countries share' ?> a border with <?= Format::e($country['name']) ?>.</p>
  </div>
  <div class="grid grid--4">
    <?php foreach ($neighbours as $neighbour): ?>
      <a class="card card--link reveal" href="/country/<?= Format::e($neighbour['iso3']) ?>">
        <div style="font-size:2rem"><?= $neighbour['flag'] ?></div>
        <strong><?= Format::e($neighbour['name']) ?></strong>
        <div style="font-size:.82rem;color:var(--text-faint)"><?= Format::compact($neighbour['population']) ?> people</div>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<script>
(function () {
  var nodes = Array.prototype.slice.call(document.querySelectorAll('[data-live-clock]'));
  if (!nodes.length) { return; }
  function tick() {
    var now = new Date();
    nodes.forEach(function (node) {
      try {
        node.textContent = new Intl.DateTimeFormat('en-GB', {
          timeZone: node.dataset.liveClock, hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false
        }).format(now);
      } catch (e) { node.textContent = '--:--:--'; }
    });
  }
  tick();
  setInterval(tick, 1000);
}());
</script>
