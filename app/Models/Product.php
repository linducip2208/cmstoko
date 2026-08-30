<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    public const TYPE_SIMPLE = 'simple';

    public const TYPE_CONFIGURABLE = 'configurable';

    public const TYPES = [
        self::TYPE_SIMPLE => 'Sederhana',
        self::TYPE_CONFIGURABLE => 'Dengan Varian',
    ];

    protected $fillable = [
        'category_id', 'brand_id', 'type', 'name', 'slug', 'sku', 'short_description', 'description',
        'price', 'sale_price', 'stock', 'weight', 'images', 'is_active', 'is_featured',
        'seo', 'attribute_values', 'published_at',
    ];

    protected $casts = [
        'images' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'seo' => 'array',
        'attribute_values' => 'array',
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            if (blank($product->slug)) {
                $product->slug = static::uniqueSlug($product->name);
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('position');
    }

    public function activeVariants(): HasMany
    {
        return $this->variants()->where('is_active', true);
    }

    /**
     * Approved reviews only — the source for all public rating aggregates.
     */
    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('status', Review::STATUS_APPROVED);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'collection_products');
    }

    public function variantAttributes(): array
    {
        $attributeIds = ProductVariantAttributeValue::query()
            ->whereIn('variant_id', $this->variants->pluck('id'))
            ->distinct()
            ->pluck('attribute_id');

        return Attribute::with('options')->whereIn('id', $attributeIds)->orderBy('position')->get()->all();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('short_description', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('sku', 'like', "%{$term}%")
                ->orWhereHas('brand', fn (Builder $b) => $b->where('name', 'like', "%{$term}%"))
                ->orWhereHas('category', fn (Builder $c) => $c->where('name', 'like', "%{$term}%"));
        });
    }

    public function effectivePrice(): int
    {
        return $this->sale_price && $this->sale_price < $this->price
            ? (int) $this->sale_price
            : (int) $this->price;
    }

    public function hasDiscount(): bool
    {
        return $this->sale_price && $this->sale_price < $this->price;
    }

    public function discountPercent(): int
    {
        if (! $this->hasDiscount()) {
            return 0;
        }

        return (int) round((1 - $this->sale_price / $this->price) * 100);
    }

    public function coverImage(): string
    {
        return $this->images[0] ?? '/images/placeholder.svg';
    }

    public function secondImage(): ?string
    {
        return $this->images[1] ?? null;
    }

    public function isConfigurable(): bool
    {
        return $this->type === self::TYPE_CONFIGURABLE;
    }

    public function inStock(): bool
    {
        if ($this->isConfigurable()) {
            return $this->variants->contains(fn (ProductVariant $v) => $v->is_active && $v->stock > 0);
        }

        return $this->stock > 0;
    }
}
