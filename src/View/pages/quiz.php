<?php

declare(strict_types=1);

/** @var array $mapPayload */
?>

<section class="wrap" data-quiz>
  <div class="quiz">
    <div style="text-align:center;margin-bottom:22px">
      <span class="eyebrow">🎯 Eight questions · flags, capitals &amp; map</span>
      <h1 class="hero__title" style="font-size:clamp(1.8rem,4vw,2.6rem)">World Geography Quiz</h1>
      <p class="hero__lede" style="margin-inline:auto">
        Test your geography in three modes - identify the flag, name the capital, or find the country on the map.
        Every answer reveals a real fact about the country.
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

    <h2 style="font-size:1.15rem;text-align:center;margin-bottom:12px">Your question</h2>

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

    <div class="card" style="margin-top:26px;padding:26px">
      <h2 style="font-size:1.15rem">How the quiz works</h2>
      <div class="prose">
        <p>
          Each round draws eight countries at random from the <a href="/countries">242 in the atlas</a>, limited to
          United Nations member states with a population above 300,000 so the questions stay answerable. The three
          wrong answers are picked from the same continent wherever possible - guessing Malaysia from a list
          containing Norway and Chile would not teach you much.
        </p>
        <p>
          <strong>Guess the flag</strong> shows a national flag and asks you to name the country.
          <strong>Guess the capital</strong> names a capital city and asks the same.
          <strong>Find it on the map</strong> highlights a country on the world map below, which is the hardest of
          the three once you get away from the familiar outlines.
        </p>
        <p>
          Every answer, right or wrong, comes back with a real fact about that country - the same facts you will
          find on its <a href="/countries">country page</a>. Your streak counts consecutive correct answers, and
          your best score is remembered in this browser. Nothing is uploaded, and there is no account to make.
        </p>
        <p>
          If you want to study first, the <a href="/continents">continents</a> pages group countries by region, and
          <a href="/compare">compare</a> puts any two side by side.
        </p>
      </div>
    </div>
  </div>
</section>
