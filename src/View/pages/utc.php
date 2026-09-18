<?php

declare(strict_types=1);

use Worldly\Support\Format;

/** @var list<array<string, mixed>> $zones  featured zones, east to west, with offsetSeconds/offset/abbr */
/** @var list<array{question: string, answer: string}> $faq */

// One row per distinct offset, keeping the east-to-west order of $zones.
$byOffset = [];
foreach ($zones as $zone) {
    $byOffset[$zone['offset']][] = $zone;
}
?>

<section class="hero wrap">
  <div class="timehero">
    <div>
      <span class="eyebrow">🌐 The world's time standard · Live</span>
      <h1 class="hero__title">Current UTC Time</h1>
      <p class="hero__lede">
        UTC, Coordinated Universal Time, is the reference every time zone on Earth is measured from. The clock,
        Unix timestamp and ISO 8601 string beside this are the exact UTC time right now.
      </p>
    </div>

    <?= $this->partial('clockcard', ['label' => 'Current UTC time', 'extras' => true]) ?>
  </div>
</section>

<section class="wrap">
  <div class="article">
    <div class="article__main">
      <h2>What is UTC?</h2>
      <p>
        Coordinated Universal Time is kept by a network of atomic clocks and adjusted with an occasional leap second
        to stay within a second of the Earth's rotation. It is the same moment everywhere, never observes daylight
        saving, and is the time that aviation, servers, satellites and the internet run on.
      </p>

      <h2>UTC vs GMT</h2>
      <p>
        UTC and <a href="/time-zone/gmt">GMT</a> read the same, UTC+00:00. GMT is a time zone based on the sun over
        Greenwich that some countries use in winter; UTC is the precise standard behind it. If a schedule says
        "12:00 UTC" and another says "12:00 GMT", they mean the same instant.
      </p>

      <h2>How to write a UTC time</h2>
      <p>
        Software writes UTC as an ISO 8601 timestamp ending in <strong>Z</strong> for "zero offset", like the one in
        the box beside this. Programs also count it as <strong>Unix time</strong>: the seconds elapsed since
        00:00:00 UTC on 1 January 1970. Both are the same number in every time zone, which is why they are safe to
        store and compare.
      </p>
    </div>

    <aside class="article__side">
      <div class="card">
        <span class="eyebrow">Related</span>
        <ul class="sidelinks">
          <li><a href="/time-zone/gmt">GMT time <small>→</small></a></li>
          <li><a href="/clocks">World clock <small>→</small></a></li>
          <li><a href="/converter">Time converter <small>→</small></a></li>
        </ul>
      </div>
    </aside>
  </div>
</section>

<section class="wrap">
  <div class="section-head reveal">
    <div>
      <h2>UTC offsets around the world</h2>
      <p>Each row is a UTC offset in use right now, with the cities on it and the local time there. Ordered east to west.</p>
    </div>
    <a class="btn btn--sm" href="/converter">Convert UTC to local time →</a>
  </div>

  <table class="ref-table reveal">
    <thead>
      <tr><th class="num">UTC offset</th><th>Cities</th><th class="num">Time now</th></tr>
    </thead>
    <tbody>
      <?php foreach ($byOffset as $offset => $group): ?>
        <tr>
          <th scope="row" class="num">UTC<?= Format::e((string) $offset) ?></th>
          <td>
            <?php foreach ($group as $index => $zone): ?><?= $index > 0 ? ', ' : '' ?><?= $zone['flag'] ?> <?= Format::e($zone['city']) ?><?php endforeach; ?>
          </td>
          <td class="num live" data-live-clock="<?= Format::e($group[0]['zone']) ?>">--:--:--</td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<section class="wrap">
  <div class="section-head reveal"><div><h2>UTC time: frequently asked questions</h2></div></div>
  <div class="faq reveal">
    <?php foreach ($faq as $item): ?>
      <details>
        <summary><?= Format::e($item['question']) ?></summary>
        <p><?= Format::e($item['answer']) ?></p>
      </details>
    <?php endforeach; ?>
  </div>
</section>
