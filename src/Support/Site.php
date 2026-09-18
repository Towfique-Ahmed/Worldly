<?php

declare(strict_types=1);

namespace Worldly\Support;

/**
 * Absolute-URL helpers for canonical tags, Open Graph and the sitemap.
 *
 * Search engines need absolute URLs, but the app has no idea what domain it is
 * deployed on. The origin is read from WORLDLY_BASE_URL when set - do set it in
 * production, since it is the only reliable value behind a proxy - and inferred
 * from the current request otherwise.
 */
final class Site
{
    /** Overridden with the WORLDLY_GA_ID environment variable. */
    private const DEFAULT_GA_ID = 'G-5HDW2KGKVC';

    private static ?string $baseUrl = null;

    /** Origin with no trailing slash, e.g. https://worldly.example. */
    public static function baseUrl(): string
    {
        if (self::$baseUrl !== null) {
            return self::$baseUrl;
        }

        $configured = getenv('WORLDLY_BASE_URL');
        if (is_string($configured) && $configured !== '') {
            return self::$baseUrl = rtrim($configured, '/');
        }

        $https = ($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off';
        $forwarded = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
        $scheme = $forwarded !== '' ? explode(',', $forwarded)[0] : ($https ? 'https' : 'http');

        $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
        $host = explode(',', (string) $host)[0];

        return self::$baseUrl = trim($scheme) . '://' . trim($host);
    }

    /** Absolute URL for an application path. */
    public static function url(string $path = '/'): string
    {
        return self::baseUrl() . '/' . ltrim($path, '/');
    }

    /** Canonical URL for the request being served, with the query string dropped. */
    public static function canonical(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $path = '/' . trim($path, '/');

        return self::url($path === '/' ? '' : $path);
    }

    /**
     * Google Analytics measurement ID, or null when analytics are switched off.
     *
     * Set WORLDLY_GA_ID to use a different property, or to an empty string to
     * disable the tag entirely - useful for local development and for staging,
     * where you rarely want traffic landing in the production property.
     */
    public static function analyticsId(): ?string
    {
        $configured = getenv('WORLDLY_GA_ID');

        if ($configured === false) {
            return self::DEFAULT_GA_ID;
        }

        $configured = trim($configured);

        return $configured === '' ? null : $configured;
    }

    /**
     * Newest modification time across the generated datasets, used as the
     * sitemap's lastmod so it only moves when the content actually changes.
     */
    public static function dataLastModified(string $dataDir): \DateTimeImmutable
    {
        $newest = 0;

        foreach (glob($dataDir . '/*.php') ?: [] as $file) {
            $newest = max($newest, (int) filemtime($file));
        }

        return (new \DateTimeImmutable('@' . ($newest ?: time())))->setTimezone(new \DateTimeZone('UTC'));
    }
}
