<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SeoRedirect extends Model
{
    protected $fillable = [
        'source', 'destination', 'status_code', 'is_active', 'hit_count', 'last_hit_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'status_code' => 'integer',
        'hit_count' => 'integer',
        'last_hit_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => static::flushLookupCache());
        static::deleted(fn () => static::flushLookupCache());
    }

    public static function flushLookupCache(): void
    {
        Cache::forget(static::CACHE_KEY);
    }

    public const CACHE_KEY = 'seo.redirects';

    /**
     * Cached map of active redirects: normalized source => [destination, status_code].
     *
     * @return array<string, array{0: string, 1: int}>
     */
    public static function lookupTable(): array
    {
        return Cache::rememberForever(static::CACHE_KEY, function () {
            try {
                return static::query()
                    ->where('is_active', true)
                    ->get(['source', 'destination', 'status_code'])
                    ->mapWithKeys(fn (SeoRedirect $redirect) => [
                        static::normalizePath($redirect->source) => [
                            $redirect->destination,
                            $redirect->status_code === 302 ? 302 : 301,
                            $redirect->source, // raw source for hit bookkeeping
                        ],
                    ])
                    ->all();
            } catch (\Throwable) {
                return [];
            }
        });
    }

    public static function normalizePath(string $path): string
    {
        $path = '/'.ltrim(trim($path), '/');
        $path = rtrim($path, '/');

        return $path === '' ? '/' : $path;
    }

    public static function match(string $path): ?array
    {
        $table = static::lookupTable();
        $normalized = static::normalizePath($path);

        if (! isset($table[$normalized])) {
            return null;
        }

        [$destination, $statusCode, $rawSource] = $table[$normalized];

        return [
            'source' => $rawSource,
            'destination' => $destination,
            'status_code' => $statusCode,
        ];
    }
}
