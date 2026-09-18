/**
 * Robinson projection - the browser-side twin of src/Support/Projection.php.
 *
 * Both must agree exactly, otherwise markers drift off the coastlines that PHP
 * rendered into the SVG.
 */
(function (global) {
  'use strict';

  var AA = [
    1.0000, 0.9986, 0.9954, 0.9900, 0.9822, 0.9730, 0.9600, 0.9427, 0.9216,
    0.8962, 0.8679, 0.8350, 0.7986, 0.7597, 0.7186, 0.6732, 0.6213, 0.5722, 0.5322
  ];
  var BB = [
    0.0000, 0.0620, 0.1240, 0.1860, 0.2480, 0.3100, 0.3720, 0.4340, 0.4958,
    0.5571, 0.6176, 0.6769, 0.7346, 0.7903, 0.8435, 0.8936, 0.9394, 0.9761, 1.0000
  ];

  var HALF_WIDTH = 0.8487 * Math.PI;
  var HALF_HEIGHT = 1.3523;
  var ASPECT = HALF_WIDTH / HALF_HEIGHT;

  function interpolate(table, index) {
    var last = table.length - 1;
    var i = Math.floor(index);
    if (i < 1) { i = 1; }
    if (i > last - 2) { i = last - 2; }

    var t = index - i;
    var p0 = table[i - 1], p1 = table[i], p2 = table[i + 1], p3 = table[i + 2];

    return 0.5 * (
      2 * p1 +
      (-p0 + p2) * t +
      (2 * p0 - 5 * p1 + 4 * p2 - p3) * t * t +
      (-p0 + 3 * p1 - 3 * p2 + p3) * t * t * t
    );
  }

  /** Project lon/lat degrees into map coordinates for a map `width` px wide. */
  function project(lon, lat, width) {
    var height = width / ASPECT;
    var abs = Math.min(Math.abs(lat), 90);
    var index = abs / 5;

    var x = 0.8487 * (lon * Math.PI / 180) * interpolate(AA, index);
    var y = 1.3523 * interpolate(BB, index) * (lat < 0 ? -1 : 1);

    return {
      x: (x / HALF_WIDTH * 0.5 + 0.5) * width,
      y: (0.5 - y / HALF_HEIGHT * 0.5) * height
    };
  }

  global.Projection = {
    project: project,
    aspect: ASPECT,
    height: function (width) { return width / ASPECT; }
  };
}(window));
