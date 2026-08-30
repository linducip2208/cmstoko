<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Menu extends Model
{
    public const LOCATION_HEADER = 'header';

    public const LOCATION_FOOTER = 'footer';

    protected $fillable = ['name', 'slug', 'location', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')]);
    }

    /**
     * Cached, fully-loaded active menu for a storefront location.
     */
    public static function activeAt(string $location): ?static
    {
        return Cache::rememberForever("menus.{$location}", function () use ($location) {
            return static::query()
                ->where('location', $location)
                ->where('is_active', true)
                ->with('items')
                ->first();
        });
    }

    public static function flushCache(): void
    {
        Cache::forget('menus.'.self::LOCATION_HEADER);
        Cache::forget('menus.'.self::LOCATION_FOOTER);
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }
}
