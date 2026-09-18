<?php

declare(strict_types=1);

/** @var string $label   e.g. "Current UTC time" */
/** @var bool|null $extras  also show the Unix timestamp and ISO 8601 string */
$extras ??= false;
?>
<div class="card" aria-live="off">
  <p class="timehero__label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></p>
  <div class="timehero__clock" data-hero-clock>--:--:--</div>
  <p class="timehero__date" data-hero-date>&nbsp;</p>
  <p class="timehero__local">Your local time: <span data-hero-local>--:--:--</span> <span data-hero-zone></span></p>
  <?php if ($extras): ?>
    <dl class="timehero__meta">
      <div><dt>Unix time</dt><dd data-hero-unix>-</dd></div>
      <div><dt>ISO 8601</dt><dd data-hero-iso>-</dd></div>
    </dl>
  <?php endif; ?>
  <div class="timehero__tools">
    <div class="segmented" role="group" aria-label="Time format">
      <button type="button" data-hour-format="24" aria-pressed="true">24-hour</button>
      <button type="button" data-hour-format="12" aria-pressed="false">AM / PM</button>
    </div>
    <span>Applies to every clock on this page</span>
  </div>
</div>
