<?php

declare(strict_types=1);

use Worldly\Support\Format;

/** @var string $what */
?>

<section class="wrap" style="min-height:52vh;display:grid;place-items:center;text-align:center">
  <div>
    <div style="font-size:4.6rem;animation:bob 3.4s ease-in-out infinite">🧭</div>
    <h1 class="hero__title" style="font-size:clamp(1.8rem,4vw,2.6rem)">Off the edge of the map</h1>
    <p class="hero__lede" style="margin-inline:auto">
      We could not find that <?= Format::e($what) ?>. It may have been renamed, or it may never have existed -
      cartography has form here.
    </p>
    <div class="hero__actions" style="justify-content:center">
      <a class="btn btn--primary" href="/">Back to the map</a>
      <a class="btn" href="/countries">Browse countries</a>
    </div>
  </div>
</section>
