<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;

class FlashSale extends Model
{
    protected $fillable = [
        'name', 'slug', 'starts_at', 'ends_at', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'flash_sale_products')
            ->withPivot(['flash_price', 'stock_limit', 'sort_order'])
            ->orderByPivot('sort_order');
    }

    public function isActiveNow(): bool
    {
        return $this->is_active
            && now()->between($this->starts_at, $this->ends_at);
    }

    public function scopeActiveNow($query)
    {
        return $query->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now());
    }

    /**
     * Map of product_id => best flash price across all sales active right now.
     * Cached briefly (30s) so expired sales stop affecting pricing immediately.
     *
     * @return array<int, int>
     */
    public static function activePriceMap(): array
    {
        return Cache::remember('flash_sales.price_map', now()->addSeconds(30), function () {
            try {
                $map = [];

                static::query()->activeNow()
                    ->with('products:id')
                    ->get()
                    ->each(function (FlashSale $sale) use (&$map) {
                        foreach ($sale->products as $product) {
                            $price = (int) $product->pivot->flash_price;

                            if (! isset($map[$product->id]) || $price < $map[$product->id]) {
                                $map[$product->id] = $price;
                            }
                        }
                    });

                return $map;
            } catch (\Throwable) {
                return [];
            }
        });
    }

    public static function flushPriceMap(): void
    {
        Cache::forget('flash_sales.price_map');
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::flushPriceMap());
        static::deleted(fn () => static::flushPriceMap());
    }
}
