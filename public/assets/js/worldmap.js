/**
 * WorldMap - pan, zoom, map styles, layer control, a live day/night terminator,
 * marker overlays and a canvas globe, over the server-rendered Robinson SVG.
 *
 * Emits on its root element:
 *   worldmap:select  { detail: country }
 *   worldmap:hover   { detail: { iso3 | null } }
 *   worldmap:sun     { detail: subsolar point }
 */
(function (global) {
  'use strict';

  var SVG_NS = 'http://www.w3.org/2000/svg';
  var MAP_W = 1000;
  var MAP_H = MAP_W / global.Projection.aspect;

  var PLACE_COLORS = {
    Ancient: '#ffc857', Wonder: '#ff9f68', Nature: '#4fe3c1', City: '#5aa9ff',
    Beach: '#59d3ff', Island: '#7ef0d0', Desert: '#f0a35e', Wildlife: '#a3e05b',
    Adventure: '#b47cff', Spiritual: '#ff7a92'
  };

  function el(name, attrs) {
    var node = document.createElementNS(SVG_NS, name);
    for (var key in attrs) {
      if (Object.prototype.hasOwnProperty.call(attrs, key)) { node.setAttribute(key, attrs[key]); }
    }
    return node;
  }

  function clamp(value, min, max) { return Math.min(max, Math.max(min, value)); }

  function compact(n) {
    var abs = Math.abs(n);
    if (abs >= 1e9) { return (n / 1e9).toFixed(2).replace(/\.?0+$/, '') + 'B'; }
    if (abs >= 1e6) { return (n / 1e6).toFixed(1).replace(/\.0$/, '') + 'M'; }
    if (abs >= 1e3) { return (n / 1e3).toFixed(1).replace(/\.0$/, '') + 'K'; }
    return String(Math.round(n));
  }

  function formatCoords(lon, lat) {
    return Math.abs(lat).toFixed(1) + '° ' + (lat >= 0 ? 'N' : 'S') + ', ' +
           Math.abs(lon).toFixed(1) + '° ' + (lon >= 0 ? 'E' : 'W');
  }

  /* ------------------------------------------------------------ solar maths */

  /** Subsolar point - where the sun is directly overhead right now. */
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

  /** Great-circle distance in kilometres. */
  function haversine(lon1, lat1, lon2, lat2) {
    var rad = Math.PI / 180;
    var dLat = (lat2 - lat1) * rad;
    var dLon = (lon2 - lon1) * rad;
    var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(lat1 * rad) * Math.cos(lat2 * rad) * Math.sin(dLon / 2) * Math.sin(dLon / 2);
    return 6371 * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  }

  /** Points along the great circle between two coordinates. */
  function greatCircle(lon1, lat1, lon2, lat2, steps) {
    var rad = Math.PI / 180;
    var φ1 = lat1 * rad, λ1 = lon1 * rad, φ2 = lat2 * rad, λ2 = lon2 * rad;
    var d = 2 * Math.asin(Math.sqrt(
      Math.pow(Math.sin((φ2 - φ1) / 2), 2) +
      Math.cos(φ1) * Math.cos(φ2) * Math.pow(Math.sin((λ2 - λ1) / 2), 2)
    ));

    var points = [];
    if (d === 0) { return [[lon1, lat1]]; }

    for (var i = 0; i <= steps; i++) {
      var f = i / steps;
      var A = Math.sin((1 - f) * d) / Math.sin(d);
      var B = Math.sin(f * d) / Math.sin(d);
      var x = A * Math.cos(φ1) * Math.cos(λ1) + B * Math.cos(φ2) * Math.cos(λ2);
      var y = A * Math.cos(φ1) * Math.sin(λ1) + B * Math.cos(φ2) * Math.sin(λ2);
      var z = A * Math.sin(φ1) + B * Math.sin(φ2);
      points.push([Math.atan2(y, x) / rad, Math.atan2(z, Math.sqrt(x * x + y * y)) / rad]);
    }

    return points;
  }

  /* ------------------------------------------------------------------ class */

  function WorldMap(root) {
    this.root = root;
    this.svg = root.querySelector('.worldmap');
    this.camera = root.querySelector('.wm-camera');
    this.countries = Array.prototype.slice.call(root.querySelectorAll('.wm-country'));
    this.tooltip = root.querySelector('[data-tooltip]');
    this.markerLayer = root.querySelector('[data-markers]');
    this.labelLayer = root.querySelector('[data-labels]');
    this.pulseLayer = root.querySelector('[data-pulse]');
    this.arcLayer = root.querySelector('[data-arcs]');
    this.graticule = root.querySelector('[data-graticule]');
    this.nightShade = root.querySelector('[data-night-shade]');
    this.sun = root.querySelector('[data-sun]');
    this.nightGroup = root.querySelector('[data-night]');
    this.scaleLabel = root.querySelector('[data-scale]');
    this.coordLabel = root.querySelector('[data-coords]');
    this.globeCanvas = root.querySelector('[data-globe]');

    var payloadNode = root.querySelector('[data-worldmap-payload]');
    this.data = payloadNode
      ? JSON.parse(payloadNode.textContent)
      : { countries: [], capitals: [], cities: [], mountains: [], places: [], water: [], continents: {} };

    this.byIso = {};
    this.data.countries.forEach(function (country) { this.byIso[country.iso3] = country; }, this);

    // Country paths render straight from geometry, so attach the label data.
    this.countries.forEach(function (path) {
      var country = this.byIso[path.dataset.iso3];
      if (country) {
        path.dataset.name = country.name;
        path.dataset.continent = country.continent;
        path.dataset.population = country.population;
        path.dataset.flag = country.flag;
        path.setAttribute('aria-label', country.name);
      } else {
        path.classList.add('wm-country--unlabelled');
        path.setAttribute('tabindex', '-1');
      }
    }, this);

    this.layers = {};
    (root.dataset.layers || '').split(',').forEach(function (name) {
      if (name.trim()) { this.layers[name.trim()] = true; }
    }, this);

    this.style = root.dataset.style || 'physical';
    this.selected = null;
    this.view = { x: 0, y: 0, k: 1 };
    this.measure = { active: false, from: null };
    this.globeOn = false;

    this.buildGraticule();
    this.applyStyle(this.style);
    this.syncLayerVisibility();
    this.loadDetail();
    this.renderLabels();
    this.renderMarkers();
    this.bind();

    if (this.layers.daynight) {
      this.updateTerminator();
      this.terminatorTimer = setInterval(this.updateTerminator.bind(this), 60000);
    } else if (this.nightGroup) {
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
    if (!this.graticule || this.graticule.childNodes.length) { return; }

    var project = global.Projection.project;
    var lat, lon, points, i;

    function draw(target, points, className) {
      var d = points.map(function (p, index) {
        return (index ? 'L' : 'M') + p.x.toFixed(1) + ' ' + p.y.toFixed(1);
      }).join('');
      target.appendChild(el('path', { d: d, class: className || '' }));
    }

    for (lat = -60; lat <= 60; lat += 15) {
      points = [];
      for (lon = -180; lon <= 180; lon += 5) { points.push(project(lon, lat, MAP_W)); }
      draw(this.graticule, points, lat === 0 ? 'wm-equator' : (Math.abs(lat) === 30 || Math.abs(lat) === 60 ? '' : 'wm-minor'));
    }

    // Tropics and polar circles, which are the lines people actually look for.
    [23.44, -23.44, 66.56, -66.56].forEach(function (special) {
      points = [];
      for (lon = -180; lon <= 180; lon += 5) { points.push(project(lon, special, MAP_W)); }
      draw(this.graticule, points, 'wm-tropic');
    }, this);

    for (i = -180; i <= 180; i += 15) {
      points = [];
      for (lat = -85; lat <= 85; lat += 5) { points.push(project(i, lat, MAP_W)); }
      draw(this.graticule, points, i % 30 === 0 ? '' : 'wm-minor');
    }
  };

  /* ---------------------------------------------------------------- styling */

  WorldMap.prototype.applyStyle = function (style) {
    this.style = style;
    this.root.dataset.style = style;

    var mapId = this.root.id;
    var maxPop = 1, maxGdp = 1, maxDensity = 1;

    this.data.countries.forEach(function (country) {
      maxPop = Math.max(maxPop, country.population);
      maxGdp = Math.max(maxGdp, country.gdpPerCapita);
      maxDensity = Math.max(maxDensity, country.density || 0);
    });

    this.countries.forEach(function (path) {
      var country = this.byIso[path.dataset.iso3];

      if (!country || style === 'physical' || style === 'night') {
        path.style.fill = '';
        return;
      }

      if (style === 'political') {
        var slug = country.continent.toLowerCase().replace(/[^a-z0-9]+/g, '-');
        path.style.fill = 'url(#' + mapId + '-c-' + slug + ')';
      } else if (style === 'population') {
        var t = Math.pow(country.population / maxPop, 0.32);
        path.style.fill = 'hsl(' + (208 - t * 168) + ' 82% ' + (26 + t * 34) + '%)';
      } else if (style === 'density') {
        var d = Math.pow(Math.min(country.density || 0, 600) / 600, 0.4);
        path.style.fill = 'hsl(' + (190 - d * 190) + ' 76% ' + (24 + d * 36) + '%)';
      } else if (style === 'gdp') {
        var g = Math.pow(Math.min(country.gdpPerCapita, 90000) / 90000, 0.5);
        path.style.fill = 'hsl(' + (272 - g * 122) + ' 74% ' + (28 + g * 32) + '%)';
      } else {
        path.style.fill = '';
      }
    }, this);

    // The night style lights cities instead of colouring countries.
    this.setLayer('cities', style === 'night' ? true : this.layers.cities === true, true);
    if (this.globeOn) { this.drawGlobe(); }
  };

  /* ----------------------------------------------------------------- layers */

  WorldMap.prototype.syncLayerVisibility = function () {
    var pairs = {
      graticule: '[data-graticule]',
      rivers: '[data-rivers]',
      lakes: '[data-lakes]',
      terrain: '[data-terrain]',
      labels: '[data-labels]',
      daynight: '[data-night]'
    };

    Object.keys(pairs).forEach(function (name) {
      var node = this.root.querySelector(pairs[name]);
      if (node) { node.style.display = this.layers[name] ? '' : 'none'; }
    }, this);
  };

  WorldMap.prototype.setLayer = function (name, on, quiet) {
    this.layers[name] = !!on;

    if (name === 'daynight') {
      if (this.nightGroup) { this.nightGroup.style.display = on ? '' : 'none'; }
      if (on) {
        this.updateTerminator();
        if (!this.terminatorTimer) {
          this.terminatorTimer = setInterval(this.updateTerminator.bind(this), 60000);
        }
      }
      return;
    }

    if (name === 'graticule' || name === 'rivers' || name === 'lakes' || name === 'terrain') {
      this.syncLayerVisibility();
      this.loadDetail();
      if (this.globeOn) { this.drawGlobe(); }
      return;
    }

    if (name === 'labels') {
      this.syncLayerVisibility();
      return;
    }

    this.renderMarkers();
    if (!quiet && this.globeOn) { this.drawGlobe(); }
  };

  /* ------------------------------------------------------- deferred detail */

  var TERRAIN_ORDER = ['plain', 'basin', 'plateau', 'tundra', 'desert', 'range'];
  var detailPromise = null;

  /**
   * Rivers, lakes and terrain arrive from /api/mapdetail after first paint.
   * The fetch is shared across every map on the page and happens at most once.
   */
  function fetchDetail() {
    if (!detailPromise) {
      detailPromise = fetch('/api/mapdetail')
        .then(function (response) { return response.json(); })
        .catch(function () { return { terrain: [], lakes: [], rivers: [] }; });
    }
    return detailPromise;
  }

  WorldMap.prototype.loadDetail = function () {
    if (this.root.dataset.detail !== '1' || this.detailLoaded) { return; }

    // Nothing to draw until one of these layers is actually switched on.
    if (!this.layers.rivers && !this.layers.lakes && !this.layers.terrain) { return; }

    this.detailLoaded = true;
    var self = this;

    fetchDetail().then(function (detail) {
      var terrainLayer = self.root.querySelector('[data-terrain]');
      var lakeLayer = self.root.querySelector('[data-lakes]');
      var riverLayer = self.root.querySelector('[data-rivers]');

      function paint(layer, items, build) {
        if (!layer || layer.childNodes.length) { return; }
        var fragment = document.createDocumentFragment();
        items.forEach(function (item) {
          var node = build(item);
          var title = document.createElementNS(SVG_NS, 'title');
          title.textContent = item.n;
          node.appendChild(title);
          fragment.appendChild(node);
        });
        layer.appendChild(fragment);
      }

      var terrain = (detail.terrain || []).slice().sort(function (a, b) {
        return TERRAIN_ORDER.indexOf(a.k) - TERRAIN_ORDER.indexOf(b.k);
      });

      paint(terrainLayer, terrain, function (item) {
        return el('path', { class: 'wm-terrain__area wm-terrain--' + item.k, d: item.d });
      });

      paint(lakeLayer, detail.lakes || [], function (item) {
        return el('path', { class: 'wm-lake', d: item.d });
      });

      paint(riverLayer, detail.rivers || [], function (item) {
        return el('path', { class: 'wm-river wm-river--r' + item.r, d: item.d });
      });

      self.root.dispatchEvent(new CustomEvent('worldmap:detail'));
    });
  };

  /* ---------------------------------------------------------- water labels */

  WorldMap.prototype.renderLabels = function () {
    if (!this.labelLayer) { return; }

    var project = global.Projection.project;
    this.labelLayer.textContent = '';

    (this.data.water || []).forEach(function (water) {
      var p = project(water.lon, water.lat, MAP_W);
      var text = el('text', {
        x: p.x.toFixed(1),
        y: p.y.toFixed(1),
        class: 'wm-water-label wm-water-label--' + (water.rank === 0 ? 'ocean' : 'sea'),
        'text-anchor': 'middle'
      });
      text.textContent = water.name;
      this.labelLayer.appendChild(text);
    }, this);
  };

  /* ---------------------------------------------------------------- markers */

  WorldMap.prototype.renderMarkers = function () {
    if (!this.markerLayer) { return; }

    var project = global.Projection.project;
    var self = this;
    var scale = 1 / this.view.k;

    this.markerLayer.textContent = '';

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

    if (this.layers.cities) {
      (this.data.cities || []).forEach(function (city) {
        city.__meta = city.country + ' · ' + compact(city.population) + ' people';
        add(city, 'city', function () {
          return el('circle', { r: city.population > 8e6 ? 2.2 : 1.5, 'fill-opacity': 0.8 });
        });
      });
    }

    if (this.layers.capitals) {
      this.data.capitals.forEach(function (city) {
        city.__meta = city.country + (city.population ? ' · ' + compact(city.population) + ' people' : '');
        add(city, 'capital', function () {
          var big = city.population > 5e6;
          var group = el('g', {});
          group.appendChild(el('circle', { r: big ? 2.8 : 1.9, 'fill-opacity': 0.9 }));
          if (big) { group.appendChild(el('circle', { r: 4.6, fill: 'none', 'stroke-width': 0.7, class: 'wm-marker__ring' })); }
          return group;
        });
      });
    }

    if (this.layers.mountains) {
      this.data.mountains.forEach(function (peak) {
        peak.__meta = peak.range + ' · ' + peak.elevation.toLocaleString() + ' m';
        add(peak, 'mountain', function () {
          return el('path', { d: 'M0 -4.6 L3.8 2.8 L-3.8 2.8 Z', 'fill-opacity': 0.92 });
        });
      });
    }

    if (this.layers.places) {
      this.data.places.forEach(function (place) {
        place.__meta = place.country + ' · ' + place.category;
        add(place, 'place', function () {
          return el('circle', {
            r: 2.7,
            fill: PLACE_COLORS[place.category] || '#ff7a92',
            'fill-opacity': 0.9,
            stroke: 'rgba(255,255,255,.7)',
            'stroke-width': 0.6
          });
        });
      });
    }
  };

  /* ------------------------------------------------------------ terminator */

  WorldMap.prototype.updateTerminator = function () {
    if (!this.nightShade) { return; }

    var project = global.Projection.project;
    var sun = subsolarPoint(new Date());
    var rad = Math.PI / 180;
    var points = [];
    var lon, lat;

    var tanDecl = Math.tan(sun.declination);
    if (Math.abs(tanDecl) < 1e-6) { tanDecl = tanDecl < 0 ? -1e-6 : 1e-6; }

    for (lon = -180; lon <= 180; lon += 2) {
      lat = Math.atan(-Math.cos((lon - sun.lon) * rad) / tanDecl) / rad;
      points.push(project(lon, clamp(lat, -89.9, 89.9), MAP_W));
    }

    var polarLat = sun.declination > 0 ? -90 : 90;
    for (lon = 180; lon >= -180; lon -= 5) { points.push(project(lon, polarLat, MAP_W)); }

    this.nightShade.setAttribute('d', points.map(function (p, index) {
      return (index ? 'L' : 'M') + p.x.toFixed(1) + ' ' + p.y.toFixed(1);
    }).join('') + 'Z');

    var sunPoint = project(sun.lon, sun.lat, MAP_W);
    this.sun.setAttribute('cx', sunPoint.x.toFixed(1));
    this.sun.setAttribute('cy', sunPoint.y.toFixed(1));
    this.sun.setAttribute('r', (6 / this.view.k).toFixed(2));

    this.sunPoint = sun;
    this.root.dispatchEvent(new CustomEvent('worldmap:sun', { detail: sun }));
    if (this.globeOn) { this.drawGlobe(); }
  };

  /* --------------------------------------------------------------- camera */

  WorldMap.prototype.applyView = function (animate) {
    var v = this.view;
    var maxX = MAP_W * (v.k - 1);
    var maxY = MAP_H * (v.k - 1);

    v.x = clamp(v.x, -maxX, 0);
    v.y = clamp(v.y, -maxY, 0);

    this.root.classList.toggle('is-zooming', !animate);
    this.camera.setAttribute('transform',
      'translate(' + v.x.toFixed(2) + ' ' + v.y.toFixed(2) + ') scale(' + v.k.toFixed(4) + ')');

    if (this.scaleLabel) { this.scaleLabel.textContent = v.k.toFixed(1) + '×'; }
    this.root.classList.toggle('is-zoomed', v.k > 2.2);

    var scale = (1 / v.k).toFixed(3);
    var markers = this.markerLayer ? this.markerLayer.childNodes : [];
    for (var i = 0; i < markers.length; i++) {
      var t = markers[i].getAttribute('transform');
      markers[i].setAttribute('transform', t.replace(/scale\([^)]*\)/, 'scale(' + scale + ')'));
    }
    if (this.sun) { this.sun.setAttribute('r', (6 / v.k).toFixed(2)); }
  };

  WorldMap.prototype.zoomBy = function (factor, cx, cy) {
    var v = this.view;
    var k = clamp(v.k * factor, 1, 16);
    if (k === v.k) { return; }
    if (cx === undefined) { cx = MAP_W / 2; cy = MAP_H / 2; }

    v.x = cx - (cx - v.x) * (k / v.k);
    v.y = cy - (cy - v.y) * (k / v.k);
    v.k = k;

    this.applyView(false);
  };

  WorldMap.prototype.flyTo = function (lon, lat, zoom) {
    if (this.globeOn) {
      this.globe.targetLon = -lon;
      this.globe.targetLat = -lat;
      return;
    }

    var point = global.Projection.project(lon, lat, MAP_W);
    var k = clamp(zoom || 3, 1, 16);

    this.view.k = k;
    this.view.x = MAP_W / 2 - point.x * k;
    this.view.y = MAP_H / 2 - point.y * k;
    this.applyView(true);
  };

  WorldMap.prototype.reset = function () {
    this.view = { x: 0, y: 0, k: 1 };
    this.applyView(true);
  };

  WorldMap.prototype.ping = function (lon, lat) {
    if (!this.pulseLayer) { return; }
    var p = global.Projection.project(lon, lat, MAP_W);
    this.pulseLayer.textContent = '';
    this.pulseLayer.appendChild(el('circle', { cx: p.x.toFixed(1), cy: p.y.toFixed(1), r: 2 }));
  };

  /** Draw a great-circle arc and return its distance in kilometres. */
  WorldMap.prototype.drawArc = function (from, to) {
    if (!this.arcLayer) { return 0; }

    var project = global.Projection.project;
    var points = greatCircle(from.lon, from.lat, to.lon, to.lat, 96);
    var d = '';
    var previousLon = null;

    points.forEach(function (point) {
      if (previousLon !== null && Math.abs(point[0] - previousLon) > 180) { d += ''; }
      var p = project(point[0], point[1], MAP_W);
      d += (d === '' || (previousLon !== null && Math.abs(point[0] - previousLon) > 180) ? 'M' : 'L') +
           p.x.toFixed(1) + ' ' + p.y.toFixed(1);
      previousLon = point[0];
    });

    this.arcLayer.textContent = '';
    this.arcLayer.appendChild(el('path', { d: d }));

    [from, to].forEach(function (end) {
      var p = project(end.lon, end.lat, MAP_W);
      this.arcLayer.appendChild(el('circle', { cx: p.x.toFixed(1), cy: p.y.toFixed(1), r: 2.6, class: 'wm-arc-end' }));
    }, this);

    return haversine(from.lon, from.lat, to.lon, to.lat);
  };

  WorldMap.prototype.clearArc = function () {
    if (this.arcLayer) { this.arcLayer.textContent = ''; }
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

    if (this.globeOn) { this.drawGlobe(); }
  };

  WorldMap.prototype.highlightContinent = function (name) {
    this.countries.forEach(function (path) {
      path.classList.toggle('is-dimmed', Boolean(name) && path.dataset.continent !== name);
    });
  };

  /* ------------------------------------------------------------------ globe */

  WorldMap.prototype.toggleGlobe = function (on) {
    if (!this.globeCanvas) { return; }

    this.globeOn = on === undefined ? !this.globeOn : on;
    this.globeCanvas.hidden = !this.globeOn;
    this.svg.style.visibility = this.globeOn ? 'hidden' : '';
    this.root.classList.toggle('is-globe', this.globeOn);

    var button = this.root.querySelector('[data-map-action="globe"]');
    if (button) { button.setAttribute('aria-pressed', String(this.globeOn)); }

    if (!this.globeOn) {
      cancelAnimationFrame(this.globeFrame);
      return;
    }

    if (!this.globe) {
      this.globe = { lon: 20, lat: -12, targetLon: 20, targetLat: -12, spin: true, rings: null };
    }

    var self = this;
    if (!this.globe.rings) {
      fetch('/api/globe')
        .then(function (response) { return response.json(); })
        .then(function (payload) { self.globe.rings = payload.rings || {}; self.drawGlobe(); })
        .catch(function () { self.globe.rings = {}; });
    }

    this.resizeGlobe();
    this.animateGlobe();
  };

  WorldMap.prototype.resizeGlobe = function () {
    if (!this.globeCanvas) { return; }
    var rect = this.root.getBoundingClientRect();
    var dpr = Math.min(window.devicePixelRatio || 1, 2);

    this.globeCanvas.width = Math.max(1, Math.round(rect.width * dpr));
    this.globeCanvas.height = Math.max(1, Math.round(rect.height * dpr));
    this.globeCanvas.style.width = rect.width + 'px';
    this.globeCanvas.style.height = rect.height + 'px';
    this.globeDpr = dpr;
  };

  WorldMap.prototype.animateGlobe = function () {
    var self = this;

    function frame() {
      var g = self.globe;
      if (g.spin && !g.dragging) { g.targetLon -= 0.09; }

      g.lon += (g.targetLon - g.lon) * 0.12;
      g.lat += (g.targetLat - g.lat) * 0.12;

      self.drawGlobe();
      self.globeFrame = requestAnimationFrame(frame);
    }

    cancelAnimationFrame(this.globeFrame);
    this.globeFrame = requestAnimationFrame(frame);
  };

  /** Orthographic projection: the view you would get from deep space. */
  WorldMap.prototype.drawGlobe = function () {
    if (!this.globeOn || !this.globeCanvas) { return; }

    var ctx = this.globeCanvas.getContext('2d');
    var w = this.globeCanvas.width;
    var h = this.globeCanvas.height;
    var cx = w / 2;
    var cy = h / 2;
    var radius = Math.min(w, h) * 0.42;
    var rad = Math.PI / 180;
    var g = this.globe;

    var sinLat = Math.sin(g.lat * rad), cosLat = Math.cos(g.lat * rad);

    function project(lon, lat) {
      var λ = (lon + g.lon) * rad;
      var φ = lat * rad;
      var cosφ = Math.cos(φ);
      var x = cosφ * Math.sin(λ);
      var y = Math.sin(φ);
      var z = cosφ * Math.cos(λ);

      // Rotate about the horizontal axis for the tilt.
      var y2 = y * cosLat - z * sinLat;
      var z2 = y * sinLat + z * cosLat;

      return { x: cx + x * radius, y: cy - y2 * radius, visible: z2 > 0 };
    }

    ctx.clearRect(0, 0, w, h);

    var night = this.style === 'night';
    var sea = ctx.createRadialGradient(cx - radius * 0.32, cy - radius * 0.35, radius * 0.1, cx, cy, radius);
    sea.addColorStop(0, night ? '#12233f' : '#2f76b5');
    sea.addColorStop(0.62, night ? '#0a1425' : '#134b7d');
    sea.addColorStop(1, night ? '#050a14' : '#07203c');

    ctx.beginPath();
    ctx.arc(cx, cy, radius, 0, Math.PI * 2);
    ctx.fillStyle = sea;
    ctx.fill();

    // Land.
    var rings = (g.rings || {});
    var selected = this.selected;

    Object.keys(rings).forEach(function (iso3) {
      var isSelected = iso3 === selected;
      ctx.fillStyle = isSelected ? '#4fe3c1' : (night ? '#1d2b3f' : '#3f7a4e');
      ctx.strokeStyle = isSelected ? '#ffffff' : 'rgba(180, 225, 255, 0.28)';
      ctx.lineWidth = Math.max(1, this.globeDpr * 0.5);

      rings[iso3].forEach(function (flat) {
        ctx.beginPath();
        var started = false;
        var drew = false;

        for (var i = 0; i < flat.length; i += 2) {
          var p = project(flat[i], flat[i + 1]);
          if (!p.visible) { started = false; continue; }
          if (!started) { ctx.moveTo(p.x, p.y); started = true; } else { ctx.lineTo(p.x, p.y); }
          drew = true;
        }

        if (drew) { ctx.closePath(); ctx.fill(); ctx.stroke(); }
      });
    }, this);

    // Graticule.
    if (this.layers.graticule) {
      ctx.strokeStyle = 'rgba(190, 225, 255, 0.16)';
      ctx.lineWidth = Math.max(1, this.globeDpr * 0.5);
      for (var lat = -60; lat <= 60; lat += 30) {
        ctx.beginPath();
        var open = false;
        for (var lon = -180; lon <= 180; lon += 3) {
          var p = project(lon, lat);
          if (!p.visible) { open = false; continue; }
          if (!open) { ctx.moveTo(p.x, p.y); open = true; } else { ctx.lineTo(p.x, p.y); }
        }
        ctx.stroke();
      }
      for (var mer = -180; mer < 180; mer += 30) {
        ctx.beginPath();
        var openM = false;
        for (var la = -90; la <= 90; la += 3) {
          var pm = project(mer, la);
          if (!pm.visible) { openM = false; continue; }
          if (!openM) { ctx.moveTo(pm.x, pm.y); openM = true; } else { ctx.lineTo(pm.x, pm.y); }
        }
        ctx.stroke();
      }
    }

    // Markers.
    var self = this;
    function dot(list, color, size) {
      ctx.fillStyle = color;
      list.forEach(function (item) {
        var p = project(item.lon, item.lat);
        if (!p.visible) { return; }
        ctx.beginPath();
        ctx.arc(p.x, p.y, size * self.globeDpr, 0, Math.PI * 2);
        ctx.fill();
      });
    }

    if (this.layers.capitals) { dot(this.data.capitals, 'rgba(255, 200, 87, 0.95)', 1.6); }
    if (this.layers.cities) { dot(this.data.cities || [], 'rgba(255, 236, 180, 0.75)', 1.1); }
    if (this.layers.places) { dot(this.data.places, 'rgba(255, 122, 146, 0.95)', 1.8); }
    if (this.layers.mountains) { dot(this.data.mountains, 'rgba(240, 250, 255, 0.95)', 1.6); }

    // Night side, drawn as a soft shadow away from the subsolar point.
    if (this.layers.daynight && this.sunPoint) {
      var sunProjected = project(this.sunPoint.lon, this.sunPoint.lat);
      var shade = ctx.createRadialGradient(
        sunProjected.x, sunProjected.y, radius * 0.15,
        cx - (sunProjected.x - cx), cy - (sunProjected.y - cy), radius * 2.1
      );
      shade.addColorStop(0, 'rgba(255, 240, 200, 0.10)');
      shade.addColorStop(0.42, 'rgba(0, 0, 0, 0)');
      shade.addColorStop(1, 'rgba(2, 6, 18, 0.72)');

      ctx.save();
      ctx.beginPath();
      ctx.arc(cx, cy, radius, 0, Math.PI * 2);
      ctx.clip();
      ctx.fillStyle = shade;
      ctx.fillRect(0, 0, w, h);
      ctx.restore();
    }

    // Atmosphere.
    var halo = ctx.createRadialGradient(cx, cy, radius * 0.97, cx, cy, radius * 1.14);
    halo.addColorStop(0, 'rgba(120, 200, 255, 0.30)');
    halo.addColorStop(1, 'rgba(120, 200, 255, 0)');
    ctx.beginPath();
    ctx.arc(cx, cy, radius * 1.14, 0, Math.PI * 2);
    ctx.fillStyle = halo;
    ctx.fill();
  };

  /* ---------------------------------------------------------------- tooltip */

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

  /** Approximate lon/lat under the pointer, for the coordinate readout. */
  WorldMap.prototype.pointerCoords = function (event) {
    var rect = this.svg.getBoundingClientRect();
    var ratio = MAP_W / rect.width;
    var mapX = ((event.clientX - rect.left) * ratio - this.view.x) / this.view.k;
    var mapY = ((event.clientY - rect.top) * ratio - this.view.y) / this.view.k;

    // Invert Robinson numerically: the latitude scaling is monotonic, so a
    // short binary search is both simple and accurate enough for a readout.
    var lat = 0;
    var low = -90, high = 90;
    for (var i = 0; i < 24; i++) {
      lat = (low + high) / 2;
      if (global.Projection.project(0, lat, MAP_W).y > mapY) { low = lat; } else { high = lat; }
    }

    var unit = global.Projection.project(180, lat, MAP_W).x - MAP_W / 2;
    var lon = unit === 0 ? 0 : (mapX - MAP_W / 2) / unit * 180;

    return { lon: clamp(lon, -180, 180), lat: clamp(lat, -90, 90) };
  };

  /* ---------------------------------------------------------------- events */

  WorldMap.prototype.bind = function () {
    var self = this;
    var drag = null;

    this.svg.addEventListener('pointerdown', function (event) {
      if (event.button !== 0) { return; }
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
      if (self.coordLabel && !drag) {
        var c = self.pointerCoords(event);
        self.coordLabel.textContent = formatCoords(c.lon, c.lat);
      }

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

      // Measuring mode turns clicks into great-circle endpoints instead.
      if (self.measure.active) {
        var point = self.pointerCoords(event);
        var marker = target.closest('.wm-marker');
        var country = target.closest('.wm-country');

        if (marker && marker.__meta) {
          point = { lon: marker.__meta.lon, lat: marker.__meta.lat, label: marker.__meta.title };
        } else if (country && self.byIso[country.dataset.iso3]) {
          var record = self.byIso[country.dataset.iso3];
          point = { lon: record.lon, lat: record.lat, label: record.name };
        } else {
          point.label = formatCoords(point.lon, point.lat);
        }

        if (!self.measure.from) {
          self.measure.from = point;
          self.clearArc();
          self.ping(point.lon, point.lat);
          self.root.dispatchEvent(new CustomEvent('worldmap:measure', { detail: { from: point, to: null, km: 0 } }));
        } else {
          var km = self.drawArc(self.measure.from, point);
          self.root.dispatchEvent(new CustomEvent('worldmap:measure', {
            detail: { from: self.measure.from, to: point, km: km }
          }));
          self.measure.from = null;
        }
        return;
      }

      var hitMarker = target.closest('.wm-marker');
      if (hitMarker && hitMarker.__meta && hitMarker.__meta.iso3) {
        self.select(hitMarker.__meta.iso3);
        return;
      }

      var hitCountry = target.closest('.wm-country');
      if (hitCountry && self.byIso[hitCountry.dataset.iso3]) {
        self.select(hitCountry.dataset.iso3);
      }
    }

    this.svg.addEventListener('pointerup', endDrag);
    this.svg.addEventListener('pointercancel', endDrag);

    this.svg.addEventListener('wheel', function (event) {
      event.preventDefault();
      var rect = self.svg.getBoundingClientRect();
      var ratio = MAP_W / rect.width;
      self.zoomBy(
        event.deltaY < 0 ? 1.18 : 1 / 1.18,
        (event.clientX - rect.left) * ratio,
        (event.clientY - rect.top) * ratio
      );
    }, { passive: false });

    this.svg.addEventListener('mousemove', function (event) {
      var target = event.target.closest ? event.target.closest('.wm-country, .wm-marker, .wm-river, .wm-lake, .wm-terrain__area') : null;
      if (!target) { self.hideTooltip(); return; }

      if (target.classList.contains('wm-marker')) {
        var meta = target.__meta || {};
        self.showTooltip(event, meta.title || '', meta.meta || '', '📍');
        return;
      }

      if (target.classList.contains('wm-country')) {
        var country = self.byIso[target.dataset.iso3];
        if (!country) { self.hideTooltip(); return; }
        self.showTooltip(
          event,
          country.name,
          country.continent + (country.population ? ' · ' + compact(country.population) + ' people' : ''),
          country.flag
        );
        self.root.dispatchEvent(new CustomEvent('worldmap:hover', { detail: { iso3: country.iso3 } }));
        return;
      }

      // Rivers, lakes and terrain carry a <title> for their name.
      var title = target.querySelector('title');
      if (title) {
        var kind = target.classList.contains('wm-river') ? 'River'
                 : target.classList.contains('wm-lake') ? 'Lake' : 'Terrain';
        self.showTooltip(event, title.textContent, kind, kind === 'River' ? '🏞' : kind === 'Lake' ? '💧' : '⛰');
      }
    });

    this.svg.addEventListener('mouseleave', function () {
      self.hideTooltip();
      self.root.dispatchEvent(new CustomEvent('worldmap:hover', { detail: { iso3: null } }));
    });

    this.svg.addEventListener('keydown', function (event) {
      if (event.key !== 'Enter' && event.key !== ' ') { return; }
      var country = event.target.closest ? event.target.closest('.wm-country') : null;
      if (country && self.byIso[country.dataset.iso3]) {
        event.preventDefault();
        self.select(country.dataset.iso3);
      }
    });

    // Globe interaction.
    if (this.globeCanvas) {
      var spin = null;

      this.globeCanvas.addEventListener('pointerdown', function (event) {
        spin = { x: event.clientX, y: event.clientY, lon: self.globe.targetLon, lat: self.globe.targetLat };
        self.globe.dragging = true;
        self.globeCanvas.setPointerCapture(event.pointerId);
      });

      this.globeCanvas.addEventListener('pointermove', function (event) {
        if (!spin) { return; }
        self.globe.targetLon = spin.lon + (event.clientX - spin.x) * 0.32;
        self.globe.targetLat = clamp(spin.lat - (event.clientY - spin.y) * 0.32, -85, 85);
      });

      this.globeCanvas.addEventListener('pointerup', function (event) {
        spin = null;
        self.globe.dragging = false;
        if (self.globeCanvas.hasPointerCapture(event.pointerId)) {
          self.globeCanvas.releasePointerCapture(event.pointerId);
        }
      });

      this.globeCanvas.addEventListener('dblclick', function () {
        self.globe.spin = !self.globe.spin;
      });

      window.addEventListener('resize', function () {
        if (self.globeOn) { self.resizeGlobe(); self.drawGlobe(); }
      });
    }

    this.root.querySelectorAll('[data-map-action]').forEach(function (button) {
      button.addEventListener('click', function () {
        var action = button.dataset.mapAction;

        if (action === 'zoom-in') { self.zoomBy(1.5); }
        else if (action === 'zoom-out') { self.zoomBy(1 / 1.5); }
        else if (action === 'globe') { self.toggleGlobe(); }
        else if (action === 'reset') {
          self.reset();
          self.select(null, { silent: true, fly: false });
          self.highlightContinent(null);
          self.clearArc();
        } else if (action === 'random') {
          var list = self.data.countries.filter(function (c) { return c.population > 500000; });
          self.select(list[Math.floor(Math.random() * list.length)].iso3);
        }
      });
    });
  };

  WorldMap.prototype.setMeasuring = function (on) {
    this.measure.active = !!on;
    this.measure.from = null;
    this.root.classList.toggle('is-measuring', this.measure.active);
    if (!on) { this.clearArc(); }
  };

  global.WorldMap = WorldMap;
  global.WorldMap.PLACE_COLORS = PLACE_COLORS;
  global.WorldMap.haversine = haversine;
}(window));
