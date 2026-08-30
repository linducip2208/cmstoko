<?php

namespace App\Shop;

use App\Models\Category;
use App\Models\Collection;
use App\Models\HomepageSection;
use App\Models\Product;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Section content resolver — returns the data each section type needs.
 * Keeps section components free of query logic and shared with future APIs.
 */
class SectionResolver
{
    public function resolve(HomepageSection $section): array
    {
        return [
            'section' => $section,
            'config' => $section->config ?? [],
            'products' => $this->products($section),
            'categories' => $this->categories($section),
        ];
    }

    protected function products(HomepageSection $section): SupportCollection
    {
        $query = Product::active()->with(['category', 'brand', 'variants']);

        $source = $section->config('source', 'featured');
        $limit = (int) $section->config('limit', 8);

        return match ($source) {
            'featured' => $query->featured()->orderByDesc('created_at')->limit($limit)->get(),
            'new' => $query->orderByDesc('created_at')->limit($limit)->get(),
            'best' => $query->orderByDesc('is_featured')->limit($limit)->get(),
            'discount' => $query->whereNotNull('sale_price')->orderByDesc('created_at')->limit($limit)->get(),
            'collection' => $this->collectionProducts($section->config('collection_slug'), $limit),
            'category' => $query->whereHas('category', fn ($q) => $q->where('slug', $section->config('category_slug')))->limit($limit)->get(),
            'product_ids' => $query->whereIn('id', array_map('intval', (array) $section->config('product_ids', [])))->limit($limit)->get(),
            default => collect(),
        };
    }

    protected function collectionProducts(?string $slug, int $limit): SupportCollection
    {
        if (! $slug) {
            return collect();
        }

        $collection = Collection::active()->where('slug', $slug)->first();

        if (! $collection) {
            return collect();
        }

        return $collection->resolveProducts()->with(['category', 'brand', 'variants'])->limit($limit)->get();
    }

    protected function categories(HomepageSection $section): SupportCollection
    {
        if ($section->type !== 'category_grid') {
            return collect();
        }

        $limit = (int) $section->config('limit', 8);

        return Category::active()
            ->root()
            ->withCount('activeProducts')
            ->orderBy('sort_order')
            ->limit($limit)
            ->get();
    }
}
