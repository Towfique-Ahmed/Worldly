/**
 * Live clocks for the time pages (/clocks, /time-zone/gmt, /time-zone/utc).
 *
 *   [data-live-clock="Zone/Name"]  a table or card clock, ticking every second
 *   [data-hero-clock]              the big UTC clock, plus optional
 *   [data-hero-date] [data-hero-local] [data-hero-zone] [data-hero-unix] [data-hero-iso]
 *   [data-hour-format="12|24"]     the 24-hour / AM-PM toggle, remembered in this browser
 */
(function () {
  'use strict';

  var hour12 = false;
  try { hour12 = localStorage.getItem('worldly:hour12') === '1'; } catch (e) { /* private mode */ }

  var liveNodes = Array.prototype.slice.call(document.querySelectorAll('[data-live-clock]'));
  var buttons = Array.prototype.slice.call(document.querySelectorAll('[data-hour-format]'));
  var hero = {
    clock: document.querySelector('[data-hero-clock]'),
    date: document.querySelector('[data-hero-date]'),
    local: document.querySelector('[data-hero-local]'),
    zone: document.querySelector('[data-hero-zone]'),
    unix: document.querySelector('[data-hero-unix]'),
    iso: document.querySelector('[data-hero-iso]')
  };

  function time(now, zone) {
    var options = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: hour12 };
    if (zone) { options.timeZone = zone; }
    return new Intl.DateTimeFormat('en-GB', options).format(now).toUpperCase();
  }

  function tick() {
    var now = new Date();

    liveNodes.forEach(function (node) {
      try { node.textContent = time(now, node.dataset.liveClock); }
      catch (e) { node.textContent = '--:--:--'; }
    });

    if (!hero.clock) { return; }

    hero.clock.textContent = time(now, 'UTC');
    if (hero.date) {
      hero.date.textContent = new Intl.DateTimeFormat('en-GB', {
        timeZone: 'UTC', weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
      }).format(now);
    }
    if (hero.local) { hero.local.textContent = time(now, null); }
    if (hero.zone) { hero.zone.textContent = '(' + (Intl.DateTimeFormat().resolvedOptions().timeZone || 'local') + ')'; }
    if (hero.unix) { hero.unix.textContent = String(Math.floor(now.getTime() / 1000)); }
    if (hero.iso) { hero.iso.textContent = now.toISOString().replace(/\.\d{3}Z$/, 'Z'); }
  }

  function paint() {
    buttons.forEach(function (button) {
      button.setAttribute('aria-pressed', String((button.dataset.hourFormat === '12') === hour12));
    });
  }

  buttons.forEach(function (button) {
    button.addEventListener('click', function () {
      hour12 = button.dataset.hourFormat === '12';
      try { localStorage.setItem('worldly:hour12', hour12 ? '1' : '0'); } catch (e) { /* ignore */ }
      paint();
      tick();
    });
  });

  paint();
  tick();
  setInterval(tick, 1000);
}());
