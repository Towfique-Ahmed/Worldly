<?php

declare(strict_types=1);

/**
 * Continent profiles. `accent` drives the map choropleth and the card gradients;
 * `focus` is the lon/lat the map animates to when a continent is selected.
 */

return [
    'Africa' => [
        'name' => 'Africa',
        'accent' => '#f2a03d',
        'accent2' => '#e2632f',
        'area' => 30370000,
        'highest' => 'Kilimanjaro, 5,895 m',
        'lowest' => 'Lake Assal, −155 m',
        'countries' => 54,
        'focus' => ['lon' => 20, 'lat' => 3, 'zoom' => 2.4],
        'blurb' => 'The second largest and second most populous continent, straddling the equator so evenly that it reaches almost equally far into both hemispheres. It holds the longest river, the largest hot desert and the oldest human fossils yet found.',
        'facts' => [
            'The Sahara alone is close to the size of the United States.',
            'Africa has the youngest population of any continent - a median age around 19.',
            'The Great Rift Valley is splitting the continent apart at a few millimetres a year.',
        ],
    ],
    'Asia' => [
        'name' => 'Asia',
        'accent' => '#f2585b',
        'accent2' => '#b02a7a',
        'area' => 44579000,
        'highest' => 'Mount Everest, 8,849 m',
        'lowest' => 'Dead Sea shore, −430 m',
        'countries' => 49,
        'focus' => ['lon' => 90, 'lat' => 35, 'zoom' => 2.2],
        'blurb' => 'The largest continent by both area and population, carrying roughly six in every ten people alive. It contains the highest and lowest points on land, and every major religion began somewhere inside it.',
        'facts' => [
            'Russia and Turkey sit in both Asia and Europe.',
            'Asia holds the ten highest mountains on Earth, all of them in the Himalaya-Karakoram belt.',
            'The Dead Sea shore is the lowest exposed land anywhere on the planet.',
        ],
    ],
    'Europe' => [
        'name' => 'Europe',
        'accent' => '#4d9df6',
        'accent2' => '#6f5cf0',
        'area' => 10180000,
        'highest' => 'Mount Elbrus, 5,642 m',
        'lowest' => 'Caspian Sea shore, −28 m',
        'countries' => 44,
        'focus' => ['lon' => 15, 'lat' => 52, 'zoom' => 3.6],
        'blurb' => 'A peninsula of peninsulas at the western end of Eurasia, small in area but dense with coastline, borders and languages. No point in Europe is much more than 500 km from the sea.',
        'facts' => [
            'The Vatican, at 0.49 km², is the smallest sovereign state in the world.',
            'Iceland sits astride the Mid-Atlantic Ridge and is growing wider each year.',
            'Europe has more than 200 living languages in an area smaller than Canada.',
        ],
    ],
    'North America' => [
        'name' => 'North America',
        'accent' => '#37c9a3',
        'accent2' => '#2f8fd6',
        'area' => 24709000,
        'highest' => 'Denali, 6,190 m',
        'lowest' => 'Badwater Basin, −86 m',
        'countries' => 23,
        'focus' => ['lon' => -100, 'lat' => 45, 'zoom' => 2.4],
        'blurb' => 'Stretching from the Arctic ice to the Panama isthmus, this continent packs boreal forest, prairie, desert and rainforest into a single north-south sweep - and holds the largest freshwater system on Earth.',
        'facts' => [
            'The Great Lakes contain about 21 per cent of the world\'s surface fresh water.',
            'Greenland is the largest island on Earth and part of North America.',
            'Death Valley recorded 56.7 °C in 1913, the highest reliable air temperature ever measured.',
        ],
    ],
    'South America' => [
        'name' => 'South America',
        'accent' => '#8ed14a',
        'accent2' => '#1fa87a',
        'area' => 17840000,
        'highest' => 'Aconcagua, 6,961 m',
        'lowest' => 'Laguna del Carbón, −105 m',
        'countries' => 12,
        'focus' => ['lon' => -60, 'lat' => -18, 'zoom' => 2.6],
        'blurb' => 'Defined by two extremes running side by side: the Andes, the longest mountain range on the planet, and the Amazon, the largest river by volume and the largest rainforest anywhere.',
        'facts' => [
            'The Amazon discharges more water than the next seven largest rivers combined.',
            'The Atacama Desert has weather stations that have never recorded rain.',
            'Angel Falls drops 979 m - so far that much of it becomes mist before landing.',
        ],
    ],
    'Oceania' => [
        'name' => 'Oceania',
        'accent' => '#f5c542',
        'accent2' => '#f2704a',
        'area' => 8600000,
        'highest' => 'Puncak Jaya, 4,884 m',
        'lowest' => 'Lake Eyre, −15 m',
        'countries' => 14,
        'focus' => ['lon' => 150, 'lat' => -22, 'zoom' => 2.6],
        'blurb' => 'Mostly ocean: a continent-sized landmass in Australia plus tens of thousands of islands scattered across a third of the Earth\'s surface, home to species found nowhere else.',
        'facts' => [
            'Oceania spans about 25,000 islands across the Pacific.',
            'Australia is the flattest and driest inhabited continent.',
            'The Mariana Trench, at nearly 11 km deep, lies within the region.',
        ],
    ],
    'Antarctica' => [
        'name' => 'Antarctica',
        'accent' => '#7fd8ff',
        'accent2' => '#5c7cf0',
        'area' => 14200000,
        'highest' => 'Mount Vinson, 4,892 m',
        'lowest' => 'Bentley Subglacial Trench, −2,540 m',
        'countries' => 0,
        'focus' => ['lon' => 0, 'lat' => -75, 'zoom' => 2.0],
        'blurb' => 'A desert of ice with no permanent residents and no government - only research stations under a treaty that reserves the whole continent for science.',
        'facts' => [
            'It holds roughly 70 per cent of the world\'s fresh water as ice.',
            '−89.2 °C was recorded at Vostok Station, the coldest air temperature on record.',
            'Technically a desert: the interior receives less precipitation than the Sahara.',
        ],
    ],
];
