<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerGroup extends Model
{
    public const SLUG_GUEST = 'guest';

    public const SLUG_RETAIL = 'retail';

    public const SLUG_VIP = 'vip';

    protected $fillable = ['name', 'slug', 'description', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    protected static function booted(): void
    {
        static::creating(function (CustomerGroup $group) {
            if (blank($group->slug)) {
                $group->slug = \Illuminate\Support\Str::slug($group->name);
            }
        });
    }
}
