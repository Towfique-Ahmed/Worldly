/**
 * Worldly — clock wall, countdown timer, stopwatch and time converter.
 *
 * All zone arithmetic goes through Intl.DateTimeFormat with an explicit
 * timeZone, so daylight saving is handled by the browser's own tz database
 * rather than by hand-rolled offsets.
 */
(function () {
  'use strict';

  function $(selector, scope) { return (scope || document).querySelector(selector); }
  function $$(selector, scope) { return Array.prototype.slice.call((scope || document).querySelectorAll(selector)); }
  function pad(n, width) { return String(Math.floor(Math.abs(n))).padStart(width || 2, '0'); }

  /**
   * Wall-clock parts for an instant in a given IANA zone.
   */
  function partsIn(date, zone) {
    var formatter = new Intl.DateTimeFormat('en-GB', {
      timeZone: zone,
      hour12: false,
      year: 'numeric', month: 'short', day: '2-digit', weekday: 'short',
      hour: '2-digit', minute: '2-digit', second: '2-digit'
    });

    var out = {};
    formatter.formatToParts(date).forEach(function (part) { out[part.type] = part.value; });
    out.hour = out.hour === '24' ? '00' : out.hour;

    return {
      weekday: out.weekday,
      day: out.day,
      month: out.month,
      year: out.year,
      hour: Number(out.hour),
      minute: Number(out.minute),
      second: Number(out.second),
      date: out.weekday + ', ' + out.day + ' ' + out.month + ' ' + out.year
    };
  }

  /** Offset of `zone` from UTC in minutes at `date`. */
  function offsetMinutes(date, zone) {
    var formatter = new Intl.DateTimeFormat('en-US', {
      timeZone: zone, hour12: false,
      year: 'numeric', month: '2-digit', day: '2-digit',
      hour: '2-digit', minute: '2-digit', second: '2-digit'
    });

    var out = {};
    formatter.formatToParts(date).forEach(function (part) { out[part.type] = part.value; });
    if (out.hour === '24') { out.hour = '00'; }

    var asUtc = Date.UTC(
      Number(out.year), Number(out.month) - 1, Number(out.day),
      Number(out.hour), Number(out.minute), Number(out.second)
    );

    return Math.round((asUtc - date.getTime() - date.getMilliseconds() * 0) / 60000);
  }

  function offsetLabel(date, zone) {
    var minutes = offsetMinutes(date, zone);
    var sign = minutes < 0 ? '−' : '+';
    minutes = Math.abs(minutes);
    return 'UTC' + sign + pad(minutes / 60) + ':' + pad(minutes % 60);
  }

  function abbreviation(date, zone) {
    try {
      var parts = new Intl.DateTimeFormat('en-US', { timeZone: zone, timeZoneName: 'short' }).formatToParts(date);
      for (var i = 0; i < parts.length; i++) {
        if (parts[i].type === 'timeZoneName') { return parts[i].value; }
      }
    } catch (e) { /* fall through */ }
    return '';
  }

  /* ----------------------------------------------------------- clock wall */

  var CLOCK_STORE = 'worldly:clocks';

  function loadWall(defaults) {
    try {
      var raw = localStorage.getItem(CLOCK_STORE);
      if (raw) {
        var parsed = JSON.parse(raw);
        if (Array.isArray(parsed) && parsed.length) { return parsed; }
      }
    } catch (e) { /* ignore */ }
    return defaults;
  }

  function saveWall(zones) {
    try { localStorage.setItem(CLOCK_STORE, JSON.stringify(zones)); } catch (e) { /* ignore */ }
  }

  function clockFace() {
    var ns = 'http://www.w3.org/2000/svg';
    var svg = document.createElementNS(ns, 'svg');
    svg.setAttribute('viewBox', '0 0 100 100');
    svg.setAttribute('class', 'clock__face');

    var disc = document.createElementNS(ns, 'circle');
    disc.setAttribute('cx', 50); disc.setAttribute('cy', 50); disc.setAttribute('r', 44);
    disc.setAttribute('class', 'clock-daynight');
    svg.appendChild(disc);

    var ring = document.createElementNS(ns, 'circle');
    ring.setAttribute('cx', 50); ring.setAttribute('cy', 50); ring.setAttribute('r', 44);
    ring.setAttribute('class', 'clock-face-bg');
    svg.appendChild(ring);

    for (var i = 0; i < 60; i++) {
      var major = i % 5 === 0;
      var angle = (i / 60) * Math.PI * 2;
      var outer = 40, inner = major ? 33 : 37;
      var tick = document.createElementNS(ns, 'line');
      tick.setAttribute('x1', (50 + Math.sin(angle) * inner).toFixed(2));
      tick.setAttribute('y1', (50 - Math.cos(angle) * inner).toFixed(2));
      tick.setAttribute('x2', (50 + Math.sin(angle) * outer).toFixed(2));
      tick.setAttribute('y2', (50 - Math.cos(angle) * outer).toFixed(2));
      tick.setAttribute('class', 'clock-tick' + (major ? ' clock-tick--major' : ''));
      svg.appendChild(tick);
    }

    ['h', 'm', 's'].forEach(function (kind) {
      var length = kind === 'h' ? 22 : kind === 'm' ? 32 : 36;
      var hand = document.createElementNS(ns, 'line');
      hand.setAttribute('x1', 50); hand.setAttribute('y1', 54);
      hand.setAttribute('x2', 50); hand.setAttribute('y2', 50 - length);
      hand.setAttribute('class', 'clock-hand clock-hand--' + kind);
      hand.dataset.hand = kind;
      svg.appendChild(hand);
    });

    var pin = document.createElementNS(ns, 'circle');
    pin.setAttribute('cx', 50); pin.setAttribute('cy', 50); pin.setAttribute('r', 2.6);
    pin.setAttribute('class', 'clock-pin');
    svg.appendChild(pin);

    return svg;
  }

  function dayTint(hour) {
    if (hour >= 6 && hour < 9) { return 'rgba(255, 176, 92, .16)'; }
    if (hour >= 9 && hour < 17) { return 'rgba(120, 200, 255, .15)'; }
    if (hour >= 17 && hour < 20) { return 'rgba(255, 132, 108, .17)'; }
    return 'rgba(40, 60, 130, .28)';
  }

  function clockWall() {
    var wall = $('[data-clockwall]');
    if (!wall) { return; }

    var catalogue = JSON.parse($('[data-zone-catalogue]').textContent);
    var byZone = {};
    catalogue.forEach(function (entry) { byZone[entry.zone] = entry; });

    var zones = loadWall(JSON.parse(wall.dataset.default));
    var cards = [];

    function build() {
      wall.textContent = '';
      cards = [];

      zones.forEach(function (zone) {
        var meta = byZone[zone] || { city: zone.split('/').pop().replace(/_/g, ' '), country: zone, flag: '🕒' };

        var card = document.createElement('div');
        card.className = 'card clock reveal is-in';
        card.style.position = 'relative';

        var remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'clock__remove';
        remove.setAttribute('aria-label', 'Remove ' + meta.city);
        remove.textContent = '×';
        remove.addEventListener('click', function () {
          zones = zones.filter(function (z) { return z !== zone; });
          saveWall(zones);
          build();
        });

        var face = clockFace();
        var digital = document.createElement('div');
        digital.className = 'clock__digital';

        var city = document.createElement('div');
        city.className = 'clock__city';
        city.textContent = (meta.flag || '') + ' ' + meta.city;

        var info = document.createElement('div');
        info.className = 'clock__meta';

        card.appendChild(remove);
        card.appendChild(face);
        card.appendChild(digital);
        card.appendChild(city);
        card.appendChild(info);
        wall.appendChild(card);

        cards.push({ zone: zone, face: face, digital: digital, info: info, card: card });
      });
    }

    function tick() {
      var now = new Date();

      cards.forEach(function (entry) {
        var p = partsIn(now, entry.zone);
        entry.digital.textContent = pad(p.hour) + ':' + pad(p.minute) + ':' + pad(p.second);
        entry.info.textContent = p.date + ' · ' + offsetLabel(now, entry.zone) +
          (abbreviation(now, entry.zone) ? ' · ' + abbreviation(now, entry.zone) : '');

        var seconds = p.second;
        var minutes = p.minute + seconds / 60;
        var hours = (p.hour % 12) + minutes / 60;

        $('[data-hand="h"]', entry.face).style.transform = 'rotate(' + (hours * 30) + 'deg)';
        $('[data-hand="m"]', entry.face).style.transform = 'rotate(' + (minutes * 6) + 'deg)';
        var hand = $('[data-hand="s"]', entry.face);
        hand.style.transition = seconds === 0 ? 'none' : '';
        hand.style.transform = 'rotate(' + (seconds * 6) + 'deg)';

        $('.clock-daynight', entry.face).setAttribute('fill', dayTint(p.hour));
      });
    }

    var picker = $('[data-clock-add]');
    if (picker) {
      picker.addEventListener('change', function () {
        var zone = picker.value;
        if (!zone) { return; }
        if (zones.indexOf(zone) === -1) {
          zones.push(zone);
          saveWall(zones);
          build();
          tick();
          if (window.worldlyToast) { window.worldlyToast('Added ' + zone.split('/').pop().replace(/_/g, ' ')); }
        }
        picker.value = '';
      });
    }

    var resetButton = $('[data-clock-reset]');
    if (resetButton) {
      resetButton.addEventListener('click', function () {
        zones = JSON.parse(wall.dataset.default);
        saveWall(zones);
        build();
        tick();
      });
    }

    build();
    tick();
    setInterval(tick, 1000);
  }

  /* --------------------------------------------------------------- timer */

  function beep() {
    try {
      var Ctx = window.AudioContext || window.webkitAudioContext;
      if (!Ctx) { return; }
      var ctx = new Ctx();
      var now = ctx.currentTime;

      [0, 0.28, 0.56].forEach(function (offset) {
        var osc = ctx.createOscillator();
        var gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(880, now + offset);
        gain.gain.setValueAtTime(0.0001, now + offset);
        gain.gain.exponentialRampToValueAtTime(0.32, now + offset + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, now + offset + 0.22);
        osc.connect(gain).connect(ctx.destination);
        osc.start(now + offset);
        osc.stop(now + offset + 0.24);
      });

      setTimeout(function () { ctx.close(); }, 1400);
    } catch (e) { /* audio is a nicety, not a requirement */ }
  }

  function timer() {
    var root = $('[data-timer]');
    if (!root) { return; }

    var display = $('[data-timer-display]', root);
    var ring = $('[data-timer-ring]', root);
    var startButton = $('[data-timer-start]', root);
    var resetButton = $('[data-timer-reset]', root);
    var inputs = { h: $('[data-timer-h]', root), m: $('[data-timer-m]', root), s: $('[data-timer-s]', root) };

    var circumference = 2 * Math.PI * Number(ring.getAttribute('r'));
    ring.style.strokeDasharray = circumference;

    var total = 0;
    var remaining = 0;
    var endsAt = 0;
    var running = false;
    var handle = null;

    function readInputs() {
      return (Number(inputs.h.value) || 0) * 3600 +
             (Number(inputs.m.value) || 0) * 60 +
             (Number(inputs.s.value) || 0);
    }

    function paint() {
      var value = Math.max(0, remaining);
      var hours = Math.floor(value / 3600);
      var minutes = Math.floor((value % 3600) / 60);
      var seconds = Math.floor(value % 60);

      display.textContent = (hours ? pad(hours) + ':' : '') + pad(minutes) + ':' + pad(seconds);

      var fraction = total > 0 ? value / total : 0;
      ring.style.strokeDashoffset = circumference * (1 - fraction);
    }

    function stop() {
      running = false;
      clearInterval(handle);
      handle = null;
      startButton.textContent = '▶ Start';
    }

    function finish() {
      stop();
      remaining = 0;
      paint();
      root.classList.add('is-alarming');
      beep();
      if (window.worldlyToast) { window.worldlyToast("⏰ Time's up!"); }
      setTimeout(function () { root.classList.remove('is-alarming'); }, 2600);
    }

    startButton.addEventListener('click', function () {
      if (running) {
        remaining = (endsAt - Date.now()) / 1000;
        stop();
        return;
      }

      if (remaining <= 0) {
        total = readInputs();
        remaining = total;
      }
      if (remaining <= 0) {
        if (window.worldlyToast) { window.worldlyToast('Set a duration first'); }
        return;
      }

      if (!total) { total = remaining; }
      endsAt = Date.now() + remaining * 1000;
      running = true;
      startButton.textContent = '❚❚ Pause';

      handle = setInterval(function () {
        remaining = (endsAt - Date.now()) / 1000;
        if (remaining <= 0) { finish(); return; }
        paint();
      }, 100);

      paint();
    });

    resetButton.addEventListener('click', function () {
      stop();
      total = readInputs();
      remaining = total;
      paint();
    });

    $$('[data-timer-preset]', root).forEach(function (button) {
      button.addEventListener('click', function () {
        var seconds = Number(button.dataset.timerPreset);
        stop();
        inputs.h.value = Math.floor(seconds / 3600);
        inputs.m.value = Math.floor((seconds % 3600) / 60);
        inputs.s.value = seconds % 60;
        total = seconds;
        remaining = seconds;
        paint();
      });
    });

    Object.keys(inputs).forEach(function (key) {
      inputs[key].addEventListener('input', function () {
        if (running) { return; }
        total = readInputs();
        remaining = total;
        paint();
      });
    });

    total = readInputs();
    remaining = total;
    paint();
  }

  /* ----------------------------------------------------------- stopwatch */

  function stopwatch() {
    var root = $('[data-stopwatch]');
    if (!root) { return; }

    var display = $('[data-sw-display]', root);
    var startButton = $('[data-sw-start]', root);
    var lapButton = $('[data-sw-lap]', root);
    var resetButton = $('[data-sw-reset]', root);
    var lapList = $('[data-sw-laps]', root);

    var elapsed = 0;
    var startedAt = 0;
    var running = false;
    var frame = null;
    var laps = [];

    function format(ms) {
      var minutes = Math.floor(ms / 60000);
      var seconds = Math.floor((ms % 60000) / 1000);
      var hundredths = Math.floor((ms % 1000) / 10);
      return pad(minutes) + ':' + pad(seconds) + '.' + pad(hundredths);
    }

    function paint() {
      var total = elapsed + (running ? Date.now() - startedAt : 0);
      display.textContent = format(total);
      if (running) { frame = requestAnimationFrame(paint); }
    }

    startButton.addEventListener('click', function () {
      if (running) {
        elapsed += Date.now() - startedAt;
        running = false;
        cancelAnimationFrame(frame);
        startButton.textContent = '▶ Start';
      } else {
        startedAt = Date.now();
        running = true;
        startButton.textContent = '❚❚ Pause';
        paint();
      }
    });

    lapButton.addEventListener('click', function () {
      var total = elapsed + (running ? Date.now() - startedAt : 0);
      if (!total) { return; }

      var previous = laps.length ? laps[laps.length - 1] : 0;
      laps.push(total);

      var row = document.createElement('div');
      row.className = 'lap';
      row.innerHTML = '<span>Lap ' + laps.length + '</span><span>+' + format(total - previous) + '</span><strong>' + format(total) + '</strong>';
      lapList.insertBefore(row, lapList.firstChild);
    });

    resetButton.addEventListener('click', function () {
      running = false;
      cancelAnimationFrame(frame);
      elapsed = 0;
      laps = [];
      lapList.textContent = '';
      startButton.textContent = '▶ Start';
      display.textContent = '00:00.00';
    });

    display.textContent = '00:00.00';
  }

  /* ----------------------------------------------------------- converter */

  function converter() {
    var root = $('[data-converter]');
    if (!root) { return; }

    var fromSelect = $('[data-conv-from]', root);
    var toSelect = $('[data-conv-to]', root);
    var whenInput = $('[data-conv-when]', root);
    var swapButton = $('[data-conv-swap]', root);
    var nowButton = $('[data-conv-now]', root);
    var output = $('[data-conv-output]', root);
    var strip = $('[data-conv-strip]', root);
    var catalogue = JSON.parse($('[data-zone-catalogue]').textContent);

    function localInputValue(date, zone) {
      var p = partsIn(date, zone);
      var month = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'].indexOf(p.month) + 1;
      return p.year + '-' + pad(month) + '-' + pad(Number(p.day)) + 'T' + pad(p.hour) + ':' + pad(p.minute);
    }

    /**
     * Turn "wall clock time in `zone`" into a real instant.
     * Applying the offset once and re-checking handles the DST boundary case.
     */
    function instantFromWallTime(value, zone) {
      var guess = new Date(value + ':00Z');
      if (isNaN(guess.getTime())) { return new Date(); }

      var offset = offsetMinutes(guess, zone);
      var instant = new Date(guess.getTime() - offset * 60000);
      var corrected = offsetMinutes(instant, zone);
      if (corrected !== offset) {
        instant = new Date(guess.getTime() - corrected * 60000);
      }
      return instant;
    }

    function render() {
      var instant = instantFromWallTime(whenInput.value, fromSelect.value);
      var from = partsIn(instant, fromSelect.value);
      var to = partsIn(instant, toSelect.value);

      var difference = (offsetMinutes(instant, toSelect.value) - offsetMinutes(instant, fromSelect.value)) / 60;
      var sign = difference > 0 ? '+' : difference < 0 ? '−' : '';
      var differenceText = difference === 0
        ? 'Same clock time'
        : sign + Math.abs(difference).toFixed(Math.abs(difference) % 1 ? 2 : 0).replace(/\.00$/, '') + ' hours';

      var dayNote = '';
      if (from.date !== to.date) {
        dayNote = new Date(to.year, 0, 1) && (to.day + to.month) !== (from.day + from.month)
          ? '<span class="tag">' + to.date.split(',')[0] + ' — different day</span>'
          : '';
      }

      output.innerHTML = '' +
        '<div class="grid grid--2" style="align-items:center">' +
          '<div>' +
            '<div class="field"><label>' + fromSelect.value.replace(/_/g, ' ') + '</label></div>' +
            '<div class="bigtime">' + pad(from.hour) + ':' + pad(from.minute) + '</div>' +
            '<p class="detail__sub">' + from.date + ' · ' + offsetLabel(instant, fromSelect.value) + '</p>' +
          '</div>' +
          '<div>' +
            '<div class="field"><label>' + toSelect.value.replace(/_/g, ' ') + '</label></div>' +
            '<div class="bigtime">' + pad(to.hour) + ':' + pad(to.minute) + '</div>' +
            '<p class="detail__sub">' + to.date + ' · ' + offsetLabel(instant, toSelect.value) + ' ' + dayNote + '</p>' +
          '</div>' +
        '</div>' +
        '<p style="margin:14px 0 0;color:var(--text-dim)">Difference: <strong>' + differenceText + '</strong></p>';

      // Day bar marker: position the target hour along a 24-hour strip.
      var bar = $('[data-daybar]', root);
      if (bar) {
        var fraction = (to.hour + to.minute / 60) / 24;
        $('[data-daybar-marker]', bar).style.left = (fraction * 100) + '%';
        var label = $('[data-daybar-label]', bar);
        // Keep the label inside the bar at either end.
        label.style.left = Math.min(94, Math.max(6, fraction * 100)) + '%';
        label.textContent = pad(to.hour) + ':' + pad(to.minute);
      }

      // Same instant across the featured zones.
      strip.textContent = '';
      catalogue.slice(0, 12).forEach(function (entry) {
        var p = partsIn(instant, entry.zone);
        var cell = document.createElement('div');
        cell.className = 'card';
        cell.style.padding = '13px 15px';
        cell.innerHTML = '<div style="font-size:.78rem;color:var(--text-faint)">' + entry.flag + ' ' + entry.city + '</div>' +
          '<div style="font-size:1.32rem;font-weight:700;font-variant-numeric:tabular-nums">' + pad(p.hour) + ':' + pad(p.minute) + '</div>' +
          '<div style="font-size:.72rem;color:var(--text-faint)">' + p.weekday + ' · ' + offsetLabel(instant, entry.zone) + '</div>';
        strip.appendChild(cell);
      });
    }

    swapButton.addEventListener('click', function () {
      var instant = instantFromWallTime(whenInput.value, fromSelect.value);
      var target = toSelect.value;
      toSelect.value = fromSelect.value;
      fromSelect.value = target;
      whenInput.value = localInputValue(instant, fromSelect.value);
      render();
    });

    nowButton.addEventListener('click', function () {
      whenInput.value = localInputValue(new Date(), fromSelect.value);
      render();
    });

    [fromSelect, toSelect, whenInput].forEach(function (node) {
      node.addEventListener('change', render);
      node.addEventListener('input', render);
    });

    $$('[data-conv-quick]', root).forEach(function (button) {
      button.addEventListener('click', function () {
        toSelect.value = button.dataset.convQuick;
        render();
      });
    });

    whenInput.value = localInputValue(new Date(), fromSelect.value);
    render();
  }

  document.addEventListener('DOMContentLoaded', function () {
    clockWall();
    timer();
    stopwatch();
    converter();
  });
}());
