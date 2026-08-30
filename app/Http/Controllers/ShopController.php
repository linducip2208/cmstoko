<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function __invoke(Request $request)
    {
        $products = Product::active()
            ->with('category')
            ->when($request->filled('category'), fn ($q) => $q->whereHas('category', fn ($c) => $c->where('slug', $request->category)))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$request->q.'%')
                ->orWhere('description', 'like', '%'.$request->q.'%')))
            ->when($request->sort === 'price_asc', fn ($q) => $q->orderByRaw('COALESCE(sale_price, price) asc'))
            ->when($request->sort === 'price_desc', fn ($q) => $q->orderByRaw('COALESCE(sale_price, price) desc'))
            ->when(! in_array($request->sort, ['price_asc', 'price_desc']), fn ($q) => $q->latest())
            ->paginate(12)
            ->withQueryString();

        return view('pages.shop', [
            'products' => $products,
            'categories' => Category::active()->withCount('activeProducts')->orderBy('sort_order')->get(),
            'activeCategory' => $request->category,
        ]);
    }
}
