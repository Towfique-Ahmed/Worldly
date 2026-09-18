<?php

declare(strict_types=1);

use Worldly\Support\Format;

/** @var array $zones */
/** @var array $grouped */
?>

<section class="wrap">
  <span class="eyebrow">🔁 Time zone converter · all IANA zones supported</span>
  <h1 class="hero__title" style="font-size:clamp(1.9rem,4.4vw,3rem)">Time Zone Converter - Any City, Any Date</h1>
  <p class="hero__lede">
    Pick any date and time in one zone and instantly read it in any other. Daylight saving is handled
    automatically from your browser's IANA database, including half-hour and 45-minute offsets. Use the city
    shortcuts below to jump to a destination zone in one tap.
  </p>

  <script type="application/json" data-zone-catalogue><?= json_encode(array_map(
      static fn (array $z): array => ['zone' => $z['zone'], 'city' => $z['city'], 'country' => $z['country'], 'flag' => $z['flag']],
      $zones,
  ), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) ?></script>

  <div data-converter>
    <div class="card reveal" style="margin-top:22px">
      <div style="display:grid;grid-template-columns:minmax(0,1fr) auto minmax(0,1fr);gap:16px;align-items:end">
        <div class="field">
          <label for="convFrom">From</label>
          <select id="convFrom" data-conv-from>
            <?php foreach ($grouped as $region => $list): ?>
              <optgroup label="<?= Format::e($region) ?>">
                <?php foreach ($list as $item): ?>
                  <option value="<?= Format::e($item['zone']) ?>"<?= $item['zone'] === 'Europe/London' ? ' selected' : '' ?>>
                    <?= Format::e($item['label']) ?> (UTC<?= Format::e($item['offset']) ?>)
                  </option>
                <?php endforeach; ?>
              </optgroup>
            <?php endforeach; ?>
          </select>
        </div>

        <button class="swap-btn" type="button" data-conv-swap title="Swap zones" aria-label="Swap the two zones">⇄</button>

        <div class="field">
          <label for="convTo">To</label>
          <select id="convTo" data-conv-to>
            <?php foreach ($grouped as $region => $list): ?>
              <optgroup label="<?= Format::e($region) ?>">
                <?php foreach ($list as $item): ?>
                  <option value="<?= Format::e($item['zone']) ?>"<?= $item['zone'] === 'Asia/Dhaka' ? ' selected' : '' ?>>
                    <?= Format::e($item['label']) ?> (UTC<?= Format::e($item['offset']) ?>)
                  </option>
                <?php endforeach; ?>
              </optgroup>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div style="display:flex;gap:12px;align-items:end;margin-top:16px;flex-wrap:wrap">
        <div class="field" style="flex:1 1 260px">
          <label for="convWhen">Date &amp; time in the source zone</label>
          <input id="convWhen" type="datetime-local" data-conv-when>
        </div>
        <button class="btn" type="button" data-conv-now>Use right now</button>
      </div>

      <div style="margin-top:22px" data-conv-output></div>

      <div class="daybar" data-daybar style="margin-top:20px">
        <span class="daybar__marker" data-daybar-marker style="left:50%"></span>
        <span class="daybar__label" data-daybar-label style="left:50%">--:--</span>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:.72rem;color:var(--text-faint);margin-top:6px">
        <span>00:00</span><span>06:00</span><span>12:00</span><span>18:00</span><span>24:00</span>
      </div>
    </div>

    <div class="section-head reveal" style="margin-top:34px">
      <div>
        <h2>Jump to a city</h2>
        <p>Set the destination zone with one tap.</p>
      </div>
    </div>

    <div class="chipset" style="margin-bottom:24px">
      <?php foreach ($zones as $zone): ?>
        <button class="chip" type="button" data-conv-quick="<?= Format::e($zone['zone']) ?>"><?= $zone['flag'] ?> <?= Format::e($zone['city']) ?></button>
      <?php endforeach; ?>
    </div>

    <div class="section-head reveal">
      <div>
        <h2>That same instant, everywhere</h2>
        <p>Handy for picking a meeting slot that does not land at 3 a.m. for someone.</p>
      </div>
    </div>

    <div class="grid grid--4" data-conv-strip></div>
  </div>
</section>
