<?php

declare(strict_types=1);

use Worldly\Support\Format;

/** @var array $zones */
/** @var array $wall */
/** @var list<array{zone: string, abbr: string}> $utcZones */
?>

<section class="hero wrap">
  <div class="timehero">
    <div>
      <span class="eyebrow">⏱ Live · <?= count(DateTimeZone::listIdentifiers()) ?> IANA time zones</span>
      <h1 class="hero__title">World Clock, Countdown Timer &amp; Stopwatch</h1>
      <p class="hero__lede">
        Live analog clocks for any city worldwide, each tinting with the local hour so you can see at a glance
        who is awake. Add cities to build your own clock wall, set a countdown timer that rings when it hits zero,
        or use the stopwatch with lap tracking. Your wall is saved in this browser.
      </p>
    </div>

    <div class="card" aria-live="off">
      <p class="timehero__label">Current UTC time</p>
      <div class="timehero__clock" data-hero-clock>--:--:--</div>
      <p class="timehero__date" data-hero-date>&nbsp;</p>
      <p class="timehero__local">Your local time: <span data-hero-local>--:--:--</span> <span data-hero-zone></span></p>
      <div class="timehero__tools">
        <div class="segmented" role="group" aria-label="Time format">
          <button type="button" data-hour-format="24" aria-pressed="true">24-hour</button>
          <button type="button" data-hour-format="12" aria-pressed="false">AM / PM</button>
        </div>
        <span>Applies to every clock below</span>
      </div>
    </div>
  </div>
</section>

<section class="wrap">
  <div class="article">
    <div class="article__main">
      <h2>What is UTC?</h2>
      <p>
        UTC — Coordinated Universal Time — is the reference every other time zone is measured against. It is the
        same everywhere on Earth and never changes for daylight saving. GMT, Greenwich Mean Time, runs at the same
        offset (UTC+00:00) and is the name used in the United Kingdom and much of West Africa in winter.
      </p>
      <h2>How to read a time zone</h2>
      <p>
        Every zone is written as an offset from UTC. Tokyo at UTC+09:00 is nine hours ahead, New York at UTC−05:00
        is five hours behind, and a few places — India, Nepal, parts of Australia — sit on half-hour or 45-minute
        offsets. The tables below show each zone's offset and abbreviation as of right now, with daylight saving
        already applied.
      </p>
    </div>

    <aside class="article__side">
      <div class="card">
        <span class="eyebrow">More time tools</span>
        <ul class="sidelinks">
          <li><a href="/converter">Time converter <small>→</small></a></li>
          <li><a href="/countries">Countries <small>→</small></a></li>
          <li><a href="/">World map &amp; day/night line <small>→</small></a></li>
        </ul>
      </div>
    </aside>
  </div>
</section>

<section class="wrap">
  <div class="section-head">
    <div>
      <h2>Your world clock wall</h2>
      <p>Add any city; each clock face tints with the local hour. Saved in this browser.</p>
    </div>
  </div>

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
                <stop offset="0%" stop-color="#006dca"/><stop offset="100%" stop-color="#2b94e1"/>
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

  <table class="ref-table reveal">
    <thead>
      <tr><th>City</th><th>IANA time zone</th><th>Abbr.</th><th class="num">UTC offset</th><th class="num">Time now</th></tr>
    </thead>
    <tbody>
      <?php foreach ($zones as $zone): ?>
        <tr>
          <th scope="row"><?= $zone['flag'] ?> <?= Format::e($zone['city']) ?></th>
          <td><?= Format::e($zone['zone']) ?></td>
          <td><?= Format::e($zone['abbr']) ?></td>
          <td class="num">UTC<?= Format::e($zone['offset']) ?></td>
          <td class="num live" data-live-clock="<?= Format::e($zone['zone']) ?>">--:--:--</td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<section class="wrap">
  <div class="section-head reveal">
    <div>
      <h2>Time zones at UTC+00:00 right now</h2>
      <p>
        <?= count($utcZones) ?> IANA zones share UTC's offset at this moment. Some — Iceland, Ghana, Senegal —
        stay on it all year; others, like London and Lisbon, only do in winter.
      </p>
    </div>
  </div>

  <ul class="zone-list reveal">
    <?php foreach ($utcZones as $entry): ?>
      <li><?= Format::e(str_replace('_', ' ', $entry['zone'])) ?> <small>· <?= Format::e($entry['abbr']) ?></small></li>
    <?php endforeach; ?>
  </ul>
</section>

<script>
(function () {
  var hour12 = false;
  try { hour12 = localStorage.getItem('worldly:hour12') === '1'; } catch (e) { /* private mode */ }

  var liveNodes = Array.prototype.slice.call(document.querySelectorAll('[data-live-clock]'));
  var heroClock = document.querySelector('[data-hero-clock]');
  var heroDate = document.querySelector('[data-hero-date]');
  var heroLocal = document.querySelector('[data-hero-local]');
  var heroZone = document.querySelector('[data-hero-zone]');
  var buttons = Array.prototype.slice.call(document.querySelectorAll('[data-hour-format]'));

  function time(now, zone, seconds) {
    var options = { hour: '2-digit', minute: '2-digit', hour12: hour12 };
    if (seconds) { options.second = '2-digit'; }
    if (zone) { options.timeZone = zone; }
    return new Intl.DateTimeFormat('en-GB', options).format(now).toUpperCase();
  }

  function tick() {
    var now = new Date();

    liveNodes.forEach(function (node) {
      try { node.textContent = time(now, node.dataset.liveClock, true); }
      catch (e) { node.textContent = '--:--:--'; }
    });

    if (heroClock) {
      heroClock.textContent = time(now, 'UTC', true);
      heroDate.textContent = new Intl.DateTimeFormat('en-GB', {
        timeZone: 'UTC', weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
      }).format(now);
      heroLocal.textContent = time(now, null, true);
      heroZone.textContent = '(' + (Intl.DateTimeFormat().resolvedOptions().timeZone || 'local') + ')';
    }
  }

  buttons.forEach(function (button) {
    button.addEventListener('click', function () {
      hour12 = button.dataset.hourFormat === '12';
      try { localStorage.setItem('worldly:hour12', hour12 ? '1' : '0'); } catch (e) { /* ignore */ }
      paint();
      tick();
    });
  });

  function paint() {
    buttons.forEach(function (button) {
      button.setAttribute('aria-pressed', String((button.dataset.hourFormat === '12') === hour12));
    });
  }

  paint();
  tick();
  setInterval(tick, 1000);
}());
</script>
