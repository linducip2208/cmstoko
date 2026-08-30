<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WishlistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $product = $this->product;

        return [
            'id' => $this->id,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => $product->effectivePrice(),
                'cover' => $product->coverImage(),
                'in_stock' => $product->inStock(),
            ],
            'added_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
