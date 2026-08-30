<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\CmsPage;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $xml = Cache::remember('seo.sitemap', now()->addHour(), function (): string {
            return $this->build();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    protected function build(): string
    {
        $urls = [];

        $urls[] = ['loc' => route('home'), 'changefreq' => 'daily', 'priority' => '1.0'];
        $urls[] = ['loc' => route('shop'), 'changefreq' => 'daily', 'priority' => '0.9'];

        $products = Product::active()
            ->where(function ($query) {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->get(['id', 'name', 'slug', 'updated_at']);

        foreach ($products as $product) {
            $urls[] = [
                'loc' => route('product.show', $product->slug),
                'lastmod' => $product->updated_at?->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        foreach (Category::active()->get(['id', 'slug', 'updated_at']) as $category) {
            $urls[] = [
                'loc' => route('shop', ['category' => $category->slug]),
                'lastmod' => $category->updated_at?->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ];
        }

        foreach (Brand::active()->get(['id', 'slug', 'updated_at']) as $brand) {
            $urls[] = [
                'loc' => route('shop', ['brand' => $brand->slug]),
                'lastmod' => $brand->updated_at?->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.6',
            ];
        }

        foreach (CmsPage::published()->get(['id', 'slug', 'updated_at']) as $page) {
            $urls[] = [
                'loc' => route('pages.show', $page->slug),
                'lastmod' => $page->updated_at?->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.5',
            ];
        }

        $entry = function (array $u): string {
            $xml = "  <url>\n    <loc>".htmlspecialchars($u['loc'], ENT_XML1)."</loc>\n";

            if (! empty($u['lastmod'])) {
                $xml .= '    <lastmod>'.htmlspecialchars($u['lastmod'], ENT_XML1)."</lastmod>\n";
            }

            $xml .= '    <changefreq>'.($u['changefreq'] ?? 'weekly')."</changefreq>\n";
            $xml .= '    <priority>'.($u['priority'] ?? '0.5')."</priority>\n  </url>\n";

            return $xml;
        };

        $body = implode('', array_map($entry, collect($urls)->unique('loc')->all()));

        return '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"
            .$body
            .'</urlset>'."\n";
    }
}
