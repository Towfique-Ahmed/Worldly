/**
 * Worldly - shared UI behaviour: backdrop, theme, live UTC clock, scroll
 * reveals, global search, and the per-page wiring for the map-driven views.
 */
(function () {
  'use strict';

  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function $(selector, scope) { return (scope || document).querySelector(selector); }
  function $$(selector, scope) { return Array.prototype.slice.call((scope || document).querySelectorAll(selector)); }

  function compact(n) {
    var abs = Math.abs(n);
    if (abs >= 1e9) { return (n / 1e9).toFixed(2).replace(/\.?0+$/, '') + 'B'; }
    if (abs >= 1e6) { return (n / 1e6).toFixed(1).replace(/\.0$/, '') + 'M'; }
    if (abs >= 1e3) { return (n / 1e3).toFixed(1).replace(/\.0$/, '') + 'K'; }
    return String(Math.round(n));
  }

  /* ------------------------------------------------------------------ theme */

  function theme() {
    var stored = null;
    try { stored = localStorage.getItem('worldly:theme'); } catch (e) { /* private mode */ }

    var current = stored === 'night' ? 'night' : 'day';
    document.documentElement.dataset.theme = current;

    var icon = $('[data-theme-icon]');
    function paint() {
      if (icon) { icon.textContent = document.documentElement.dataset.theme === 'night' ? '☾' : '☀'; }
    }
    paint();

    var toggle = $('[data-theme-toggle]');
    if (!toggle) { return; }

    toggle.addEventListener('click', function () {
      var next = document.documentElement.dataset.theme === 'night' ? 'day' : 'night';
      document.documentElement.dataset.theme = next;
      paint();
      try { localStorage.setItem('worldly:theme', next); } catch (e) { /* ignore */ }
    });
  }

  /* ------------------------------------------------------------ main menu */

  function menus() {
    var groups = $$('[data-menu]');
    if (!groups.length) { return; }

    function closeAll(except) {
      groups.forEach(function (group) {
        if (group === except) { return; }
        group.classList.remove('is-open');
        $('.menu__trigger', group).setAttribute('aria-expanded', 'false');
      });
    }

    groups.forEach(function (group) {
      var trigger = $('.menu__trigger', group);
      trigger.addEventListener('click', function (event) {
        event.stopPropagation();
        var open = !group.classList.contains('is-open');
        closeAll(group);
        group.classList.toggle('is-open', open);
        trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
    });

    document.addEventListener('click', function () { closeAll(null); });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') { closeAll(null); }
    });
  }

  /* -------------------------------------------------------------- utc clock */

  function utcClock() {
    var nodes = $$('[data-utc-clock]');
    if (!nodes.length) { return; }

    function tick() {
      var now = new Date();
      var text = String(now.getUTCHours()).padStart(2, '0') + ':' +
                 String(now.getUTCMinutes()).padStart(2, '0') + ':' +
                 String(now.getUTCSeconds()).padStart(2, '0');
      nodes.forEach(function (node) { node.textContent = text; });
    }

    tick();
    setInterval(tick, 1000);
  }

  /* ----------------------------------------------------------- scroll reveal */

  function reveals() {
    var targets = $$('.reveal');
    if (!targets.length) { return; }

    if (reduceMotion || !('IntersectionObserver' in window)) {
      targets.forEach(function (node) { node.classList.add('is-in'); });
      return;
    }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) { return; }
        entry.target.style.transitionDelay = (entry.target.dataset.delay || 0) + 'ms';
        entry.target.classList.add('is-in');
        observer.unobserve(entry.target);
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });

    targets.forEach(function (node) { observer.observe(node); });
  }

  /* --------------------------------------------------------------- counters */

  function counters() {
    var nodes = $$('[data-count-to]');
    if (!nodes.length) { return; }

    function run(node) {
      var target = Number(node.dataset.countTo);
      var isCompact = node.dataset.countFormat === 'compact';
      var duration = 1400;
      var start = performance.now();

      function step(now) {
        var t = Math.min(1, (now - start) / duration);
        var eased = 1 - Math.pow(1 - t, 3);
        var value = target * eased;
        node.textContent = isCompact ? compact(value) : Math.round(value).toLocaleString();
        if (t < 1) { requestAnimationFrame(step); }
      }

      if (reduceMotion) {
        node.textContent = isCompact ? compact(target) : target.toLocaleString();
        return;
      }
      requestAnimationFrame(step);
    }

    if (!('IntersectionObserver' in window)) { nodes.forEach(run); return; }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) { return; }
        run(entry.target);
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.4 });

    nodes.forEach(function (node) { observer.observe(node); });
  }

  /* ----------------------------------------------------------------- meters */

  function meters() {
    var fills = $$('.meter__fill[data-width]');
    if (!fills.length) { return; }

    if (!('IntersectionObserver' in window)) {
      fills.forEach(function (fill) { fill.style.width = fill.dataset.width + '%'; });
      return;
    }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) { return; }
        entry.target.style.width = entry.target.dataset.width + '%';
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.3 });

    fills.forEach(function (fill) { observer.observe(fill); });
  }

  /* ------------------------------------------------------------------ toast */

  var toastTimer = null;
  function toast(message) {
    var node = document.getElementById('toast');
    if (!node) { return; }
    node.textContent = message;
    node.classList.add('is-up');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { node.classList.remove('is-up'); }, 2600);
  }
  window.worldlyToast = toast;

  /* ----------------------------------------------------------------- search */

  function globalSearch(map) {
    var box = $('[data-global-search]');
    if (!box) { return; }

    var input = $('input', box);
    var list = $('[data-suggest]', box);
    var timer = null;
    var results = [];
    var active = -1;

    function close() { list.hidden = true; active = -1; }

    function draw() {
      list.textContent = '';
      if (!results.length) { close(); return; }

      results.forEach(function (item, index) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'suggest__item' + (index === active ? ' is-active' : '');
        button.innerHTML = '<span>' + item.label + '</span><small>' + item.detail + '</small>';
        button.addEventListener('click', function () { pick(item); });
        list.appendChild(button);
      });

      list.hidden = false;
    }

    function pick(item) {
      close();
      input.value = '';
      if (map && item.lat !== undefined) {
        map.flyTo(item.lon, item.lat, item.type === 'country' ? 3.4 : 5.5);
        map.ping(item.lon, item.lat);
        if (item.type === 'country') {
          map.select(item.href.split('/').pop(), { fly: false });
        }
        toast('Flying to ' + item.label.replace(/^[^\s]+\s/, ''));
      } else {
        window.location.href = item.href;
      }
    }

    input.addEventListener('input', function () {
      clearTimeout(timer);
      var query = input.value.trim();
      if (query.length < 2) { close(); return; }

      timer = setTimeout(function () {
        fetch('/api/search?q=' + encodeURIComponent(query))
          .then(function (response) { return response.json(); })
          .then(function (payload) {
            results = payload.results || [];
            active = -1;
            draw();
          })
          .catch(function () { close(); });
      }, 180);
    });

    input.addEventListener('keydown', function (event) {
      if (list.hidden) { return; }
      if (event.key === 'ArrowDown') { event.preventDefault(); active = Math.min(results.length - 1, active + 1); draw(); }
      else if (event.key === 'ArrowUp') { event.preventDefault(); active = Math.max(0, active - 1); draw(); }
      else if (event.key === 'Enter') { event.preventDefault(); if (results[active]) { pick(results[active]); } else if (results[0]) { pick(results[0]); } }
      else if (event.key === 'Escape') { close(); }
    });

    document.addEventListener('click', function (event) {
      if (!box.contains(event.target)) { close(); }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === '/' && document.activeElement !== input) {
        event.preventDefault();
        input.focus();
      }
    });
  }

  /* ------------------------------------------------------------ detail panel */

  function detailPanel(map) {
    var panel = $('[data-detail]');
    if (!panel || !map) { return; }

    map.root.addEventListener('worldmap:select', function (event) {
      var country = event.detail;
      panel.innerHTML = '<div class="empty-state"><span class="empty-state__icon">🛰</span><p>Loading ' + country.name + '…</p></div>';

      fetch('/api/country/' + country.iso3)
        .then(function (response) { return response.json(); })
        .then(function (data) { renderDetail(panel, data); })
        .catch(function () {
          panel.innerHTML = '<div class="empty-state"><span class="empty-state__icon">⚠️</span><p>Could not load that country.</p></div>';
        });
    });
  }

  function renderDetail(panel, data) {
    var country = data.country;
    var capital = data.capital;

    var rows = [
      ['Continent', country.continent],
      ['Sub-region', country.subregion],
      ['Capital', capital ? capital.name : '-'],
      ['Population', Number(country.population).toLocaleString()],
      ['GDP', '$' + compact(country.gdp * 1e6)],
      ['GDP per person', '$' + Number(country.gdpPerCapita).toLocaleString()],
      ['Economy', country.economy || '-'],
      ['ISO codes', country.iso2 + ' · ' + country.iso3]
    ];

    var html = '' +
      '<div class="detail__flag">' + country.flag + '</div>' +
      '<div><h3 class="detail__name">' + country.name + '</h3>' +
      '<p class="detail__sub">' + (country.formalName || country.longName) + '</p></div>' +
      '<dl class="kv">' + rows.map(function (row) {
        return '<dt>' + row[0] + '</dt><dd>' + row[1] + '</dd>';
      }).join('') + '</dl>';

    if (data.cities && data.cities.length) {
      html += '<div><h4 style="margin:6px 0 8px;font-size:.8rem;letter-spacing:.1em;text-transform:uppercase;color:var(--text-faint)">Largest cities</h4>' +
        '<div class="chipset">' + data.cities.map(function (city) {
          return '<span class="chip" style="cursor:default">' + city.name + ' <small style="color:var(--text-faint)">' + compact(city.population) + '</small></span>';
        }).join('') + '</div></div>';
    }

    if (data.mountains && data.mountains.length) {
      html += '<div><h4 style="margin:6px 0 8px;font-size:.8rem;letter-spacing:.1em;text-transform:uppercase;color:var(--text-faint)">Peaks</h4>' +
        '<div class="chipset">' + data.mountains.map(function (peak) {
          return '<span class="chip" style="cursor:default">🏔 ' + peak.name + ' <small style="color:var(--text-faint)">' + peak.elevation.toLocaleString() + ' m</small></span>';
        }).join('') + '</div></div>';
    }

    if (data.places && data.places.length) {
      html += '<div><h4 style="margin:6px 0 8px;font-size:.8rem;letter-spacing:.1em;text-transform:uppercase;color:var(--text-faint)">Worth the trip</h4>' +
        '<div class="chipset">' + data.places.map(function (place) {
          return '<span class="chip" style="cursor:default">📍 ' + place.name + '</span>';
        }).join('') + '</div></div>';
    }

    html += '<a class="btn btn--sm" href="/country/' + country.iso3 + '" style="margin-top:auto">Open full profile →</a>';

    panel.innerHTML = html;
  }

  /* ------------------------------------------------------------- map toolbar */

  function mapToolbar(map) {
    if (!map) { return; }

    $$('[data-layer-toggle]').forEach(function (button) {
      button.addEventListener('click', function () {
        var name = button.dataset.layerToggle;
        var on = !button.classList.contains('is-on');
        button.classList.toggle('is-on', on);
        button.setAttribute('aria-pressed', String(on));
        map.setLayer(name, on);
      });
    });

    $$('[data-continent-focus]').forEach(function (button) {
      button.addEventListener('click', function () {
        var name = button.dataset.continentFocus;
        var already = button.classList.contains('is-on');

        $$('[data-continent-focus]').forEach(function (other) { other.classList.remove('is-on'); });

        if (already) {
          map.highlightContinent(null);
          map.reset();
          return;
        }

        button.classList.add('is-on');
        map.highlightContinent(name);

        var focus = (map.data.continents || {})[name];
        if (focus && focus.focus) {
          map.flyTo(focus.focus.lon, focus.focus.lat, focus.focus.zoom);
        }
      });
    });
  }

  /* --------------------------------------------------------- list filtering */

  function filterable() {
    $$('[data-filterable]').forEach(function (root) {
      var items = $$('[data-item]', root);
      var search = $('[data-filter-search]', root);
      var sort = $('[data-filter-sort]', root);
      var chips = $$('[data-filter-value]', root);
      var countNode = $('[data-filter-count]', root);
      var list = $('[data-filter-list]', root);
      var facet = '';

      function apply() {
        var query = (search && search.value.trim().toLowerCase()) || '';
        var shown = 0;

        items.forEach(function (item) {
          var haystack = (item.dataset.search || '').toLowerCase();
          var matchesQuery = !query || haystack.indexOf(query) !== -1;
          var matchesFacet = !facet || (item.dataset.facet || '').split('|').indexOf(facet) !== -1;
          var visible = matchesQuery && matchesFacet;

          item.hidden = !visible;
          if (visible) { shown++; }
        });

        if (countNode) { countNode.textContent = shown; }
      }

      function applySort() {
        if (!sort || !list) { return; }
        var key = sort.value;
        var direction = key.charAt(0) === '-' ? -1 : 1;
        var field = key.replace(/^-/, '');

        items.slice().sort(function (a, b) {
          var av = a.dataset['sort' + field.charAt(0).toUpperCase() + field.slice(1)];
          var bv = b.dataset['sort' + field.charAt(0).toUpperCase() + field.slice(1)];
          var an = Number(av), bn = Number(bv);
          if (!isNaN(an) && !isNaN(bn)) { return (an - bn) * direction; }
          return String(av).localeCompare(String(bv)) * direction;
        }).forEach(function (item) { list.appendChild(item); });
      }

      if (search) { search.addEventListener('input', apply); }
      if (sort) { sort.addEventListener('change', function () { applySort(); apply(); }); }

      chips.forEach(function (chip) {
        chip.addEventListener('click', function () {
          var value = chip.dataset.filterValue;
          var already = chip.classList.contains('is-on');
          chips.forEach(function (other) { other.classList.remove('is-on'); });

          facet = already ? '' : value;
          if (!already) { chip.classList.add('is-on'); }
          apply();
        });
      });

      applySort();
      apply();
    });
  }

  /* --------------------------------------------------------------- bootstrap */

  document.addEventListener('DOMContentLoaded', function () {
    theme();
    menus();
    utcClock();
    reveals();
    counters();
    meters();
    filterable();

    var mapRoots = $$('[data-worldmap]');
    var primary = null;

    mapRoots.forEach(function (root) {
      var map = new window.WorldMap(root);
      root.__worldmap = map;
      if (!primary) { primary = map; }
    });

    window.worldlyMap = primary;

    globalSearch(primary);
    detailPanel(primary);
    mapToolbar(primary);

    // Cards that fly the map somewhere instead of navigating.
    $$('[data-fly-to]').forEach(function (node) {
      node.addEventListener('click', function (event) {
        if (!primary) { return; }
        event.preventDefault();
        var lon = Number(node.dataset.lon);
        var lat = Number(node.dataset.lat);
        primary.flyTo(lon, lat, Number(node.dataset.zoom || 5));
        primary.ping(lon, lat);
        primary.root.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
      });
    });
  });
}());
