<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Attribute extends Model
{
    public const TYPE_SELECT = 'select';

    public const TYPE_COLOR = 'color';

    public const TYPE_TEXT = 'text';

    public const TYPE_NUMBER = 'number';

    protected $fillable = ['name', 'slug', 'type', 'is_variant', 'is_required', 'position'];

    protected $casts = [
        'is_variant' => 'boolean',
        'is_required' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Attribute $attribute) {
            if (blank($attribute->slug)) {
                $attribute->slug = static::uniqueSlug($attribute->name);
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

    public function options(): HasMany
    {
        return $this->hasMany(AttributeOption::class)->orderBy('position');
    }
}
