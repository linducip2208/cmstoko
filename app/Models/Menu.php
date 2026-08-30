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
     * Cached, fully-resolved active menu for a storefront location.
     * Plain arrays only (unserialize-safety); URLs pre-resolved.
     */
    public static function activeAt(string $location): ?array
    {
        return Cache::rememberForever("menus.{$location}", function () use ($location) {
            $menu = static::query()
                ->where('location', $location)
                ->where('is_active', true)
                ->first();

            if (! $menu) {
                return null;
            }

            return $menu->items()
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->map(fn (MenuItem $item) => [
                    'label' => $item->label,
                    'url' => $item->resolvedUrl(),
                    'open_in_new' => $item->open_in_new,
                    'children' => $item->children()
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->get()
                        ->map(fn (MenuItem $child) => [
                            'label' => $child->label,
                            'url' => $child->resolvedUrl(),
                            'open_in_new' => $child->open_in_new,
                        ])
                        ->values()
                        ->all(),
                ])
                ->filter(fn (array $item) => $item['url'] !== null)
                ->values()
                ->all();
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
