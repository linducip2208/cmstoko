<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:120'],
            'brand' => ['nullable', 'string', 'max:120'],
            'min' => ['nullable', 'integer', 'min:0'],
            'max' => ['nullable', 'integer', 'min:0'],
            'stock' => ['nullable', 'in:in'],
            'sort' => ['nullable', 'in:recommended,latest,price_asc,price_desc,best,discount'],
        ]);

        $products = Product::active()
            ->with(['category:id,name,slug', 'brand:id,name,slug', 'variants:id,product_id,price,stock,is_active'])
            ->withAvg('approvedReviews', 'rating')
            ->withCount('approvedReviews')
            ->when($validated['q'] ?? null, fn ($q, $term) => $q->search($term))
            ->when($validated['category'] ?? null, function ($q, $slug) {
                $q->whereHas('category', fn ($c) => $c->where('slug', $slug));
            })
            ->when($validated['brand'] ?? null, function ($q, $slug) {
                $q->whereHas('brand', fn ($b) => $b->where('slug', $slug));
            })
            ->when(isset($validated['min']), fn ($q) => $q->whereRaw('COALESCE(sale_price, price) >= ?', [(int) $validated['min']]))
            ->when(isset($validated['max']), fn ($q) => $q->whereRaw('COALESCE(sale_price, price) <= ?', [(int) $validated['max']]))
            ->when(($validated['stock'] ?? null) === 'in', fn ($q) => $q->where('stock', '>', 0))
            ->when(
                ($validated['sort'] ?? null) === 'price_asc',
                fn ($q) => $q->orderByRaw('COALESCE(sale_price, price) asc')
            )
            ->when(
                ($validated['sort'] ?? null) === 'price_desc',
                fn ($q) => $q->orderByRaw('COALESCE(sale_price, price) desc')
            )
            ->when(
                in_array($validated['sort'] ?? 'recommended', ['recommended', 'latest', 'best'], true),
                fn ($q) => $q->orderByDesc('is_featured')->orderByDesc('created_at')
            )
            ->when(
                ($validated['sort'] ?? null) === 'discount',
                fn ($q) => $q->whereNotNull('sale_price')->orderByDesc('created_at')
            )
            ->paginate(min((int) $request->integer('per_page', 12), 50))
            ->withQueryString();

        return ProductResource::collection($products);
    }

    public function show(string $slug): ProductResource
    {
        $product = Product::active()
            ->with(['category:id,name,slug', 'brand:id,name,slug', 'variants.attributeValues.option'])
            ->withAvg('approvedReviews', 'rating')
            ->withCount('approvedReviews')
            ->where('slug', $slug)
            ->firstOrFail();

        return new ProductResource($product);
    }
}
