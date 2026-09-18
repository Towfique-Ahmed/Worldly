<?php

declare(strict_types=1);

use Worldly\Support\Format;

/** @var list<array{zone: string, abbr: string}> $utcZones */
/** @var list<array<string, mixed>> $yearRound */
/** @var list<array<string, mixed>> $winterOnly */
/** @var string $londonAbbr  GMT or BST, as of this request */
/** @var list<array{question: string, answer: string}> $faq */

$londonOnGmt = $londonAbbr === 'GMT';
?>

<section class="hero wrap">
  <div class="timehero">
    <div>
      <span class="eyebrow">🌐 UTC+00:00 · Live</span>
      <h1 class="hero__title">Current GMT Time</h1>
      <p class="hero__lede">
        GMT, Greenwich Mean Time, is the time at the Royal Observatory in Greenwich, London, and the name of the
        UTC+00:00 time zone. The clock shows the exact GMT time right now, to the second.
      </p>
    </div>

    <?= $this->partial('clockcard', ['label' => 'Current GMT time']) ?>
  </div>
</section>

<section class="wrap">
  <div class="article">
    <div class="article__main">
      <h2>What is GMT?</h2>
      <p>
        Greenwich Mean Time is the mean solar time at the Prime Meridian, the 0° line of longitude that runs through
        Greenwich. It was the world's reference clock from 1884 until atomic time took over, and it is still the legal
        time in the United Kingdom in winter and the everyday time of a band of West African countries and Iceland.
      </p>

      <h2>GMT vs UTC</h2>
      <p>
        GMT and UTC show the same time, UTC+00:00, so for scheduling a call or a flight you can treat them as
        identical. The difference is in what they are: GMT is a time zone that countries adopt, while
        <a href="/time-zone/utc">UTC</a> is the atomic-clock standard every zone, including GMT, is defined from.
      </p>

      <h2>GMT vs BST: is London on GMT right now?</h2>
      <p>
        <?php if ($londonOnGmt): ?>
          Yes. London is on <strong>GMT</strong> at the moment, so its clocks match the time above.
        <?php else: ?>
          No. London is on <strong><?= Format::e($londonAbbr) ?></strong> at the moment, one hour ahead of GMT,
          because British Summer Time (UTC+01:00) is in force.
        <?php endif; ?>
        The United Kingdom moves to BST on the last Sunday of March and back to GMT on the last Sunday of October.
        GMT itself never changes; it is the country that switches zone.
      </p>
    </div>

    <aside class="article__side">
      <div class="card">
        <span class="eyebrow">London right now</span>
        <div class="timehero__clock" style="font-size:2.4rem" data-live-clock="Europe/London">--:--:--</div>
        <p class="timehero__local">Currently on <strong><?= Format::e($londonAbbr) ?></strong>
          (UTC<?= Format::e(Format::offset('Europe/London')) ?>)</p>
      </div>

      <div class="card">
        <span class="eyebrow">Related</span>
        <ul class="sidelinks">
          <li><a href="/time-zone/utc">UTC time <small>→</small></a></li>
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
      <h2>Countries that use GMT</h2>
      <p>Some stay on GMT all year; others use it only in winter and move their clocks in summer.</p>
    </div>
  </div>

  <table class="ref-table reveal">
    <thead>
      <tr><th>Country</th><th>Capital</th><th>Uses GMT</th></tr>
    </thead>
    <tbody>
      <?php foreach ([['All year', $yearRound], ['Winter only', $winterOnly]] as [$when, $group]): ?>
        <?php foreach ($group as $country): ?>
          <tr>
            <th scope="row"><a href="/country/<?= Format::e($country['iso3']) ?>"><?= $country['flag'] ?> <?= Format::e($country['name']) ?></a></th>
            <td><?= Format::e((string) ($country['capital'] ?? '—')) ?></td>
            <td><?= Format::e($when) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<section class="wrap">
  <div class="section-head reveal">
    <div>
      <h2>Time zones at UTC+00:00 right now</h2>
      <p>
        <?= count($utcZones) ?> IANA zones share GMT's offset at this moment, including any that are only on it
        for part of the year.
      </p>
    </div>
  </div>

  <ul class="zone-list reveal">
    <?php foreach ($utcZones as $entry): ?>
      <li><?= Format::e(str_replace('_', ' ', $entry['zone'])) ?> <small>· <?= Format::e($entry['abbr']) ?></small></li>
    <?php endforeach; ?>
  </ul>
</section>

<section class="wrap">
  <div class="section-head reveal"><div><h2>GMT time: frequently asked questions</h2></div></div>
  <div class="faq reveal">
    <?php foreach ($faq as $item): ?>
      <details>
        <summary><?= Format::e($item['question']) ?></summary>
        <p><?= Format::e($item['answer']) ?></p>
      </details>
    <?php endforeach; ?>
  </div>
</section>
