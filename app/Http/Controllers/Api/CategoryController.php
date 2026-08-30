<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $categories = Category::active()
            ->root()
            ->with('activeChildren:id,parent_id,name,slug,is_active,sort_order')
            ->orderBy('sort_order')->orderBy('name')
            ->get(['id', 'parent_id', 'name', 'slug', 'is_active', 'sort_order']);

        return CategoryResource::collection($categories);
    }
}
