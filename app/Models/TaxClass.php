<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TaxClass extends Model
{
    protected $fillable = ['name', 'slug', 'is_default'];

    protected $casts = ['is_default' => 'boolean'];

    public function rates()
    {
        return $this->hasMany(TaxRate::class);
    }

    protected static function booted(): void
    {
        static::creating(function (TaxClass $class) {
            if (blank($class->slug)) {
                $class->slug = Str::slug($class->name);
            }
        });

        // Data integrity: only ONE default class may exist.
        static::saving(function (TaxClass $class) {
            if ($class->is_default) {
                static::query()->where('is_default', true)
                    ->when($class->exists, fn ($q) => $q->whereKeyNot($class->id))
                    ->update(['is_default' => false]);
            }
        });
    }
}
