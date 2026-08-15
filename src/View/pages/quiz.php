<?php

declare(strict_types=1);

/** @var array $mapPayload */
?>

<section class="wrap" data-quiz>
  <div class="quiz">
    <div style="text-align:center;margin-bottom:22px">
      <span class="eyebrow">🎯 Eight questions</span>
      <h1 class="hero__title" style="font-size:clamp(1.8rem,4vw,2.6rem)">Atlas quiz</h1>
      <p class="hero__lede" style="margin-inline:auto">
        Flags, capitals or the map itself. Every answer comes back with a real fact about the country.
      </p>
    </div>

    <div class="chipset" style="justify-content:center;margin-bottom:18px" role="group" aria-label="Quiz mode">
      <button class="chip is-on" type="button" data-quiz-mode="flag">🏳 Guess the flag</button>
      <button class="chip" type="button" data-quiz-mode="capital">🏛 Guess the capital</button>
      <button class="chip" type="button" data-quiz-mode="map">🗺 Find it on the map</button>
    </div>

    <div class="quiz__score" style="margin-bottom:8px">
      <span data-quiz-score>0 / 0</span>
      <span class="streak" data-quiz-streak>🔥 0</span>
      <span data-quiz-best>Best: 0</span>
    </div>

    <div class="quiz__bar" style="margin-bottom:18px"><span data-quiz-bar style="width:0%"></span></div>

    <div class="quiz__stage" data-quiz-stage aria-live="polite"></div>

    <div style="margin-top:24px">
      <?php echo $this->partial('worldmap', [
          'mapId' => 'quizMap',
          'mapPayload' => $mapPayload,
          'variant' => 'full',
          'layers' => ['graticule'],
          'style' => 'physical',
          'detail' => false,
          'controls' => false,
          'globe' => false,
      ]); ?>
      <p style="margin-top:10px;font-size:.8rem;color:var(--text-faint);text-align:center">
        In map mode the answer is highlighted here in teal.
      </p>
    </div>
  </div>
</section>
