<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Brand extends Model
{
    protected $fillable = [
        'name', 'slug', 'logo', 'cover', 'description', 'is_active', 'sort_order', 'seo',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'seo' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Brand $brand) {
            if (blank($brand->slug)) {
                $brand->slug = static::uniqueSlug($brand->name);
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

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
