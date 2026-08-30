<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WishlistResource;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class WishlistController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $wishlists = Wishlist::where('user_id', $request->user()->id)
            ->whereHas('product', fn ($q) => $q->where('is_active', true))
            ->with(['product:id,name,slug,price,sale_price,stock,images'])
            ->latest()
            ->get();

        return WishlistResource::collection($wishlists);
    }

    public function toggle(Request $request): JsonResource
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer'],
        ]);

        $product = Product::active()->whereKey($validated['product_id'])->firstOrFail();

        $existing = Wishlist::where('user_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return new JsonResource(['product_id' => $product->id, 'wishlisted' => false]);
        }

        Wishlist::create([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
        ]);

        return new JsonResource(['product_id' => $product->id, 'wishlisted' => true]);
    }
}
