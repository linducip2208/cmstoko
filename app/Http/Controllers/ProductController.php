<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;

class ProductController extends Controller
{
    public function show(string $slug)
    {
        $product = Product::active()
            ->with(['category', 'brand', 'variants.attributeValues.option'])
            ->where('slug', $slug)
            ->firstOrFail();

        $reviews = Review::approved()
            ->where('product_id', $product->id)
            ->with('user')
            ->latest('approved_at')
            ->paginate(8);

        $ratingAggregate = Review::approved()
            ->where('product_id', $product->id)
            ->selectRaw('AVG(rating) as average, COUNT(*) as total')
            ->first();

        $related = Product::active()
            ->with(['category', 'brand', 'variants'])
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->latest()
            ->take(4)
            ->get();

        return view('pages.product-show', [
            'product' => $product,
            'related' => $related,
            'reviews' => $reviews,
            'ratingAverage' => (float) ($ratingAggregate->average ?? 0),
            'ratingTotal' => (int) ($ratingAggregate->total ?? 0),
            'seo' => \App\Support\Seo::forProduct(
                $product,
                (float) ($ratingAggregate->average ?? 0),
                (int) ($ratingAggregate->total ?? 0),
            ),
        ]);
    }
}
