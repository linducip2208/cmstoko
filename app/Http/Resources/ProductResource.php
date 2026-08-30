<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public product payload. Never exposes internal/admin-only data.
 */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'type' => $this->type,
            'short_description' => $this->short_description,
            'description' => $this->when($request->routeIs('api.products.show'), $this->description),
            'brand' => $this->when($this->brand, fn () => [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
                'slug' => $this->brand->slug,
            ]),
            'category' => $this->when($this->category, fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
            'price' => $this->effectivePrice(),
            'regular_price' => $this->hasDiscount() ? (int) $this->price : null,
            'discount_percent' => $this->hasDiscount() ? $this->discountPercent() : null,
            'in_stock' => $this->inStock(),
            'stock' => $this->stock,
            'weight' => $this->weight,
            'images' => collect($this->images ?? [])->filter()->values(),
            'cover' => $this->coverImage(),
            'rating' => $this->whenAggregated('approvedReviews', 'rating', 'avg'),
            'reviews_count' => $this->whenCounted('approvedReviews'),
            'variants' => $this->when($this->isConfigurable(), function () {
                return $this->variants->filter(fn ($v) => $v->is_active)->map(fn ($v) => [
                    'id' => $v->id,
                    'sku' => $v->sku,
                    'price' => $v->effectivePrice(),
                    'regular_price' => $v->hasDiscount() ? (int) $v->price : null,
                    'stock' => $v->stock,
                    'label' => $v->label(),
                    'options' => $v->attributeValues->map(fn ($a) => [
                        'attribute' => $a->attribute->name,
                        'option' => $a->option->label,
                    ]),
                ])->values();
            }),
        ];

        return $data;
    }
}
