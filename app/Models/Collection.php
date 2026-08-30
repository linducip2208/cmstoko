<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Collection extends Model
{
    public const TYPE_MANUAL = 'manual';

    public const TYPE_RULES = 'rules';

    protected $fillable = [
        'name', 'slug', 'description', 'image', 'type', 'rules',
        'is_active', 'is_featured', 'sort_order', 'seo',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'rules' => 'array',
        'seo' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Collection $collection) {
            if (blank($collection->slug)) {
                $collection->slug = static::uniqueSlug($collection->name);
            }
        });
    }

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $base.'-'.++$i;
        }

        return $slug;
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'collection_products')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    /**
     * Resolve the live product query for this collection.
     * Manual: products linked via pivot. Rules: dynamic matching query.
     */
    public function resolveProducts(): Builder
    {
        if ($this->type === self::TYPE_RULES) {
            $query = Product::active();

            if (is_array($this->rules)) {
                $query->where(function ($q) {
                    foreach ($this->rules as $rule) {
                        if (! is_array($rule)) {
                            continue;
                        }

                        $field = $rule['field'] ?? null;
                        $value = $rule['value'] ?? null;

                        match ($field) {
                            'category' => $q->orWhere('category_id', $value),
                            'brand' => $q->orWhere('brand_id', $value),
                            'price_max' => $q->orWhereRaw('COALESCE(sale_price, price) <= ?', [$value]),
                            'price_min' => $q->orWhereRaw('COALESCE(sale_price, price) >= ?', [$value]),
                            'featured' => $q->orWhere('is_featured', true),
                            'discount' => $q->orWhereNotNull('sale_price'),
                            'new' => $q->orWhere('created_at', '>=', now()->subDays((int) ($value ?? 30))),
                            default => null,
                        };
                    }
                });
            }

            return $query->orderByDesc('created_at');
        }

        $ids = $this->products()->pluck('products.id')->all();

        return Product::active()
            ->whereIn('id', $ids)
            ->orderByRaw('FIELD(id, '.implode(',', $ids ?: [0]).')');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
