<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    public const TARGET_CUSTOM = 'custom';

    public const TARGET_CATEGORY = 'category';

    public const TARGET_BRAND = 'brand';

    public const TARGET_PAGE = 'page';

    protected $fillable = [
        'menu_id', 'parent_id', 'label', 'target_type', 'target_id',
        'url', 'open_in_new', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'open_in_new' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Resolve the final href from the target type. Unknown/removed targets
     * resolve to null and the item is skipped in rendering.
     */
    public function resolvedUrl(): ?string
    {
        if ($this->target_type === self::TARGET_CUSTOM) {
            $url = trim((string) $this->url);

            if ($url === '') {
                return null;
            }

            // Only same-host absolute URLs or internal paths are allowed.
            if (str_starts_with($url, '/')) {
                return $url;
            }

            if (preg_match('#^https?://#i', $url)) {
                $host = parse_url($url, PHP_URL_HOST);
                $appHost = parse_url(config('app.url') ?: url('/'), PHP_URL_HOST);

                if ($host && $appHost && strcasecmp($host, $appHost) === 0) {
                    return $url;
                }

                return null;
            }

            // Bare path without leading slash.
            return '/'.$url;
        }

        return match ($this->target_type) {
            self::TARGET_CATEGORY => $this->categoryRoute(),
            self::TARGET_BRAND => $this->brandRoute(),
            self::TARGET_PAGE => $this->pageRoute(),
            default => null,
        };
    }

    protected function categoryRoute(): ?string
    {
        $category = Category::find($this->target_id);

        return $category?->is_active ? route('shop', ['category' => $category->slug]) : null;
    }

    protected function brandRoute(): ?string
    {
        $brand = Brand::find($this->target_id);

        return $brand?->is_active ? route('shop', ['brand' => $brand->slug]) : null;
    }

    protected function pageRoute(): ?string
    {
        $page = CmsPage::find($this->target_id);

        return $page && $page->status === CmsPage::STATUS_PUBLISHED
            ? route('pages.show', $page->slug)
            : null;
    }
}
