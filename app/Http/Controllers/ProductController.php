<?php

namespace App\Http\Controllers;

use App\Models\Product;

class ProductController extends Controller
{
    public function show(string $slug)
    {
        $product = Product::active()->with('category')->where('slug', $slug)->firstOrFail();

        return view('pages.product-show', [
            'product' => $product,
            'related' => Product::active()
                ->with('category')
                ->where('category_id', $product->category_id)
                ->whereKeyNot($product->id)
                ->latest()
                ->take(4)
                ->get(),
        ]);
    }
}
