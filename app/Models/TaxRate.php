<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class TaxRate extends Model
{
    public const TYPE_EXCLUSIVE = 'exclusive';

    public const TYPE_INCLUSIVE = 'inclusive';

    protected $fillable = [
        'tax_class_id', 'name', 'rate_bp', 'type', 'province_id', 'city_id',
        'applies_to_shipping', 'priority', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'applies_to_shipping' => 'boolean',
        'rate_bp' => 'integer',
        'priority' => 'integer',
    ];

    public function taxClass(): BelongsTo
    {
        return $this->belongsTo(TaxClass::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderByDesc('priority');
    }

    /**
     * Cached active rates (plain arrays — never cache Eloquent).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function activeMap(): array
    {
        return Cache::rememberForever('tax.active_rates', function () {
            try {
                return static::query()->active()
                    ->get(['id', 'tax_class_id', 'name', 'rate_bp', 'type', 'province_id', 'city_id', 'applies_to_shipping', 'priority'])
                    ->map(fn (TaxRate $r) => [
                        'id' => $r->id,
                        'tax_class_id' => (int) $r->tax_class_id,
                        'name' => $r->name,
                        'rate_bp' => $r->rate_bp,
                        'type' => $r->type,
                        'province_id' => $r->province_id !== null ? (int) $r->province_id : null,
                        'city_id' => $r->city_id !== null ? (int) $r->city_id : null,
                        'applies_to_shipping' => (bool) $r->applies_to_shipping,
                        'priority' => $r->priority,
                    ])
                    ->all();
            } catch (\Throwable) {
                return [];
            }
        });
    }

    public static function flushCache(): void
    {
        Cache::forget('tax.active_rates');
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }
}
