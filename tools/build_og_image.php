<?php

declare(strict_types=1);

/**
 * Renders public/assets/og-cover.png — the 1200x630 card shown when a Worldly
 * link is shared on social platforms or in chat.
 *
 * Usage: php tools/build_og_image.php
 *
 * The map is not a stock image: the same Robinson projection the site uses is
 * applied to the coarse country rings from src/Data/geometry/globe.php and
 * filled with GD, so the cover always matches the atlas it advertises.
 */

require __DIR__ . '/../src/Support/Projection.php';

use Worldly\Support\Projection;

const W = 1200;
const H = 630;

$rings = require __DIR__ . '/../src/Data/geometry/globe.php';
$font = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
$fontRegular = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';

foreach ([$font, $fontRegular] as $file) {
    if (!is_file($file)) {
        fwrite(STDERR, "Missing font: {$file}\n");
        exit(1);
    }
}

$image = imagecreatetruecolor(W, H);
imageantialias($image, true);

/** Vertical gradient background, matching the site's night theme. */
for ($y = 0; $y < H; $y++) {
    $t = $y / H;
    $colour = imagecolorallocate(
        $image,
        (int) round(6 + $t * 10),
        (int) round(9 + $t * 16),
        (int) round(19 + $t * 29),
    );
    imageline($image, 0, $y, W, $y, $colour);
}

// ---------------------------------------------------------------------------
// The map — the whole world, uncropped, sitting below the wordmark
// ---------------------------------------------------------------------------

$mapWidth = 830.0;
$mapHeight = Projection::height($mapWidth);
$offsetX = (W - $mapWidth) / 2;
$offsetY = H - $mapHeight - 14;

$land = imagecolorallocate($image, 74, 126, 92);
$landEdge = imagecolorallocate($image, 120, 192, 160);

foreach ($rings as $countryRings) {
    foreach ($countryRings as $flat) {
        $points = [];

        for ($i = 0; $i < count($flat); $i += 2) {
            [$x, $y] = Projection::point((float) $flat[$i], (float) $flat[$i + 1], $mapWidth);
            $points[] = (int) round($x + $offsetX);
            $points[] = (int) round($y + $offsetY);
        }

        if (count($points) >= 6) {
            imagefilledpolygon($image, $points, $land);
            imagepolygon($image, $points, $landEdge);
        }
    }
}

/**
 * A smooth radial scrim behind the wordmark, drawn per pixel so it has no
 * visible banding or hard edge the way stacked ellipses would.
 */
$centreX = 220.0;
$centreY = 90.0;
$radius = 640.0;

for ($y = 0; $y < H; $y++) {
    for ($x = 0; $x < W; $x++) {
        $distance = sqrt(($x - $centreX) ** 2 + ($y - $centreY) ** 2) / $radius;
        if ($distance >= 1.0) {
            continue;
        }

        // Smoothstep, so the scrim fades out with no seam.
        $strength = (1 - $distance) ** 2;
        $existing = imagecolorat($image, $x, $y);

        $r = ($existing >> 16) & 0xFF;
        $g = ($existing >> 8) & 0xFF;
        $b = $existing & 0xFF;

        imagesetpixel($image, $x, $y, imagecolorallocate(
            $image,
            (int) round($r * (1 - $strength * 0.86) + 8 * $strength * 0.86),
            (int) round($g * (1 - $strength * 0.86) + 22 * $strength * 0.86),
            (int) round($b * (1 - $strength * 0.86) + 34 * $strength * 0.86),
        ));
    }
}

// ---------------------------------------------------------------------------
// Type
// ---------------------------------------------------------------------------

$white = imagecolorallocate($image, 236, 243, 255);
$teal = imagecolorallocate($image, 79, 227, 193);
$dim = imagecolorallocate($image, 160, 178, 208);
$rule = imagecolorallocate($image, 79, 227, 193);

imagefilledrectangle($image, 70, 74, 74, 146, $rule);

imagettftext($image, 60, 0, 100, 126, $white, $font, 'Worldly');
imagettftext($image, 20, 0, 102, 166, $teal, $fontRegular, 'An interactive atlas of everywhere');

$bullets = '242 countries  ·  2,420 facts  ·  335 rivers  ·  46 peaks  ·  3D globe';
imagettftext($image, 16, 0, 100, 200, $dim, $fontRegular, $bullets);

// ---------------------------------------------------------------------------

$target = __DIR__ . '/../public/assets/og-cover.png';
imagepng($image, $target, 9);
imagedestroy($image);

printf("og-cover.png  %d x %d  %.0f KB\n", W, H, filesize($target) / 1024);
