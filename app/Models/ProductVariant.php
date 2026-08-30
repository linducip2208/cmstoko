<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id', 'sku', 'barcode', 'price', 'sale_price', 'cost',
        'stock', 'weight', 'image', 'is_active', 'position',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'integer',
        'sale_price' => 'integer',
        'cost' => 'integer',
        'stock' => 'integer',
        'weight' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductVariantAttributeValue::class, 'variant_id');
    }

    /**
     * Effective price: variant override falls back to product price.
     */
    public function effectivePrice(): int
    {
        $price = $this->price ?? $this->product->price;
        $sale = $this->sale_price ?? $this->product->sale_price;

        return $sale !== null && $sale < $price ? (int) $sale : (int) $price;
    }

    public function hasDiscount(): bool
    {
        $price = $this->price ?? $this->product->price;
        $sale = $this->sale_price ?? $this->product->sale_price;

        return $sale !== null && $sale < $price;
    }

    public function label(): string
    {
        return $this->attributeValues
            ->map(fn (ProductVariantAttributeValue $v) => $v->option->label)
            ->implode(' / ');
    }

    /**
     * Find a variant of the product matching the exact option combination.
     *
     * @param  array<int, int>  $optionIds
     */
    public static function findMatching(Product $product, array $optionIds): ?self
    {
        $attributeCount = Attribute::whereIn('id', function ($query) use ($optionIds) {
            $query->select('attribute_id')
                ->from('product_variant_attribute_values')
                ->whereIn('attribute_option_id', $optionIds);
        })->where('is_variant', true)->count();

        if ($attributeCount === 0) {
            return null;
        }

        return static::query()
            ->where('product_id', $product->id)
            ->where('is_active', true)
            ->whereHas('attributeValues', function (Builder $q) use ($optionIds) {
                $q->whereIn('attribute_option_id', $optionIds);
            }, '=', count($optionIds))
            ->whereDoesntHave('attributeValues', function (Builder $q) use ($optionIds) {
                $q->whereNotIn('attribute_option_id', $optionIds);
            })
            ->first();
    }
}
