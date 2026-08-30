<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Theme
{
    public const CACHE_KEY = 'shop.theme.vars';

    /**
     * Resolved CSS variable set: active preset overridden by store-level custom values.
     * Never cached long — must reflect activation immediately (flushed on save).
     *
     * @return array<string, string>
     */
    public static function vars(): array
    {
        return Cache::rememberForever(static::CACHE_KEY, function () {
            $presetKey = Settings::get('theme.preset', config('theme-presets.default', 'editorial'));

            $presets = (array) config('theme-presets.presets', []);

            $vars = $presets[$presetKey]['vars']
                ?? $presets[config('theme-presets.default', 'editorial')]['vars']
                ?? [];

            $custom = (array) (Settings::get('theme.custom') ?? []);

            foreach ($custom as $key => $value) {
                if (is_string($value) && Str::startsWith($key, '--') && $value !== '') {
                    $vars[$key] = $value;
                }
            }

            return $vars;
        });
    }

    public static function flush(): void
    {
        Cache::forget(static::CACHE_KEY);
    }

    /**
     * @return array<string, array{label: string, description: string, vars: array<string, string>}>
     */
    public static function presets(): array
    {
        return (array) config('theme-presets.presets', []);
    }

    public static function activePreset(): string
    {
        return Settings::get('theme.preset', config('theme-presets.default', 'editorial'));
    }
}
