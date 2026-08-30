<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    protected $fillable = ['name', 'role_company', 'avatar', 'quote', 'rating', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Ratings are optional and only displayed when the merchant filled one in.
     */
    public function hasRating(): bool
    {
        return $this->rating !== null && $this->rating >= 1 && $this->rating <= 5;
    }
}
