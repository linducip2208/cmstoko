<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BrandController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $brands = Brand::active()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description', 'logo', 'is_active']);

        return BrandResource::collection($brands);
    }
}
