<?php

declare(strict_types=1);

use Worldly\Support\Format;

/** @var array $country */
/** @var array|null $continent */
/** @var array|null $capital */
/** @var array $cities */
/** @var array $mountains */
/** @var array $places */
/** @var array $neighbours */
/** @var array $mapPayload */

$accent = $continent['accent'] ?? '#5aa9ff';
$capitalZone = null;
?>

<section class="wrap">
  <a class="btn btn--sm" href="/countries" style="margin-bottom:18px">← All countries</a>

  <div class="hero__grid">
    <div>
      <div style="font-size:4.4rem;line-height:1;animation:pop .5s var(--ease)"><?= $country['flag'] ?></div>
      <h1 class="hero__title" style="font-size:clamp(2rem,4.6vw,3rem);margin-top:10px"><?= Format::e($country['name']) ?></h1>
      <p class="hero__lede" style="margin-bottom:18px">
        <?= Format::e($country['formalName'] ?? $country['longName']) ?> ·
        <a href="/continent/<?= Format::e(Format::slug($country['continent'])) ?>" style="color:<?= Format::e($accent) ?>"><?= Format::e($country['continent']) ?></a>
        · <?= Format::e($country['subregion']) ?>
      </p>

      <div class="hero__stats">
        <div class="stat"><span class="stat__value" data-count-to="<?= (int) $country['population'] ?>" data-count-format="compact">0</span><span class="stat__label">Population</span></div>
        <div class="stat"><span class="stat__value">$<?= Format::compact($country['gdp'] * 1_000_000) ?></span><span class="stat__label">GDP</span></div>
        <div class="stat"><span class="stat__value">$<?= Format::compact($country['gdpPerCapita']) ?></span><span class="stat__label">GDP / person</span></div>
        <div class="stat"><span class="stat__value"><?= Format::e($capital['name'] ?? '—') ?></span><span class="stat__label">Capital</span></div>
      </div>

      <div class="card" style="margin-top:20px">
        <dl class="kv">
          <dt>ISO codes</dt><dd><?= Format::e($country['iso2']) ?> · <?= Format::e($country['iso3']) ?></dd>
          <dt>Economy</dt><dd><?= Format::e($country['economy'] ?: '—') ?></dd>
          <dt>Income group</dt><dd><?= Format::e($country['income'] ?: '—') ?></dd>
          <dt>Map centre</dt><dd><?= Format::e(Format::coords((float) $country['lat'], (float) $country['lon'])) ?></dd>
          <?php if ($capital): ?>
            <dt>Capital coords</dt><dd><?= Format::e(Format::coords((float) $capital['lat'], (float) $capital['lon'])) ?></dd>
          <?php endif; ?>
        </dl>
      </div>
    </div>

    <div>
      <?= $this->partial('worldmap', [
          'mapId' => 'countryMap',
          'mapPayload' => $mapPayload,
          'variant' => 'full',
          'layers' => ['graticule', 'capitals', 'mountains', 'places'],
          'colorMode' => 'continent',
          'highlight' => $country['iso3'],
      ]) ?>
      <p style="margin-top:10px;font-size:.82rem;color:var(--text-faint)">Highlighted in teal. Scroll to zoom, drag to pan.</p>
    </div>
  </div>
</section>

<?php if ($cities): ?>
<section class="wrap">
  <div class="section-head reveal"><h2>Cities</h2><p>The largest places we hold coordinates for.</p></div>
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
          <div class="row" style="grid-template-columns:1fr auto">
            <span class="row__title"><span><?= Format::e($peak['name']) ?></span></span>
            <span class="row__num"><?= Format::number($peak['elevation']) ?> m</span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($places): ?>
    <div class="card reveal" data-delay="80">
      <span class="eyebrow">🧭 Worth the trip</span>
      <?php foreach ($places as $place): ?>
        <div style="padding:11px 0;border-bottom:1px solid var(--line)">
          <strong><?= Format::e($place['name']) ?></strong>
          <span class="tag" style="margin-left:6px"><?= Format::e($place['category']) ?></span>
          <p style="margin:5px 0 0;font-size:.88rem;color:var(--text-dim)"><?= Format::e($place['blurb']) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($neighbours): ?>
<section class="wrap">
  <div class="section-head reveal"><h2>Nearby in <?= Format::e($country['subregion']) ?></h2></div>
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
