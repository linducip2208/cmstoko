<?php

namespace App\Support;

use App\Models\Brand;
use App\Models\Category;
use App\Models\CmsPage;
use App\Models\Collection;
use App\Models\Product;

class Seo
{
    /**
     * Normalised SEO payload for views:
     * title, description, canonical, image, robots, schema (list of JSON-LD graphs).
     *
     * @return array{title: string, description: ?string, canonical: string, image: ?string, robots: ?string, schema: array<int, array<string, mixed>>}
     */
    public static function meta(
        string $title,
        ?string $description = null,
        ?string $canonical = null,
        ?string $image = null,
        ?string $robots = null,
        array $schema = [],
    ): array {
        return [
            'title' => $title,
            'description' => $description ? mb_substr(trim(strip_tags($description)), 0, 320) : null,
            'canonical' => $canonical ?: url()->current(),
            'image' => $image,
            'robots' => $robots,
            'schema' => $schema,
        ];
    }

    /**
     * Strip script blocks (incl. their content) + tags for safe meta descriptions.
     */
    public static function safeText(?string $html, int $limit = 320): ?string
    {
        if ($html === null) {
            return null;
        }

        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', ' ', $html);
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', ' ', $html);

        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $html)));

        return $text === '' ? null : mb_substr($text, 0, $limit);
    }

    public static function entityMeta(string $defaultTitle, ?array $entitySeo, ?string $fallbackDescription, string $canonical, ?string $image = null, ?string $robots = null, array $schema = []): array
    {
        $metaTitle = $entitySeo['meta_title'] ?? null;
        $metaDescription = $entitySeo['meta_description'] ?? null;

        return static::meta(
            title: $metaTitle ?: $defaultTitle,
            description: $metaDescription ?: $fallbackDescription,
            canonical: $canonical,
            image: $image,
            robots: $robots,
            schema: $schema,
        );
    }

    public static function forHome(): array
    {
        return static::meta(
            title: Settings::get('seo.home_title') ?? Settings::get('store.name', config('shop.name', 'TokoKita')),
            description: Settings::get('seo.home_description') ?? Settings::get('store.tagline'),
            canonical: route('home'),
            image: Settings::get('seo.og_image'),
            schema: [static::organization(), static::website()],
        );
    }

    public static function forProduct(Product $product, float $ratingAverage = 0.0, int $ratingTotal = 0): array
    {
        $schema = [
            static::breadcrumb([
                ['name' => 'Beranda', 'url' => route('home')],
                ['name' => 'Katalog', 'url' => route('shop')],
                ['name' => $product->name],
            ]),
        ];

        $schema[] = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => mb_substr(strip_tags((string) ($product->short_description ?: $product->description)), 0, 500),
            'sku' => $product->sku,
            'brand' => $product->brand ? ['@type' => 'Brand', 'name' => $product->brand->name] : null,
            'image' => [$product->coverImage()],
            'offers' => [
                '@type' => 'Offer',
                'url' => route('product.show', $product->slug),
                'priceCurrency' => 'IDR',
                'price' => $product->effectivePrice(),
                'availability' => $product->inStock()
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
                'itemCondition' => 'https://schema.org/NewCondition',
            ],
        ];

        // AggregateRating only from real approved reviews.
        if ($ratingTotal > 0 && $ratingAverage > 0) {
            $schema[1]['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => round($ratingAverage, 1),
                'reviewCount' => $ratingTotal,
            ];
        }

        return static::entityMeta(
            defaultTitle: $product->name,
            entitySeo: $product->seo,
            fallbackDescription: static::safeText($product->short_description ?: $product->description, 160),
            canonical: route('product.show', $product->slug),
            image: $product->coverImage(),
            schema: array_values(array_filter($schema)),
        );
    }

    public static function forCategory(Category $category): array
    {
        return static::entityMeta(
            defaultTitle: $category->name,
            entitySeo: $category->seo,
            fallbackDescription: $category->short_description ?: 'Koleksi '.$category->name.' pilihan terbaik.',
            canonical: route('shop', ['category' => $category->slug]),
            image: $category->cover_image,
            schema: [
                static::breadcrumb([
                    ['name' => 'Beranda', 'url' => route('home')],
                    ['name' => 'Katalog', 'url' => route('shop')],
                    ['name' => $category->name, 'url' => route('shop', ['category' => $category->slug])],
                ]),
            ],
        );
    }

    public static function forBrand(Brand $brand): array
    {
        return static::entityMeta(
            defaultTitle: $brand->name,
            entitySeo: $brand->seo,
            fallbackDescription: $brand->description ?: 'Produk dari merek '.$brand->name.'.',
            canonical: route('shop', ['brand' => $brand->slug]),
            image: $brand->cover,
        );
    }

    public static function forCollection(Collection $collection): array
    {
        return static::entityMeta(
            defaultTitle: $collection->name,
            entitySeo: $collection->seo,
            fallbackDescription: $collection->description,
            canonical: url()->current(),
        );
    }

    public static function forPage(CmsPage $page): array
    {
        return static::entityMeta(
            defaultTitle: $page->title,
            entitySeo: $page->seo,
            fallbackDescription: static::safeText((string) $page->content, 160),
            canonical: route('pages.show', $page->slug),
        );
    }

    /**
     * Organization graph built from real settings only.
     */
    public static function organization(): array
    {
        $socials = [];
        foreach (['instagram', 'tiktok', 'facebook', 'youtube'] as $network) {
            if ($url = Settings::get('store.social.'.$network)) {
                $socials[] = $url;
            }
        }

        $graph = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => Settings::get('store.name', config('shop.name', 'TokoKita')),
            'url' => route('home'),
        ];

        if ($logo = Settings::get('store.logo')) {
            $graph['logo'] = $logo;
        }

        if ($email = Settings::get('store.email')) {
            $graph['contactPoint'] = [
                '@type' => 'ContactPoint',
                'email' => $email,
                'contactType' => 'customer service',
            ];
        }

        if ($socials !== []) {
            $graph['sameAs'] = $socials;
        }

        return $graph;
    }

    /**
     * WebSite graph. SearchAction only emitted because /produk?q= is a real, working search.
     */
    public static function website(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => Settings::get('store.name', config('shop.name', 'TokoKita')),
            'url' => route('home'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => route('shop').'?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * BreadcrumbList from [name, url?] tuples.
     */
    public static function breadcrumb(array $items): array
    {
        $list = [];

        foreach (array_values($items) as $index => $item) {
            $entry = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
            ];

            if (! empty($item['url'])) {
                $entry['item'] = $item['url'];
            }

            $list[] = $entry;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $list,
        ];
    }
}
