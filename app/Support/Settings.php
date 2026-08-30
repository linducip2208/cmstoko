<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Settings
{
    public const CACHE_KEY = 'shop.settings';

    /** @var array<string, mixed>|null */
    protected static ?array $bag = null;

    public static function get(string $key, mixed $default = null): mixed
    {
        $bag = static::bag();

        return array_key_exists($key, $bag) ? $bag[$key] : $default;
    }

    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => $key],
            ['value' => json_encode($value), 'group' => $group, 'updated_at' => now()],
        );

        static::flush();
    }

    public static function bag(): array
    {
        if (static::$bag !== null) {
            return static::$bag;
        }

        static::$bag = Cache::rememberForever(static::CACHE_KEY, function () {
            try {
                return DB::table('settings')
                    ->pluck('value', 'key')
                    ->map(fn ($value) => json_decode((string) $value, true))
                    ->all();
            } catch (\Throwable) {
                return [];
            }
        });

        return static::$bag;
    }

    public static function flush(): void
    {
        static::$bag = null;
        Cache::forget(static::CACHE_KEY);
    }
}
