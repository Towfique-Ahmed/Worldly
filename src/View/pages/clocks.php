<?php

declare(strict_types=1);

use Worldly\Support\Format;

/** @var array $zones */
/** @var array $wall */
?>

<section class="wrap">
  <span class="eyebrow">⏱ Live · <?= count(DateTimeZone::listIdentifiers()) ?> IANA zones available</span>
  <h1 class="hero__title" style="font-size:clamp(1.9rem,4.4vw,3rem)">World clock, timer &amp; stopwatch</h1>
  <p class="hero__lede">
    Analog faces that tint with the local hour, a countdown that rings, and a stopwatch with laps.
    Your clock wall is remembered in this browser.
  </p>

  <script type="application/json" data-zone-catalogue><?= json_encode(array_map(
      static fn (array $z): array => ['zone' => $z['zone'], 'city' => $z['city'], 'country' => $z['country'], 'flag' => $z['flag']],
      $zones,
  ), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) ?></script>

  <div class="maptools" style="margin:22px 0 18px">
    <div class="field" style="min-width:260px">
      <label for="clockAdd">Add a city to the wall</label>
      <select id="clockAdd" data-clock-add>
        <option value="">Choose a city…</option>
        <?php foreach ($zones as $zone): ?>
          <option value="<?= Format::e($zone['zone']) ?>"><?= $zone['flag'] ?> <?= Format::e($zone['city']) ?> — UTC<?= Format::e($zone['offset']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn btn--sm" type="button" data-clock-reset style="align-self:flex-end">Reset wall</button>
  </div>

  <div class="clockwall" data-clockwall data-default='<?= Format::e(json_encode($wall, JSON_THROW_ON_ERROR)) ?>'></div>
</section>

<section class="wrap">
  <div class="grid grid--2">

    <div class="card timer reveal" data-timer>
      <span class="eyebrow">⏳ Countdown timer</span>
      <div style="display:flex;gap:22px;align-items:center;flex-wrap:wrap">
        <div style="position:relative;flex:0 0 176px">
          <svg viewBox="0 0 200 200" width="176" height="176" class="timer-ring">
            <defs>
              <linearGradient id="timerGrad" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0%" stop-color="#4fe3c1"/><stop offset="50%" stop-color="#5aa9ff"/><stop offset="100%" stop-color="#b47cff"/>
              </linearGradient>
            </defs>
            <circle class="timer-ring__track" cx="100" cy="100" r="86"/>
            <circle class="timer-ring__fill" cx="100" cy="100" r="86" data-timer-ring stroke-dashoffset="0"/>
          </svg>
          <div style="position:absolute;inset:0;display:grid;place-items:center">
            <span style="font-size:1.7rem;font-weight:750;font-variant-numeric:tabular-nums" data-timer-display>00:00</span>
          </div>
        </div>

        <div style="flex:1 1 210px">
          <div style="display:flex;gap:8px;align-items:flex-end;margin-bottom:12px">
            <div class="field"><label for="timerH">Hours</label><input class="numinput" id="timerH" type="number" min="0" max="99" value="0" data-timer-h></div>
            <div class="field"><label for="timerM">Minutes</label><input class="numinput" id="timerM" type="number" min="0" max="59" value="5" data-timer-m></div>
            <div class="field"><label for="timerS">Seconds</label><input class="numinput" id="timerS" type="number" min="0" max="59" value="0" data-timer-s></div>
          </div>

          <div class="chipset" style="margin-bottom:12px">
            <button class="chip" type="button" data-timer-preset="60">1 min</button>
            <button class="chip" type="button" data-timer-preset="300">5 min</button>
            <button class="chip" type="button" data-timer-preset="900">15 min</button>
            <button class="chip" type="button" data-timer-preset="1500">25 min</button>
            <button class="chip" type="button" data-timer-preset="3600">1 hour</button>
          </div>

          <div style="display:flex;gap:9px">
            <button class="btn btn--primary" type="button" data-timer-start>▶ Start</button>
            <button class="btn" type="button" data-timer-reset>Reset</button>
          </div>
        </div>
      </div>
    </div>

    <div class="card reveal" data-stopwatch data-delay="90">
      <span class="eyebrow">⏱ Stopwatch</span>
      <div class="bigtime" data-sw-display style="margin:6px 0 16px">00:00.00</div>

      <div style="display:flex;gap:9px;margin-bottom:14px">
        <button class="btn btn--primary" type="button" data-sw-start>▶ Start</button>
        <button class="btn" type="button" data-sw-lap>Lap</button>
        <button class="btn" type="button" data-sw-reset>Reset</button>
      </div>

      <div class="lap-list" data-sw-laps></div>
    </div>

  </div>
</section>

<section class="wrap">
  <div class="section-head reveal">
    <div>
      <h2>Every featured zone, right now</h2>
      <p>Ordered east to west — the first row is where tomorrow starts.</p>
    </div>
    <a class="btn btn--sm" href="/converter">Convert a specific time →</a>
  </div>

  <div class="card table-card reveal">
    <div class="rowlist">
      <?php foreach ($zones as $zone): ?>
        <div class="row" style="grid-template-columns:34px minmax(0,1.4fr) minmax(0,1fr) auto auto">
          <span class="row__rank" style="font-size:1.2rem"><?= $zone['flag'] ?></span>
          <span class="row__title"><span><?= Format::e($zone['city']) ?></span></span>
          <span class="row__sub"><?= Format::e($zone['zone']) ?></span>
          <span class="row__sub">UTC<?= Format::e($zone['offset']) ?></span>
          <span class="row__num" data-live-clock="<?= Format::e($zone['zone']) ?>">--:--:--</span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<script>
(function () {
  var nodes = Array.prototype.slice.call(document.querySelectorAll('[data-live-clock]'));
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
