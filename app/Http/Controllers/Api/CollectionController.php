<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CollectionResource;
use App\Models\Collection;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CollectionController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $collections = Collection::active()
            ->where('is_featured', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug', 'description', 'is_featured', 'is_active']);

        return CollectionResource::collection($collections);
    }
}
