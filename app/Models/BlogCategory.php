<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BlogCategory extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'sort_order'];

    public function posts()
    {
        return $this->hasMany(BlogPost::class);
    }

    protected static function booted(): void
    {
        static::creating(function (BlogCategory $category) {
            if (blank($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }
}
