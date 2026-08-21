<?php

declare(strict_types=1);

namespace Worldly\Support;

/**
 * JSON-LD blocks.
 *
 * Search engines use these to understand what a page is about beyond its prose,
 * and they are what drive breadcrumb trails and sitelinks search boxes in the
 * results. Everything emitted here describes content that is genuinely on the
 * page — marking up anything else is a manual-action risk, not a shortcut.
 */
final class StructuredData
{
    /** Site-level identity plus the sitelinks search box, for the home page. */
    public static function website(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => 'Worldly',
            'alternateName' => 'Worldly Interactive Atlas',
            'url' => Site::url('/'),
            'description' => 'An interactive atlas: a physical world map and 3D globe, ten facts for every country, rivers, lakes and oceans, mountains, travel destinations, world clocks and a time converter.',
            'inLanguage' => 'en',
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => Site::url('/countries') . '?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * Breadcrumb trail.
     *
     * @param list<array{name: string, path: string}> $trail
     */
    public static function breadcrumbs(array $trail): array
    {
        $items = [];

        foreach ($trail as $index => $crumb) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $crumb['name'],
                'item' => Site::url($crumb['path']),
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    /**
     * A country as a Place, with the coordinates and figures the page shows.
     *
     * @param array<string, mixed>      $country
     * @param array<string, mixed>|null $capital
     * @param list<string>              $facts
     */
    public static function country(array $country, ?array $capital, array $facts): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Country',
            'name' => $country['name'],
            'url' => Site::url('/country/' . $country['iso3']),
            'identifier' => $country['iso3'],
            'alternateName' => array_values(array_unique(array_filter([
                $country['longName'] ?? null,
                $country['formalName'] ?? null,
                $country['nativeName'] ?? null,
            ], static fn (?string $v): bool => $v !== null && $v !== $country['name']))),
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => $country['lat'],
                'longitude' => $country['lon'],
            ],
        ];

        if ($country['area'] > 0) {
            $data['area'] = [
                '@type' => 'QuantitativeValue',
                'value' => $country['area'],
                'unitCode' => 'KMK',
            ];
        }

        if ($capital !== null) {
            $data['containsPlace'] = [
                '@type' => 'City',
                'name' => $capital['name'],
                'geo' => [
                    '@type' => 'GeoCoordinates',
                    'latitude' => $capital['lat'],
                    'longitude' => $capital['lon'],
                ],
            ];
        }

        if ($facts !== []) {
            $data['subjectOf'] = [
                '@type' => 'ItemList',
                'name' => 'Ten facts about ' . $country['name'],
                'numberOfItems' => count($facts),
                'itemListElement' => array_map(
                    static fn (int $i, string $fact): array => [
                        '@type' => 'ListItem',
                        'position' => $i + 1,
                        'name' => $fact,
                    ],
                    array_keys($facts),
                    $facts,
                ),
            ];
        }

        return $data;
    }

    /**
     * A collection page whose main content is a list, such as the country,
     * mountain or destination indexes.
     */
    public static function collection(string $name, string $description, string $path, int $count): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $name,
            'description' => $description,
            'url' => Site::url($path),
            'isPartOf' => ['@type' => 'WebSite', 'name' => 'Worldly', 'url' => Site::url('/')],
            'mainEntity' => [
                '@type' => 'ItemList',
                'numberOfItems' => $count,
            ],
        ];
    }

    /**
     * Site-level organization block — pairs with the WebSite block on the home page.
     */
    public static function organization(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'Worldly',
            'alternateName' => 'Worldly Interactive Atlas',
            'url' => Site::url('/'),
            'logo' => [
                '@type' => 'ImageObject',
                'url' => Site::url('/assets/og-cover.png'),
                'width' => 1200,
                'height' => 630,
            ],
            'description' => 'An interactive world atlas covering every country, continent, mountain range, river, lake and ocean, with world clocks and a time zone converter.',
        ];
    }

    /**
     * FAQ page block for "how it works" style sections.
     *
     * @param list<array{question: string, answer: string}> $items
     */
    public static function faq(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(
                static fn (array $item): array => [
                    '@type' => 'Question',
                    'name' => $item['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $item['answer'],
                    ],
                ],
                $items,
            ),
        ];
    }

    /** Render one or more blocks as script tags. */
    public static function render(array ...$blocks): string
    {
        $html = '';

        foreach ($blocks as $block) {
            if ($block === []) {
                continue;
            }

            $html .= '<script type="application/ld+json">'
                . json_encode($block, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                . "</script>\n";
        }

        return $html;
    }
}
