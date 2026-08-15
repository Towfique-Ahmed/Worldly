/**
 * Worldly — bookmarks, command palette, map style controls, the compare tool
 * and the quiz. Everything here is progressive: each block returns early when
 * its hooks are not on the page.
 */
(function () {
  'use strict';

  function $(selector, scope) { return (scope || document).querySelector(selector); }
  function $$(selector, scope) { return Array.prototype.slice.call((scope || document).querySelectorAll(selector)); }

  function toast(message) {
    if (window.worldlyToast) { window.worldlyToast(message); }
  }

  function compact(n) {
    var abs = Math.abs(n);
    if (abs >= 1e9) { return (n / 1e9).toFixed(2).replace(/\.?0+$/, '') + 'B'; }
    if (abs >= 1e6) { return (n / 1e6).toFixed(1).replace(/\.0$/, '') + 'M'; }
    if (abs >= 1e3) { return (n / 1e3).toFixed(1).replace(/\.0$/, '') + 'K'; }
    return String(Math.round(n));
  }

  /* ------------------------------------------------------------- bookmarks */

  var STORE = 'worldly:bookmarks';

  var Bookmarks = {
    all: function () {
      try {
        var raw = localStorage.getItem(STORE);
        var list = raw ? JSON.parse(raw) : [];
        return Array.isArray(list) ? list : [];
      } catch (e) {
        return [];
      }
    },

    has: function (id) { return this.all().indexOf(id) !== -1; },

    toggle: function (id) {
      var list = this.all();
      var index = list.indexOf(id);

      if (index === -1) { list.push(id); } else { list.splice(index, 1); }

      try { localStorage.setItem(STORE, JSON.stringify(list)); } catch (e) { /* private mode */ }
      document.dispatchEvent(new CustomEvent('worldly:bookmarks', { detail: { ids: list } }));

      return index === -1;
    },

    clear: function () {
      try { localStorage.removeItem(STORE); } catch (e) { /* ignore */ }
      document.dispatchEvent(new CustomEvent('worldly:bookmarks', { detail: { ids: [] } }));
    }
  };

  window.worldlyBookmarks = Bookmarks;

  function paintBookmarkButtons() {
    $$('[data-bookmark]').forEach(function (button) {
      var on = Bookmarks.has(button.dataset.bookmark);
      button.classList.toggle('is-on', on);
      button.setAttribute('aria-pressed', String(on));
      button.title = on ? 'Remove from bookmarks' : 'Save to bookmarks';
      if (!button.textContent.trim()) { button.textContent = '★'; }
    });

    var count = Bookmarks.all().length;
    $$('[data-bookmark-count]').forEach(function (node) {
      node.textContent = count;
      node.hidden = count === 0;
    });
  }

  function bookmarkButtons() {
    document.addEventListener('click', function (event) {
      var button = event.target.closest ? event.target.closest('[data-bookmark]') : null;
      if (!button) { return; }

      event.preventDefault();
      event.stopPropagation();

      var added = Bookmarks.toggle(button.dataset.bookmark);
      button.classList.add('just-saved');
      setTimeout(function () { button.classList.remove('just-saved'); }, 520);

      toast(added ? 'Saved to bookmarks ★' : 'Removed from bookmarks');
    });

    document.addEventListener('worldly:bookmarks', paintBookmarkButtons);
    paintBookmarkButtons();
  }

  /** The /bookmarks page rehydrates saved ids from the server. */
  function bookmarksPage() {
    var root = $('[data-bookmarks-page]');
    if (!root) { return; }

    var list = $('[data-bookmarks-list]', root);
    var empty = $('[data-bookmarks-empty]', root);
    var clearButton = $('[data-bookmarks-clear]', root);

    function render() {
      var ids = Bookmarks.all();

      if (!ids.length) {
        list.innerHTML = '';
        empty.hidden = false;
        if (clearButton) { clearButton.hidden = true; }
        return;
      }

      empty.hidden = true;
      if (clearButton) { clearButton.hidden = false; }
      list.innerHTML = '<div class="empty-state"><span class="empty-state__icon">★</span><p>Loading your saved places…</p></div>';

      fetch('/api/bookmarks?ids=' + encodeURIComponent(ids.join(',')))
        .then(function (response) { return response.json(); })
        .then(function (payload) {
          var items = payload.items || [];
          if (!items.length) {
            list.innerHTML = '';
            empty.hidden = false;
            return;
          }

          list.innerHTML = items.map(function (item, index) {
            return '<article class="card reveal is-in" style="transition-delay:' + (index * 40) + 'ms">' +
              '<div style="display:flex;align-items:flex-start;gap:12px">' +
                '<div style="flex:1;min-width:0">' +
                  '<h3 style="font-size:1.05rem;margin:0 0 4px">' + item.title + '</h3>' +
                  '<p style="margin:0;font-size:.86rem;color:var(--text-dim)">' + item.detail + '</p>' +
                '</div>' +
                '<button class="bookmark is-on" type="button" data-bookmark="' + item.id + '" aria-pressed="true">★</button>' +
              '</div>' +
              '<div class="pill-row" style="margin-top:14px">' +
                '<a class="btn btn--sm" href="' + item.href + '">Open →</a>' +
                '<span class="tag">' + item.type + '</span>' +
              '</div>' +
            '</article>';
          }).join('');

          paintBookmarkButtons();
        })
        .catch(function () {
          list.innerHTML = '<div class="empty-state"><span class="empty-state__icon">⚠️</span><p>Could not load your bookmarks.</p></div>';
        });
    }

    if (clearButton) {
      clearButton.addEventListener('click', function () {
        Bookmarks.clear();
        toast('Bookmarks cleared');
      });
    }

    document.addEventListener('worldly:bookmarks', render);
    render();
  }

  /* -------------------------------------------------------- command palette */

  var STATIC_COMMANDS = [
    { label: 'Explore the map', detail: 'Home', href: '/', icon: '🌍' },
    { label: 'Continents', detail: 'Seven landmasses', href: '/continents', icon: '🗺' },
    { label: 'Countries & regions', detail: 'Browse and filter', href: '/countries', icon: '🏳' },
    { label: 'Mountains', detail: 'Peaks to scale', href: '/mountains', icon: '🏔' },
    { label: 'Rivers, lakes & oceans', detail: 'Water', href: '/waters', icon: '🌊' },
    { label: 'Travel places', detail: 'Destinations', href: '/travel', icon: '🧭' },
    { label: 'Compare countries', detail: 'Side by side', href: '/compare', icon: '⚖️' },
    { label: 'Atlas quiz', detail: 'Test yourself', href: '/quiz', icon: '🎯' },
    { label: 'Your bookmarks', detail: 'Saved places', href: '/bookmarks', icon: '★' },
    { label: 'World clock & timer', detail: 'Clocks', href: '/clocks', icon: '⏱' },
    { label: 'Time converter', detail: 'Across zones', href: '/converter', icon: '🔁' },
    { label: 'Toggle light / dark theme', detail: 'Appearance', action: 'theme', icon: '◐' }
  ];

  function commandPalette() {
    var palette = $('[data-palette]');
    if (!palette) { return; }

    var input = $('[data-palette-input]', palette);
    var list = $('[data-palette-list]', palette);
    var results = [];
    var active = 0;
    var timer = null;

    function open() {
      palette.classList.add('is-open');
      palette.setAttribute('aria-hidden', 'false');
      input.value = '';
      results = STATIC_COMMANDS.slice();
      active = 0;
      draw();
      setTimeout(function () { input.focus(); }, 40);
    }

    function close() {
      palette.classList.remove('is-open');
      palette.setAttribute('aria-hidden', 'true');
    }

    function draw() {
      if (!results.length) {
        list.innerHTML = '<p class="palette__empty">Nothing matches that. Try a country, city, peak, river or lake.</p>';
        return;
      }

      list.innerHTML = results.map(function (item, index) {
        return '<button type="button" class="palette__item' + (index === active ? ' is-active' : '') + '" data-index="' + index + '">' +
          '<span>' + (item.icon || '•') + '</span>' +
          '<span>' + item.label + '</span>' +
          '<small>' + (item.detail || '') + '</small>' +
        '</button>';
      }).join('');

      $$('.palette__item', list).forEach(function (button) {
        button.addEventListener('click', function () { run(results[Number(button.dataset.index)]); });
      });

      var activeNode = $('.palette__item.is-active', list);
      if (activeNode && activeNode.scrollIntoView) { activeNode.scrollIntoView({ block: 'nearest' }); }
    }

    function run(item) {
      if (!item) { return; }
      close();

      if (item.action === 'theme') {
        var toggle = $('[data-theme-toggle]');
        if (toggle) { toggle.click(); }
        return;
      }

      window.location.href = item.href;
    }

    function search(query) {
      if (query.length < 2) {
        results = STATIC_COMMANDS.slice();
        active = 0;
        draw();
        return;
      }

      var local = STATIC_COMMANDS.filter(function (item) {
        return item.label.toLowerCase().indexOf(query.toLowerCase()) !== -1;
      });

      fetch('/api/search?q=' + encodeURIComponent(query))
        .then(function (response) { return response.json(); })
        .then(function (payload) {
          results = local.concat((payload.results || []).map(function (item) {
            return { label: item.label, detail: item.detail, href: item.href, icon: '↗' };
          }));
          active = 0;
          draw();
        })
        .catch(function () { results = local; active = 0; draw(); });
    }

    input.addEventListener('input', function () {
      clearTimeout(timer);
      var query = input.value.trim();
      timer = setTimeout(function () { search(query); }, 150);
    });

    palette.addEventListener('click', function (event) {
      if (event.target === palette) { close(); }
    });

    document.addEventListener('keydown', function (event) {
      var isOpen = palette.classList.contains('is-open');

      if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        isOpen ? close() : open();
        return;
      }

      if (!isOpen) { return; }

      if (event.key === 'Escape') { close(); }
      else if (event.key === 'ArrowDown') { event.preventDefault(); active = Math.min(results.length - 1, active + 1); draw(); }
      else if (event.key === 'ArrowUp') { event.preventDefault(); active = Math.max(0, active - 1); draw(); }
      else if (event.key === 'Enter') { event.preventDefault(); run(results[active]); }
    });

    $$('[data-palette-open]').forEach(function (button) {
      button.addEventListener('click', open);
    });
  }

  /* ----------------------------------------------------- map style controls */

  function mapStyleControls() {
    var map = window.worldlyMap;
    if (!map) { return; }

    $$('[data-map-style]').forEach(function (button) {
      button.addEventListener('click', function () {
        $$('[data-map-style]').forEach(function (other) { other.classList.remove('is-on'); });
        button.classList.add('is-on');
        map.applyStyle(button.dataset.mapStyle);
      });
    });

    var measureButton = $('[data-measure-toggle]');
    var readout = $('[data-measure-readout]');

    if (measureButton) {
      measureButton.addEventListener('click', function () {
        var on = !measureButton.classList.contains('is-on');
        measureButton.classList.add.apply(measureButton.classList, on ? ['is-on'] : []);
        if (!on) { measureButton.classList.remove('is-on'); }
        measureButton.setAttribute('aria-pressed', String(on));
        map.setMeasuring(on);

        if (readout) { readout.hidden = true; }
        toast(on ? 'Click two points to measure the great-circle distance' : 'Measuring off');
      });
    }

    map.root.addEventListener('worldmap:measure', function (event) {
      if (!readout) { return; }
      var detail = event.detail;

      if (!detail.to) {
        readout.hidden = false;
        readout.innerHTML = '<span>📍 From <strong style="font-size:1rem">' + detail.from.label + '</strong> — now pick a second point.</span>';
        return;
      }

      readout.hidden = false;
      readout.innerHTML =
        '<span>' + detail.from.label + ' → ' + detail.to.label + '</span>' +
        '<strong>' + Math.round(detail.km).toLocaleString() + ' km</strong>' +
        '<span style="color:var(--text-faint)">' + Math.round(detail.km * 0.621371).toLocaleString() + ' mi · ' +
        (detail.km / 900).toFixed(1) + ' h by jet</span>';
    });
  }

  /* ---------------------------------------------------------------- compare */

  function comparePage() {
    var root = $('[data-compare]');
    if (!root) { return; }

    var left = $('[data-compare-a]', root);
    var right = $('[data-compare-b]', root);
    var output = $('[data-compare-output]', root);
    var swap = $('[data-compare-swap]', root);
    var cache = {};

    function load(iso3) {
      if (cache[iso3]) { return Promise.resolve(cache[iso3]); }
      return fetch('/api/country/' + iso3)
        .then(function (response) { return response.json(); })
        .then(function (payload) { cache[iso3] = payload; return payload; });
    }

    function row(label, a, b, aRaw, bRaw, higherWins) {
      var aWins = '', bWins = '';
      if (higherWins !== null && typeof aRaw === 'number' && typeof bRaw === 'number' && aRaw !== bRaw) {
        var aBetter = higherWins ? aRaw > bRaw : aRaw < bRaw;
        aWins = aBetter ? ' is-winner' : '';
        bWins = aBetter ? '' : ' is-winner';
      }

      return '<div class="compare-grid__label">' + label + '</div>' +
             '<div class="compare-grid__value' + aWins + '">' + a + '</div>' +
             '<div class="compare-grid__value' + bWins + '">' + b + '</div>';
    }

    function render() {
      if (left.value === right.value) {
        output.innerHTML = '<div class="empty-state" style="padding:40px"><span class="empty-state__icon">⚖️</span><p>Pick two different countries.</p></div>';
        return;
      }

      Promise.all([load(left.value), load(right.value)]).then(function (pair) {
        var a = pair[0].country, b = pair[1].country;
        var capA = pair[0].capital, capB = pair[1].capital;

        var html = '<div class="compare-grid">' +
          '<div class="compare-grid__head"></div>' +
          '<div class="compare-grid__head"><span class="compare-grid__flag">' + a.flag + '</span><strong>' + a.name + '</strong></div>' +
          '<div class="compare-grid__head"><span class="compare-grid__flag">' + b.flag + '</span><strong>' + b.name + '</strong></div>' +
          row('Continent', a.continent, b.continent, null, null, null) +
          row('Capital', capA ? capA.name : '—', capB ? capB.name : '—', null, null, null) +
          row('Population', a.population.toLocaleString(), b.population.toLocaleString(), a.population, b.population, true) +
          row('Area', a.area ? a.area.toLocaleString() + ' km²' : '—', b.area ? b.area.toLocaleString() + ' km²' : '—', a.area, b.area, true) +
          row('Density', a.density ? a.density.toLocaleString() + ' /km²' : '—', b.density ? b.density.toLocaleString() + ' /km²' : '—', a.density, b.density, false) +
          row('GDP', '$' + compact(a.gdp * 1e6), '$' + compact(b.gdp * 1e6), a.gdp, b.gdp, true) +
          row('GDP per person', '$' + a.gdpPerCapita.toLocaleString(), '$' + b.gdpPerCapita.toLocaleString(), a.gdpPerCapita, b.gdpPerCapita, true) +
          row('Land borders', String(a.borders.length), String(b.borders.length), a.borders.length, b.borders.length, true) +
          row('Time zones', String(a.timezones.length), String(b.timezones.length), a.timezones.length, b.timezones.length, true) +
          row('Languages', a.languages.join(', ') || '—', b.languages.join(', ') || '—', null, null, null) +
          row('Currency', a.currencies.length ? a.currencies[0].name : '—', b.currencies.length ? b.currencies[0].name : '—', null, null, null) +
          row('Dial code', a.calling || '—', b.calling || '—', null, null, null) +
          row('Coastline', a.landlocked ? 'Landlocked' : 'Has a coast', b.landlocked ? 'Landlocked' : 'Has a coast', null, null, null) +
        '</div>';

        // Distance between the two, as a bonus row.
        if (window.WorldMap && window.WorldMap.haversine) {
          var km = window.WorldMap.haversine(a.lon, a.lat, b.lon, b.lat);
          html += '<p style="margin:18px 0 0;text-align:center;color:var(--text-dim)">' +
            'Roughly <strong>' + Math.round(km).toLocaleString() + ' km</strong> apart, centre to centre.</p>';
        }

        html += '<div class="grid grid--2" style="margin-top:22px">' +
          [pair[0], pair[1]].map(function (side) {
            return '<div class="card"><h3 style="font-size:1rem">' + side.country.flag + ' ' + side.country.name + '</h3>' +
              '<ol style="margin:0;padding-left:18px;color:var(--text-dim);font-size:.9rem">' +
              side.facts.slice(0, 3).map(function (fact) { return '<li style="margin-bottom:6px">' + fact + '</li>'; }).join('') +
              '</ol><a class="btn btn--sm" style="margin-top:12px" href="/country/' + side.country.iso3 + '">All 10 facts →</a></div>';
          }).join('') +
        '</div>';

        output.innerHTML = html;
      });
    }

    [left, right].forEach(function (select) { select.addEventListener('change', render); });

    if (swap) {
      swap.addEventListener('click', function () {
        var temp = left.value;
        left.value = right.value;
        right.value = temp;
        render();
      });
    }

    render();
  }

  /* ------------------------------------------------------------------- quiz */

  function quizPage() {
    var root = $('[data-quiz]');
    if (!root) { return; }

    var stage = $('[data-quiz-stage]', root);
    var bar = $('[data-quiz-bar]', root);
    var scoreNode = $('[data-quiz-score]', root);
    var streakNode = $('[data-quiz-streak]', root);
    var bestNode = $('[data-quiz-best]', root);

    var questions = [];
    var index = 0;
    var score = 0;
    var streak = 0;
    var mode = 'flag';
    var locked = false;

    function best(value) {
      try {
        var stored = Number(localStorage.getItem('worldly:quizbest') || 0);
        if (value !== undefined && value > stored) {
          localStorage.setItem('worldly:quizbest', String(value));
          return value;
        }
        return stored;
      } catch (e) {
        return value || 0;
      }
    }

    function paintScore() {
      scoreNode.textContent = score + ' / ' + questions.length;
      streakNode.textContent = '🔥 ' + streak;
      streakNode.classList.toggle('is-hot', streak >= 3);
      bestNode.textContent = 'Best: ' + best();
      bar.style.width = questions.length ? (index / questions.length * 100) + '%' : '0%';
    }

    function load() {
      stage.innerHTML = '<div class="empty-state"><span class="empty-state__icon">🎯</span><p>Building your quiz…</p></div>';

      fetch('/api/quiz?mode=' + mode)
        .then(function (response) { return response.json(); })
        .then(function (payload) {
          questions = payload.questions || [];
          index = 0;
          score = 0;
          streak = 0;
          paintScore();
          ask();
        })
        .catch(function () {
          stage.innerHTML = '<div class="empty-state"><span class="empty-state__icon">⚠️</span><p>Could not build a quiz right now.</p></div>';
        });
    }

    function ask() {
      if (index >= questions.length) { return finish(); }

      var question = questions[index];
      locked = false;

      var visual = mode === 'flag'
        ? '<div class="quiz__flag">' + question.flag + '</div>'
        : '<div class="quiz__flag" style="font-size:3.4rem">' + (mode === 'capital' ? '🏛' : '🗺') + '</div>';

      stage.innerHTML = visual +
        '<p class="quiz__prompt">' + question.prompt + '</p>' +
        '<div class="quiz__options">' +
          question.options.map(function (option) {
            return '<button class="quiz__option" type="button" data-iso3="' + option.iso3 + '">' + option.label + '</button>';
          }).join('') +
        '</div>' +
        '<p class="quiz__feedback" data-quiz-feedback></p>';

      if (mode === 'map' && window.worldlyMap) {
        window.worldlyMap.select(question.iso3, { silent: true, fly: true, zoom: 2.6 });
      }

      $$('.quiz__option', stage).forEach(function (button) {
        button.addEventListener('click', function () { answer(button, question); });
      });

      paintScore();
    }

    function answer(button, question) {
      if (locked) { return; }
      locked = true;

      var correct = button.dataset.iso3 === question.iso3;

      $$('.quiz__option', stage).forEach(function (option) {
        option.disabled = true;
        if (option.dataset.iso3 === question.iso3) { option.classList.add('is-right'); }
        else if (option === button) { option.classList.add('is-wrong'); }
      });

      if (correct) {
        score++;
        streak++;
        toast(streak >= 3 ? 'Streak of ' + streak + '! 🔥' : 'Correct');
      } else {
        streak = 0;
      }

      var feedback = $('[data-quiz-feedback]', stage);
      if (feedback) {
        feedback.textContent = (correct ? '✅ ' : '❌ ') + question.fact;
      }

      best(score);
      index++;
      paintScore();

      setTimeout(function () { ask(); }, 1700);
    }

    function finish() {
      bar.style.width = '100%';
      var pct = questions.length ? Math.round(score / questions.length * 100) : 0;
      var verdict = pct === 100 ? 'Perfect run.' : pct >= 75 ? 'Strong geography.' : pct >= 50 ? 'Not bad.' : 'The atlas awaits.';

      stage.innerHTML = '<div class="quiz__flag">' + (pct >= 75 ? '🏆' : pct >= 50 ? '🎖' : '🧭') + '</div>' +
        '<h2 style="margin:0">' + score + ' out of ' + questions.length + '</h2>' +
        '<p class="quiz__prompt" style="font-weight:500;color:var(--text-dim)">' + verdict + ' Best so far: ' + best() + '.</p>' +
        '<div class="pill-row" style="justify-content:center">' +
          '<button class="btn btn--primary" type="button" data-quiz-again>Play again</button>' +
          '<a class="btn" href="/countries">Study the countries</a>' +
        '</div>';

      var again = $('[data-quiz-again]', stage);
      if (again) { again.addEventListener('click', load); }
    }

    $$('[data-quiz-mode]', root).forEach(function (button) {
      button.addEventListener('click', function () {
        $$('[data-quiz-mode]', root).forEach(function (other) { other.classList.remove('is-on'); });
        button.classList.add('is-on');
        mode = button.dataset.quizMode;
        load();
      });
    });

    load();
  }

  /* ---------------------------------------------------------------- tabbing */

  function tabs() {
    $$('[data-tabs]').forEach(function (root) {
      var buttons = $$('[data-tab]', root);

      buttons.forEach(function (button) {
        button.addEventListener('click', function () {
          buttons.forEach(function (other) { other.classList.remove('is-on'); });
          button.classList.add('is-on');

          $$('[data-tab-panel]', root).forEach(function (panel) {
            panel.hidden = panel.dataset.tabPanel !== button.dataset.tab;
          });
        });
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    bookmarkButtons();
    bookmarksPage();
    commandPalette();
    tabs();
    comparePage();

    // These need the map instance that app.js creates on the same event.
    setTimeout(function () {
      mapStyleControls();
      quizPage();
    }, 0);
  });
}());
