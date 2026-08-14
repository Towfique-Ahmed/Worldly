/**
 * WorldMap — pan, zoom, layers, live day/night terminator and marker overlays
 * for the server-rendered Robinson SVG.
 *
 * Emits two events on its root element:
 *   worldmap:select  { detail: { iso3, name, continent, ... } }
 *   worldmap:hover   { detail: { iso3 | null } }
 */
(function (global) {
  'use strict';

  var SVG_NS = 'http://www.w3.org/2000/svg';
  var MAP_W = 1000;
  var MAP_H = MAP_W / global.Projection.aspect;

  var PLACE_COLORS = {
    Ancient: '#ffc857',
    Wonder: '#ff9f68',
    Nature: '#4fe3c1',
    City: '#5aa9ff',
    Beach: '#59d3ff',
    Island: '#7ef0d0',
    Desert: '#f0a35e',
    Wildlife: '#a3e05b',
    Adventure: '#b47cff',
    Spiritual: '#ff7a92'
  };

  function el(name, attrs) {
    var node = document.createElementNS(SVG_NS, name);
    for (var key in attrs) {
      if (Object.prototype.hasOwnProperty.call(attrs, key)) {
        node.setAttribute(key, attrs[key]);
      }
    }
    return node;
  }

  function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value));
  }

  function compact(n) {
    var abs = Math.abs(n);
    if (abs >= 1e9) { return (n / 1e9).toFixed(2).replace(/\.?0+$/, '') + 'B'; }
    if (abs >= 1e6) { return (n / 1e6).toFixed(1).replace(/\.0$/, '') + 'M'; }
    if (abs >= 1e3) { return (n / 1e3).toFixed(1).replace(/\.0$/, '') + 'K'; }
    return String(Math.round(n));
  }

  /* ------------------------------------------------------------ solar maths */

  /**
   * Subsolar point — the lon/lat where the sun is directly overhead.
   * Low-precision NOAA algorithm; good to a fraction of a degree, which is far
   * finer than this map can draw.
   */
  function subsolarPoint(date) {
    var rad = Math.PI / 180;
    var jd = date.getTime() / 86400000 + 2440587.5;
    var n = jd - 2451545.0;

    var meanLon = (280.460 + 0.9856474 * n) % 360;
    var meanAnomaly = ((357.528 + 0.9856003 * n) % 360) * rad;
    var lambda = (meanLon + 1.915 * Math.sin(meanAnomaly) + 0.020 * Math.sin(2 * meanAnomaly)) * rad;
    var obliquity = (23.439 - 0.0000004 * n) * rad;

    var declination = Math.asin(Math.sin(obliquity) * Math.sin(lambda));
    var rightAscension = Math.atan2(Math.cos(obliquity) * Math.sin(lambda), Math.cos(lambda)) / rad;

    var gmst = (18.697374558 + 24.06570982441908 * n) % 24;
    if (gmst < 0) { gmst += 24; }

    var lon = rightAscension - gmst * 15;
    lon = ((lon + 180) % 360 + 360) % 360 - 180;

    return { lon: lon, lat: declination / rad, declination: declination };
  }

  /* ------------------------------------------------------------------ class */

  function WorldMap(root) {
    this.root = root;
    this.svg = root.querySelector('.worldmap');
    this.camera = root.querySelector('.wm-camera');
    this.countries = Array.prototype.slice.call(root.querySelectorAll('.wm-country'));
    this.tooltip = root.querySelector('[data-tooltip]');
    this.markerLayer = root.querySelector('[data-markers]');
    this.pulseLayer = root.querySelector('[data-pulse]');
    this.arcLayer = root.querySelector('[data-arcs]');
    this.graticule = root.querySelector('[data-graticule]');
    this.nightShade = root.querySelector('[data-night-shade]');
    this.sun = root.querySelector('[data-sun]');
    this.nightGroup = root.querySelector('[data-night]');
    this.scaleLabel = root.querySelector('[data-scale]');

    var payloadNode = root.querySelector('[data-worldmap-payload]');
    this.data = payloadNode ? JSON.parse(payloadNode.textContent) : { countries: [], capitals: [], mountains: [], places: [] };

    this.byIso = {};
    this.data.countries.forEach(function (country) { this.byIso[country.iso3] = country; }, this);

    this.layers = {};
    (root.dataset.layers || '').split(',').forEach(function (name) {
      if (name) { this.layers[name.trim()] = true; }
    }, this);

    this.colorMode = root.dataset.colorMode || 'continent';
    this.selected = null;
    this.view = { x: 0, y: 0, k: 1 };
    this.markersDirty = true;

    this.buildGraticule();
    this.applyColorMode(this.colorMode);
    this.renderMarkers();
    this.bind();

    if (this.layers.daynight) {
      this.updateTerminator();
      this.terminatorTimer = setInterval(this.updateTerminator.bind(this), 60000);
    } else {
      this.nightGroup.style.display = 'none';
    }

    if (root.dataset.focus) {
      var focus = JSON.parse(root.dataset.focus);
      var self = this;
      setTimeout(function () { self.flyTo(focus.lon, focus.lat, focus.zoom || 2.4); }, 260);
    }

    if (root.dataset.highlight) {
      this.select(root.dataset.highlight, { silent: true, fly: true });
    }
  }

  /* ------------------------------------------------------------- graticule */

  WorldMap.prototype.buildGraticule = function () {
    if (!this.layers.graticule) { return; }

    var project = global.Projection.project;
    var lat, lon, points, d, i;

    for (lat = -60; lat <= 60; lat += 30) {
      points = [];
      for (lon = -180; lon <= 180; lon += 5) {
        points.push(project(lon, lat, MAP_W));
      }
      d = points.map(function (p, index) {
        return (index ? 'L' : 'M') + p.x.toFixed(1) + ' ' + p.y.toFixed(1);
      }).join('');
      this.graticule.appendChild(el('path', { d: d, class: lat === 0 ? 'wm-equator' : '' }));
    }

    for (i = -180; i <= 180; i += 30) {
      points = [];
      for (lat = -85; lat <= 85; lat += 5) {
        points.push(project(i, lat, MAP_W));
      }
      d = points.map(function (p, index) {
        return (index ? 'L' : 'M') + p.x.toFixed(1) + ' ' + p.y.toFixed(1);
      }).join('');
      this.graticule.appendChild(el('path', { d: d }));
    }
  };

  /* ------------------------------------------------------------ colour mode */

  WorldMap.prototype.applyColorMode = function (mode) {
    this.colorMode = mode;
    this.root.dataset.colorMode = mode;

    var mapId = this.root.id;
    var maxPop = 0, maxGdp = 0;
    this.data.countries.forEach(function (country) {
      maxPop = Math.max(maxPop, country.population);
      maxGdp = Math.max(maxGdp, country.gdpPerCapita);
    });

    this.countries.forEach(function (path) {
      var iso3 = path.dataset.iso3;
      var country = this.byIso[iso3];
      if (!country) { return; }

      if (mode === 'continent') {
        var slug = country.continent.toLowerCase().replace(/[^a-z0-9]+/g, '-');
        path.style.fill = 'url(#' + mapId + '-c-' + slug + ')';
      } else if (mode === 'population') {
        var t = Math.pow(country.population / maxPop, 0.32);
        path.style.fill = 'hsl(' + (208 - t * 168) + ' 82% ' + (26 + t * 34) + '%)';
      } else if (mode === 'gdp') {
        var g = Math.pow(Math.min(country.gdpPerCapita, 90000) / 90000, 0.5);
        path.style.fill = 'hsl(' + (272 - g * 122) + ' 74% ' + (28 + g * 32) + '%)';
      } else {
        path.style.fill = '';
      }
    }, this);
  };

  /* ---------------------------------------------------------------- markers */

  WorldMap.prototype.renderMarkers = function () {
    var project = global.Projection.project;
    this.markerLayer.textContent = '';

    var self = this;
    var scale = 1 / this.view.k;

    function add(item, kind, build) {
      var p = project(item.lon, item.lat, MAP_W);
      var group = el('g', {
        class: 'wm-marker wm-marker--' + kind,
        transform: 'translate(' + p.x.toFixed(2) + ' ' + p.y.toFixed(2) + ') scale(' + scale.toFixed(3) + ')'
      });
      group.appendChild(build());
      group.__meta = { title: item.name, meta: item.__meta, lon: item.lon, lat: item.lat, iso3: item.iso3 };
      self.markerLayer.appendChild(group);
    }

    if (this.layers.capitals) {
      this.data.capitals.forEach(function (city) {
        city.__meta = city.country + (city.population ? ' · ' + compact(city.population) + ' people' : '');
        add(city, 'capital', function () {
          return el('circle', { r: city.population > 5e6 ? 2.6 : 1.7, 'fill-opacity': 0.85 });
        });
      });
    }

    if (this.layers.mountains) {
      this.data.mountains.forEach(function (peak) {
        peak.__meta = peak.range + ' · ' + peak.elevation.toLocaleString() + ' m';
        add(peak, 'mountain', function () {
          return el('path', { d: 'M0 -4.4 L3.6 2.6 L-3.6 2.6 Z', 'fill-opacity': 0.9 });
        });
      });
    }

    if (this.layers.places) {
      this.data.places.forEach(function (place) {
        place.__meta = place.country + ' · ' + place.category;
        add(place, 'place', function () {
          return el('circle', {
            r: 2.6,
            fill: PLACE_COLORS[place.category] || '#ff7a92',
            'fill-opacity': 0.85,
            stroke: 'rgba(255,255,255,.65)',
            'stroke-width': 0.6
          });
        });
      });
    }

    this.markersDirty = false;
  };

  WorldMap.prototype.setLayer = function (name, on) {
    this.layers[name] = on;

    if (name === 'graticule') {
      this.graticule.style.display = on ? '' : 'none';
      if (on && !this.graticule.childNodes.length) { this.buildGraticule(); }
      return;
    }

    if (name === 'daynight') {
      this.nightGroup.style.display = on ? '' : 'none';
      if (on) {
        this.updateTerminator();
        if (!this.terminatorTimer) {
          this.terminatorTimer = setInterval(this.updateTerminator.bind(this), 60000);
        }
      }
      return;
    }

    this.renderMarkers();
  };

  /* ------------------------------------------------------------ terminator */

  WorldMap.prototype.updateTerminator = function () {
    var project = global.Projection.project;
    var sun = subsolarPoint(new Date());
    var rad = Math.PI / 180;
    var points = [];
    var lon, lat;

    // Latitude of the terminator at each longitude.
    var tanDecl = Math.tan(sun.declination);
    if (Math.abs(tanDecl) < 1e-6) { tanDecl = tanDecl < 0 ? -1e-6 : 1e-6; }

    for (lon = -180; lon <= 180; lon += 2) {
      var hourAngle = (lon - sun.lon) * rad;
      lat = Math.atan(-Math.cos(hourAngle) / tanDecl) / rad;
      points.push(project(lon, clamp(lat, -89.9, 89.9), MAP_W));
    }

    // Close the polygon along whichever pole is currently in darkness.
    var polarLat = sun.declination > 0 ? -90 : 90;
    for (lon = 180; lon >= -180; lon -= 5) {
      points.push(project(lon, polarLat, MAP_W));
    }

    var d = points.map(function (p, index) {
      return (index ? 'L' : 'M') + p.x.toFixed(1) + ' ' + p.y.toFixed(1);
    }).join('') + 'Z';

    this.nightShade.setAttribute('d', d);

    var sunPoint = project(sun.lon, sun.lat, MAP_W);
    this.sun.setAttribute('cx', sunPoint.x.toFixed(1));
    this.sun.setAttribute('cy', sunPoint.y.toFixed(1));
    this.sun.setAttribute('r', (6 / this.view.k).toFixed(2));

    this.root.dispatchEvent(new CustomEvent('worldmap:sun', { detail: sun }));
  };

  /* --------------------------------------------------------------- camera */

  WorldMap.prototype.applyView = function (animate) {
    var v = this.view;
    var maxX = MAP_W * (v.k - 1);
    var maxY = MAP_H * (v.k - 1);

    v.x = clamp(v.x, -maxX, 0);
    v.y = clamp(v.y, -maxY, 0);

    this.root.classList.toggle('is-zooming', !animate);
    this.camera.setAttribute('transform', 'translate(' + v.x.toFixed(2) + ' ' + v.y.toFixed(2) + ') scale(' + v.k.toFixed(4) + ')');

    if (this.scaleLabel) { this.scaleLabel.textContent = v.k.toFixed(1) + '×'; }

    var scale = (1 / v.k).toFixed(3);
    var markers = this.markerLayer.childNodes;
    for (var i = 0; i < markers.length; i++) {
      var t = markers[i].getAttribute('transform');
      markers[i].setAttribute('transform', t.replace(/scale\([^)]*\)/, 'scale(' + scale + ')'));
    }
    if (this.sun) { this.sun.setAttribute('r', (6 / v.k).toFixed(2)); }
  };

  WorldMap.prototype.zoomBy = function (factor, cx, cy) {
    var v = this.view;
    var k = clamp(v.k * factor, 1, 14);
    if (k === v.k) { return; }

    if (cx === undefined) { cx = MAP_W / 2; cy = MAP_H / 2; }

    // Keep the point under the cursor fixed while scaling.
    v.x = cx - (cx - v.x) * (k / v.k);
    v.y = cy - (cy - v.y) * (k / v.k);
    v.k = k;

    this.applyView(false);
  };

  WorldMap.prototype.flyTo = function (lon, lat, zoom) {
    var point = global.Projection.project(lon, lat, MAP_W);
    var k = clamp(zoom || 3, 1, 14);

    this.view.k = k;
    this.view.x = MAP_W / 2 - point.x * k;
    this.view.y = MAP_H / 2 - point.y * k;
    this.applyView(true);
  };

  WorldMap.prototype.reset = function () {
    this.view = { x: 0, y: 0, k: 1 };
    this.applyView(true);
  };

  /** Ripple marker at a lon/lat, used to punctuate a fly-to. */
  WorldMap.prototype.ping = function (lon, lat) {
    if (!this.pulseLayer) { return; }
    var p = global.Projection.project(lon, lat, MAP_W);
    this.pulseLayer.textContent = '';
    var circle = el('circle', { cx: p.x.toFixed(1), cy: p.y.toFixed(1), r: 2 });
    this.pulseLayer.appendChild(circle);
  };

  /* ------------------------------------------------------------- selection */

  WorldMap.prototype.select = function (iso3, options) {
    options = options || {};
    var country = this.byIso[iso3];

    this.countries.forEach(function (path) {
      path.classList.toggle('is-selected', path.dataset.iso3 === iso3);
    });

    this.selected = country ? iso3 : null;

    if (country && options.fly !== false) {
      this.flyTo(country.lon, country.lat, options.zoom || 3.4);
      this.ping(country.lon, country.lat);
    }

    if (country && !options.silent) {
      this.root.dispatchEvent(new CustomEvent('worldmap:select', { detail: country }));
    }
  };

  WorldMap.prototype.highlightContinent = function (name) {
    this.countries.forEach(function (path) {
      path.classList.toggle('is-dimmed', Boolean(name) && path.dataset.continent !== name);
    });
  };

  /* ---------------------------------------------------------------- events */

  WorldMap.prototype.showTooltip = function (event, title, meta, flag) {
    if (!this.tooltip) { return; }
    var rect = this.root.getBoundingClientRect();

    this.tooltip.hidden = false;
    this.tooltip.querySelector('[data-tooltip-title]').textContent = title;
    this.tooltip.querySelector('[data-tooltip-meta]').textContent = meta || '';
    this.tooltip.querySelector('[data-tooltip-flag]').textContent = flag || '';
    this.tooltip.style.left = (event.clientX - rect.left) + 'px';
    this.tooltip.style.top = (event.clientY - rect.top) + 'px';
  };

  WorldMap.prototype.hideTooltip = function () {
    if (this.tooltip) { this.tooltip.hidden = true; }
  };

  WorldMap.prototype.bind = function () {
    var self = this;
    var drag = null;

    this.svg.addEventListener('pointerdown', function (event) {
      if (event.button !== 0) { return; }
      // The element under the cursor is remembered here: once the pointer is
      // captured below, the browser retargets the follow-up click to the SVG.
      drag = {
        x: event.clientX, y: event.clientY,
        ox: self.view.x, oy: self.view.y,
        moved: false,
        target: event.target
      };
      self.svg.setPointerCapture(event.pointerId);
      self.root.classList.add('is-panning');
    });

    this.svg.addEventListener('pointermove', function (event) {
      if (!drag) { return; }
      var rect = self.svg.getBoundingClientRect();
      var ratio = MAP_W / rect.width;
      var dx = (event.clientX - drag.x) * ratio;
      var dy = (event.clientY - drag.y) * ratio;

      if (Math.abs(dx) + Math.abs(dy) > 3) { drag.moved = true; }

      self.view.x = drag.ox + dx;
      self.view.y = drag.oy + dy;
      self.applyView(false);
    });

    function endDrag(event) {
      if (!drag) { return; }
      if (self.svg.hasPointerCapture(event.pointerId)) {
        self.svg.releasePointerCapture(event.pointerId);
      }
      self.root.classList.remove('is-panning');

      var target = drag.target;
      var wasDrag = drag.moved;
      drag = null;

      if (wasDrag || event.type !== 'pointerup' || !target || !target.closest) { return; }

      var marker = target.closest('.wm-marker');
      if (marker && marker.__meta && marker.__meta.iso3) {
        self.select(marker.__meta.iso3);
        return;
      }

      var country = target.closest('.wm-country');
      if (country) { self.select(country.dataset.iso3); }
    }

    this.svg.addEventListener('pointerup', endDrag);
    this.svg.addEventListener('pointercancel', endDrag);

    this.svg.addEventListener('wheel', function (event) {
      event.preventDefault();
      var rect = self.svg.getBoundingClientRect();
      var ratio = MAP_W / rect.width;
      var cx = (event.clientX - rect.left) * ratio;
      var cy = (event.clientY - rect.top) * ratio;
      self.zoomBy(event.deltaY < 0 ? 1.18 : 1 / 1.18, cx, cy);
    }, { passive: false });

    this.svg.addEventListener('mousemove', function (event) {
      var target = event.target.closest ? event.target.closest('.wm-country, .wm-marker') : null;
      if (!target) { self.hideTooltip(); return; }

      if (target.classList.contains('wm-marker')) {
        var meta = target.__meta || {};
        self.showTooltip(event, meta.title || '', meta.meta || '', '📍');
      } else {
        var population = Number(target.dataset.population || 0);
        self.showTooltip(
          event,
          target.dataset.name,
          target.dataset.continent + (population ? ' · ' + compact(population) + ' people' : ''),
          target.dataset.flag
        );
        self.root.dispatchEvent(new CustomEvent('worldmap:hover', { detail: { iso3: target.dataset.iso3 } }));
      }
    });

    this.svg.addEventListener('mouseleave', function () {
      self.hideTooltip();
      self.root.dispatchEvent(new CustomEvent('worldmap:hover', { detail: { iso3: null } }));
    });

    this.svg.addEventListener('keydown', function (event) {
      if (event.key !== 'Enter' && event.key !== ' ') { return; }
      var country = event.target.closest ? event.target.closest('.wm-country') : null;
      if (country) {
        event.preventDefault();
        self.select(country.dataset.iso3);
      }
    });

    this.root.querySelectorAll('[data-map-action]').forEach(function (button) {
      button.addEventListener('click', function () {
        var action = button.dataset.mapAction;
        if (action === 'zoom-in') { self.zoomBy(1.5); }
        else if (action === 'zoom-out') { self.zoomBy(1 / 1.5); }
        else if (action === 'reset') { self.reset(); self.select(null, { silent: true, fly: false }); self.highlightContinent(null); }
        else if (action === 'random') {
          var list = self.data.countries.filter(function (c) { return c.population > 500000; });
          var pick = list[Math.floor(Math.random() * list.length)];
          self.select(pick.iso3);
        }
      });
    });
  };

  global.WorldMap = WorldMap;
  global.WorldMap.PLACE_COLORS = PLACE_COLORS;
}(window));
