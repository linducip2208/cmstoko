<?php

namespace App\Services;

use App\Contracts\SearchEngine;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

class DatabaseSearchEngine implements SearchEngine
{
    public function products(string $term): Builder
    {
        return Product::active()->search($term);
    }

    public function suggest(string $term, int $limit = 6): array
    {
        $term = trim($term);

        if ($term === '') {
            return ['products' => collect(), 'categories' => collect(), 'brands' => collect()];
        }

        $like = "%{$term}%";

        return [
            'products' => Product::active()
                ->with('category:id,name,slug')
                ->select('id', 'name', 'slug', 'price', 'sale_price', 'images', 'category_id')
                ->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('sku', 'like', $like))
                ->orderByDesc('is_featured')
                ->limit($limit)
                ->get()
                ->map(fn (Product $p) => [
                    'name' => $p->name,
                    'url' => route('product.show', $p->slug),
                    'price' => $p->effectivePrice(),
                    'cover' => $p->coverImage(),
                ]),
            'categories' => Category::active()
                ->where('name', 'like', $like)
                ->orderBy('name')
                ->limit(4)
                ->get(['id', 'name', 'slug'])
                ->map(fn (Category $c) => ['name' => $c->name, 'url' => route('shop', ['category' => $c->slug])]),
            'brands' => Brand::active()
                ->where('name', 'like', $like)
                ->orderBy('name')
                ->limit(4)
                ->get(['id', 'name', 'slug'])
                ->map(fn (Brand $b) => ['name' => $b->name, 'url' => route('shop', ['brand' => $b->slug])]),
        ];
    }
}
