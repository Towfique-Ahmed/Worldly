# 🌍 Worldly

An interactive atlas built in plain PHP — a live world map, world clocks, a time
converter, continents, countries, mountains and travel destinations.

No framework. No Composer packages. No third-party JavaScript. No map tiles, no
CDN, no tracking. Everything ships from this repository.

---

## What's in it

| Page | What it does |
|---|---|
| **Explore** (`/`) | Pan/zoom world map with a live day-night terminator, switchable layers (grid, capitals, peaks, travel pins), three colour modes (continent, population, GDP per person), hover tooltips, click-to-open country panel, fuzzy search, and a "surprise me" dice |
| **Continents** (`/continents`) | Seven continent profiles with comparative meters, plus a map that isolates and flies to each one |
| **Countries** (`/countries`) | All 177 mapped countries, filterable by continent and sortable by name, population or GDP per person |
| **Country** (`/country/{iso3}`) | Full profile: flag, population, GDP, capital, largest cities, peaks, destinations, neighbours, and the country highlighted on the map |
| **Mountains** (`/mountains`) | A to-scale elevation ridge of the 26 highest featured peaks, plus 46 profiles covering all fourteen eight-thousanders and the Seven Summits |
| **Travel** (`/travel`) | 68 destinations pinned by category, with the season that actually suits each one |
| **Clocks** (`/clocks`) | Analog clock wall that tints with the local hour, a countdown timer that rings, and a stopwatch with laps |
| **Converter** (`/converter`) | Convert any moment between any two of PHP's IANA zones, with a day/night bar and the same instant shown across twelve cities |

### JSON endpoints

```
GET /api/country/{iso3}      full country record
GET /api/time/{zone}         current time and offset for an IANA zone
GET /api/convert?from=&to=&at=
GET /api/search?q=           countries, cities, peaks and places
```

---

## Running it

Requires **PHP 8.1+** only.

```bash
php -S localhost:8000 -t public public/index.php
```

Then open <http://localhost:8000>.

Behind nginx or Apache, point the document root at `public/` and route all
non-file requests to `public/index.php`.

---

## How the map works

The world map is **not** an image and **not** a tile layer. Country outlines come
from [Natural Earth](https://www.naturalearthdata.com/) 1:110m (public domain),
simplified with Ramer–Douglas–Peucker and projected into a **Robinson
projection** by PHP at build time, then emitted as SVG paths.

The same projection is implemented twice — once in
`src/Support/Projection.php` and once in `public/assets/js/projection.js` — so
that markers, the terminator and fly-to animations computed in the browser land
exactly on the coastlines rendered by PHP. Change one and you must change the
other.

The day/night shading is a real terminator: the browser computes the subsolar
point from the date with a low-precision NOAA solar-position algorithm, solves
the terminator latitude for every longitude, projects the result, and closes the
polygon around whichever pole is currently in darkness. It refreshes every
minute.

### Regenerating the geodata

`src/Data/countries.php` and `src/Data/cities.php` are generated. To rebuild
them (for example after changing the simplification tolerance):

```bash
php tools/build_geodata.php
```

The script downloads the Natural Earth source files into `storage/raw/` on first
run and caches them there. Only the generated PHP is tracked in git.

---

## Layout

```
public/
  index.php              front controller and routes
  assets/css/app.css     the whole design system, both themes
  assets/js/
    projection.js        Robinson projection (browser twin of the PHP class)
    worldmap.js          pan, zoom, layers, terminator, selection
    app.js               backdrop, theme, search, reveals, filters
    time.js              clock wall, timer, stopwatch, converter
src/
  Atlas.php              read model over the datasets
  Router.php             pattern router, HTML or JSON
  View.php               template renderer
  bootstrap.php          autoloader and asset versioning
  Support/               Projection, Format, Timezones
  Data/                  countries + cities (generated), mountains, places, continents
  View/                  layout, partials, pages
tools/build_geodata.php  Natural Earth → src/Data
```

---

## Notes

- **Themes.** Night by default, with a full light theme; the choice is stored in
  `localStorage` and applied before paint.
- **Motion.** Every animation is disabled under `prefers-reduced-motion`.
- **Time.** Zone lists and server-side offsets come from PHP's bundled IANA
  database; browser-side arithmetic goes through `Intl.DateTimeFormat` with an
  explicit `timeZone`, so daylight saving and 45-minute offsets are handled by
  the platform rather than by hand.
- **Data vintage.** Population and GDP figures are Natural Earth's estimates and
  are a few years old — good for scale and comparison, not for citation.

## Credits

Country and city geometry: [Natural Earth](https://www.naturalearthdata.com/),
public domain. Mountain and destination datasets are hand-curated in
`src/Data/`.
