<?php

declare(strict_types=1);

use Worldly\Support\Format;

/** @var array $continent */
/** @var array $countries */
/** @var array $mountains */
/** @var array $places */
/** @var array $mapPayload */

$population = array_sum(array_column($countries, 'population'));
$sorted = $countries;
usort($sorted, static fn (array $a, array $b): int => $b['population'] <=> $a['population']);
$maxPopulation = $sorted ? (int) $sorted[0]['population'] : 1;

usort($mountains, static fn (array $a, array $b): int => $b['elevation'] <=> $a['elevation']);
?>

<section class="wrap">
  <a class="btn btn--sm" href="/continents" style="margin-bottom:18px">← All continents</a>

  <div class="hero__grid">
    <div>
      <span class="eyebrow" style="border-color:<?= Format::e($continent['accent']) ?>">
        <span class="chip__dot" style="background:<?= Format::e($continent['accent']) ?>"></span><?= Format::e($continent['name']) ?>
      </span>
      <h1 class="hero__title" style="font-size:clamp(2rem,4.6vw,3.1rem);background:linear-gradient(120deg,<?= Format::e($continent['accent']) ?>,<?= Format::e($continent['accent2']) ?>);-webkit-background-clip:text;background-clip:text;color:transparent">
        <?= Format::e($continent['name']) ?>
      </h1>
      <p class="hero__lede"><?= Format::e($continent['blurb']) ?></p>

      <div class="hero__stats">
        <div class="stat"><span class="stat__value" data-count-to="<?= count($countries) ?>">0</span><span class="stat__label">Countries mapped</span></div>
        <div class="stat"><span class="stat__value" data-count-to="<?= $population ?>" data-count-format="compact">0</span><span class="stat__label">People</span></div>
        <div class="stat"><span class="stat__value" data-count-to="<?= (int) $continent['area'] ?>" data-count-format="compact">0</span><span class="stat__label">km² of land</span></div>
        <div class="stat"><span class="stat__value"><?= count($places) ?></span><span class="stat__label">Featured places</span></div>
      </div>

      <div class="card" style="margin-top:20px">
        <h3 style="font-size:1rem">Quick facts</h3>
        <ul style="margin:0;padding-left:18px;color:var(--text-dim);font-size:.92rem">
          <?php foreach ($continent['facts'] as $fact): ?>
            <li style="margin-bottom:6px"><?= Format::e($fact) ?></li>
          <?php endforeach; ?>
        </ul>
        <p style="margin:14px 0 0;font-size:.84rem;color:var(--text-faint)">
          ▲ <?= Format::e($continent['highest']) ?> &nbsp;·&nbsp; ▼ <?= Format::e($continent['lowest']) ?>
        </p>
      </div>
    </div>

    <div>
      <?= $this->partial('worldmap', [
          'mapId' => 'continentDetailMap',
          'mapPayload' => $mapPayload,
          'variant' => 'full',
          'layers' => ['graticule', 'capitals', 'places'],
          'colorMode' => 'continent',
          'focus' => $continent['focus'],
      ]) ?>
      <p style="margin-top:10px;font-size:.82rem;color:var(--text-faint)">Drag to pan, scroll to zoom, click any country for its profile.</p>
    </div>
  </div>
</section>

<section class="wrap">
  <div class="section-head reveal">
    <h2>Countries of <?= Format::e($continent['name']) ?></h2>
    <p><?= count($countries) ?> mapped, ordered by population.</p>
  </div>

  <div class="card table-card reveal">
    <div class="rowlist">
      <?php foreach ($sorted as $index => $country): ?>
        <a class="row" href="/country/<?= Format::e($country['iso3']) ?>">
          <span class="row__rank"><?= $index + 1 ?></span>
          <span class="row__title"><span><?= $country['flag'] ?> <?= Format::e($country['name']) ?></span></span>
          <span class="row__sub"><?= Format::e($country['subregion']) ?></span>
          <span class="row__sub">
            <span class="meter"><span class="meter__fill" data-width="<?= $maxPopulation > 0 ? round($country['population'] / $maxPopulation * 100) : 0 ?>"></span></span>
          </span>
          <span class="row__num"><?= Format::compact($country['population']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php if ($mountains): ?>
<section class="wrap">
  <div class="section-head reveal"><h2>Peaks</h2><p>The highest ground on this continent.</p></div>
  <div class="grid grid--3">
    <?php foreach ($mountains as $peak): ?>
      <div class="card reveal">
        <div class="mountain-card__peak"><?= Format::number($peak['elevation']) ?><small style="font-size:.8rem;font-weight:500;color:var(--text-faint)"> m</small></div>
        <h3 style="font-size:1.08rem;margin:2px 0 4px"><?= Format::e($peak['name']) ?></h3>
        <p style="margin:0;font-size:.86rem;color:var(--text-dim)"><?= Format::e($peak['range']) ?> · <?= Format::e(implode(', ', $peak['countries'])) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($places): ?>
<section class="wrap">
  <div class="section-head reveal"><h2>Where to go</h2><p>Featured destinations across <?= Format::e($continent['name']) ?>.</p></div>
  <div class="grid grid--3">
    <?php foreach ($places as $place): ?>
      <article class="card place-card reveal">
        <div class="place-card__top">
          <h3 style="font-size:1.05rem;margin:0"><?= Format::e($place['name']) ?></h3>
          <span class="place-card__cat" style="background:<?= Format::e($continent['accent']) ?>"><?= Format::e($place['category']) ?></span>
        </div>
        <p class="place-card__blurb"><?= Format::e($place['blurb']) ?></p>
        <div class="place-card__foot"><span><?= Format::e($place['country']) ?></span><span><?= Format::e($place['best']) ?></span></div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
