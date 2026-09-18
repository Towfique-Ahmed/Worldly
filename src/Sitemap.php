<?php

declare(strict_types=1);

namespace Worldly;

use Worldly\Support\Site;

/**
 * Builds the XML sitemap and robots.txt.
 *
 * Every page the app can render is listed, which is currently around 260 URLs -
 * well inside the 50,000 URL / 50 MB limit for a single sitemap file, so no
 * sitemap index is needed. Pages that are personal to the visitor or that
 * generate fresh content on every load are deliberately left out.
 */
final class Sitemap
{
    public function __construct(
        private readonly Atlas $atlas,
        private readonly string $dataDir,
    ) {
    }

    /**
     * @return list<array{loc: string, changefreq: string, priority: string}>
     */
    public function urls(): array
    {
        $urls = [];

        $add = static function (string $path, string $changefreq, string $priority) use (&$urls): void {
            $urls[] = [
                'loc' => Site::url($path),
                'changefreq' => $changefreq,
                'priority' => $priority,
            ];
        };

        // Landing and section pages.
        $add('/', 'daily', '1.0');
        $add('/continents', 'monthly', '0.8');
        $add('/countries', 'monthly', '0.9');
        $add('/mountains', 'monthly', '0.8');
        $add('/waters', 'monthly', '0.8');
        $add('/travel', 'monthly', '0.8');
        $add('/compare', 'monthly', '0.7');
        $add('/quiz', 'monthly', '0.6');
        $add('/clocks', 'daily', '0.7');
        $add('/converter', 'monthly', '0.7');
        $add('/time-zone/gmt', 'daily', '0.7');
        $add('/time-zone/utc', 'daily', '0.7');
        $add('/richest-countries', 'monthly', '0.7');
        $add('/polluted-countries', 'monthly', '0.7');
        $add('/safest-countries', 'monthly', '0.7');
        $add('/peaceful-countries', 'monthly', '0.7');

        // One page per continent.
        foreach (array_keys($this->atlas->continents()) as $name) {
            $add('/continent/' . Support\Format::slug((string) $name), 'monthly', '0.7');
        }

        // One page per country - the bulk of the sitemap, and the pages that
        // carry the ten facts.
        foreach ($this->atlas->countries() as $country) {
            $add('/country/' . $country['iso3'], 'monthly', '0.6');
        }

        return $urls;
    }

    public function xml(): string
    {
        $lastmod = Site::dataLastModified($this->dataDir)->format(DATE_W3C);

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

        foreach ($this->urls() as $url) {
            $xml .= "  <url>\n"
                 . '    <loc>' . htmlspecialchars($url['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n"
                 . '    <lastmod>' . $lastmod . "</lastmod>\n"
                 . '    <changefreq>' . $url['changefreq'] . "</changefreq>\n"
                 . '    <priority>' . $url['priority'] . "</priority>\n"
                 . "  </url>\n";
        }

        return $xml . "</urlset>\n";
    }

    public function robots(): string
    {
        return implode("\n", [
            'User-agent: *',
            'Allow: /',
            '',
            '# Personal to the visitor, or regenerated on every request.',
            'Disallow: /bookmarks',
            'Disallow: /api/',
            '',
            'Sitemap: ' . Site::url('/sitemap.xml'),
            '',
        ]);
    }
}
