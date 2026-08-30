<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;

class HomeController extends Controller
{
    public function __invoke()
    {
        return view('pages.home', [
            'featured' => Product::active()->featured()->with('category')->latest()->take(5)->get(),
            'newArrivals' => Product::active()->with('category')->latest()->take(8)->get(),
            'categories' => Category::active()->withCount('activeProducts')->orderBy('sort_order')->get(),
        ]);
    }
}
