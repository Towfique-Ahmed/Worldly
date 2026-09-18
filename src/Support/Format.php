<?php

declare(strict_types=1);

namespace Worldly\Support;

final class Format
{
    public static function number(int|float $value): string
    {
        return number_format((float) $value, 0, '.', ',');
    }

    /** Compact human form: 1.2K, 8.4M, 1.42B. */
    public static function compact(int|float $value): string
    {
        $value = (float) $value;
        $abs = abs($value);

        return match (true) {
            $abs >= 1_000_000_000_000 => self::trim($value / 1_000_000_000_000) . 'T',
            $abs >= 1_000_000_000 => self::trim($value / 1_000_000_000) . 'B',
            $abs >= 1_000_000 => self::trim($value / 1_000_000) . 'M',
            $abs >= 1_000 => self::trim($value / 1_000) . 'K',
            default => (string) round($value),
        };
    }

    public static function money(int|float $value): string
    {
        return '$' . self::compact($value);
    }

    public static function area(int|float $km2): string
    {
        return self::number($km2) . ' km²';
    }

    /** Decimal degrees to 41.0086° N, 28.9802° E. */
    public static function coords(float $lat, float $lon): string
    {
        return sprintf(
            '%.4f° %s, %.4f° %s',
            abs($lat),
            $lat >= 0 ? 'N' : 'S',
            abs($lon),
            $lon >= 0 ? 'E' : 'W',
        );
    }

    /** UTC offset of a timezone right now, as +05:45 / −03:00. */
    public static function offset(string $timezone, ?\DateTimeImmutable $at = null): string
    {
        try {
            $zone = new \DateTimeZone($timezone);
        } catch (\Exception) {
            return '-';
        }

        $moment = ($at ?? new \DateTimeImmutable('now'))->setTimezone($zone);
        $seconds = $zone->getOffset($moment);
        $sign = $seconds < 0 ? '−' : '+';
        $seconds = abs($seconds);

        return sprintf('%s%02d:%02d', $sign, intdiv($seconds, 3600), intdiv($seconds % 3600, 60));
    }

    public static function ordinal(int $n): string
    {
        $suffix = match (true) {
            $n % 100 >= 11 && $n % 100 <= 13 => 'th',
            $n % 10 === 1 => 'st',
            $n % 10 === 2 => 'nd',
            $n % 10 === 3 => 'rd',
            default => 'th',
        };

        return $n . $suffix;
    }

    public static function year(int $year): string
    {
        if ($year === 0) {
            return 'Unrecorded';
        }

        return $year < 0 ? abs($year) . ' BCE' : (string) $year;
    }

    public static function slug(string $value): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $value) ?? ''));

        return trim($slug, '-');
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function trim(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
