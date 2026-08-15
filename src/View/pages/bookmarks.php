<?php

declare(strict_types=1);
?>

<section class="wrap" data-bookmarks-page>
  <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:16px;flex-wrap:wrap">
    <div>
      <span class="eyebrow">★ Saved in this browser</span>
      <h1 class="hero__title" style="font-size:clamp(1.9rem,4.4vw,3rem)">Your bookmarks</h1>
      <p class="hero__lede">
        Countries, peaks, rivers, lakes and destinations you have starred. Kept in this browser's local storage —
        nothing is sent anywhere, and clearing your site data clears these.
      </p>
    </div>
    <button class="btn btn--sm" type="button" data-bookmarks-clear hidden>Clear all</button>
  </div>

  <div class="grid grid--3" data-bookmarks-list style="margin-top:26px"></div>

  <div class="card" data-bookmarks-empty hidden style="margin-top:26px">
    <div class="empty-state" style="padding:44px 20px">
      <span class="empty-state__icon">★</span>
      <p>
        <strong>Nothing saved yet.</strong><br>
        Look for the ★ button on any country, peak, river, lake or destination.
      </p>
      <div class="pill-row" style="justify-content:center;margin-top:8px">
        <a class="btn btn--sm" href="/countries">Browse countries</a>
        <a class="btn btn--sm" href="/waters">Browse waters</a>
        <a class="btn btn--sm" href="/travel">Browse destinations</a>
      </div>
    </div>
  </div>
</section>
