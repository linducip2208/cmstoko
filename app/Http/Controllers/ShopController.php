<?php

namespace App\Http\Controllers;

use App\Contracts\SearchEngine;
use App\Models\Brand;
use App\Models\Category;
use App\Support\Seo;
use App\Support\Settings;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'category' => ['nullable', 'string', 'max:120'],
            'brand' => ['nullable', 'string', 'max:120'],
            'q' => ['nullable', 'string', 'max:120'],
            'min' => ['nullable', 'integer', 'min:0'],
            'max' => ['nullable', 'integer', 'min:0'],
            'stock' => ['nullable', 'in:in'],
            'sort' => ['nullable', 'in:recommended,latest,price_asc,price_desc,best,discount'],
        ]);

        $category = null;
        $brand = null;

        $products = app(SearchEngine::class)->products((string) $request->q)
            ->with(['category', 'brand', 'variants'])
            ->when($request->filled('category'), function ($q) use ($request, &$category) {
                $category = Category::active()->where('slug', $request->category)->first();

                if ($category) {
                    $ids = $category->descendantIds();
                    $q->whereIn('category_id', $ids);
                } else {
                    $q->whereRaw('1 = 0'); // unknown category = empty result
                }
            })
            ->when($request->filled('brand'), function ($q) use ($request, &$brand) {
                $brand = Brand::active()->where('slug', $request->brand)->first();
                $q->where('brand_id', $brand?->id ?? 0);
            })
            ->search((string) $request->q)
            ->when($request->filled('min'), fn ($q) => $q->whereRaw('COALESCE(sale_price, price) >= ?', [(int) $request->min]))
            ->when($request->filled('max'), fn ($q) => $q->whereRaw('COALESCE(sale_price, price) <= ?', [(int) $request->max]))
            ->when($request->stock === 'in', fn ($q) => $q->where('stock', '>', 0))
            ->when(
                $request->sort === 'price_asc',
                fn ($q) => $q->orderByRaw('COALESCE(sale_price, price) asc')
            )
            ->when(
                $request->sort === 'price_desc',
                fn ($q) => $q->orderByRaw('COALESCE(sale_price, price) desc')
            )
            ->when(
                $request->sort === 'best',
                fn ($q) => $q->orderByDesc('is_featured')->orderByDesc('created_at')
            )
            ->when(
                $request->sort === 'discount',
                fn ($q) => $q->whereNotNull('sale_price')->orderByDesc('created_at')
            )
            ->when(
                in_array($request->sort, ['recommended', null, ''], true) || $request->sort === 'latest',
                fn ($q) => $q->orderByDesc('is_featured')->orderByDesc('created_at')
            )
            ->paginate(12)
            ->withQueryString();

        $rootCategories = Category::active()
            ->root()
            ->withCount('activeProducts')
            ->orderBy('sort_order')
            ->get();

        $brands = Brand::active()
            ->withCount('products')
            ->orderBy('name')
            ->get();

        $activeFilters = collect();

        if ($category) {
            $activeFilters->put('Kategori', $category->name);
        }
        if ($brand) {
            $activeFilters->put('Merek', $brand->name);
        }
        if ($request->filled('q')) {
            $activeFilters->put('Pencarian', $request->q);
        }
        if ($request->filled('min') || $request->filled('max')) {
            $activeFilters->put('Harga', 'Rp '.number_format((int) $request->min, 0, ',', '.').' - '.number_format((int) $request->max, 0, ',', '.'));
        }
        if ($request->stock === 'in') {
            $activeFilters->put('Stok', 'Tersedia');
        }

        // SEO meta: category/brand landing gets entity meta; plain shop/search gets canonical-safe meta.
        $isFiltered = collect(['min', 'max', 'stock', 'q', 'sort'])
            ->contains(fn (string $param) => $request->filled($param));

        $seo = match (true) {
            $category !== null => Seo::forCategory($category),
            $brand !== null => Seo::forBrand($brand),
            default => Seo::meta(
                title: 'Katalog',
                description: $request->filled('q')
                    ? 'Hasil pencarian "'.strip_tags((string) $request->q).'"'
                    : Settings::get('store.tagline'),
                canonical: $request->query() ? route('shop', $request->query()) : route('shop'),
                robots: $request->query() ? 'noindex, follow' : null,
            ),
        };

        // Canonical landing pages become noindex once the visitor filters them.
        if ($isFiltered && ($category !== null || $brand !== null)) {
            $seo['robots'] = 'noindex, follow';
        }

        return view('pages.shop', [
            'products' => $products,
            'rootCategories' => $rootCategories,
            'brands' => $brands,
            'category' => $category,
            'brand' => $brand,
            'activeFilters' => $activeFilters,
            'seo' => $seo,
        ]);
    }
}
