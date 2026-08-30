<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class HomepageSection extends Model
{
    public const TYPES = [
        'hero' => 'Hero (Editorial Split)',
        'product_grid' => 'Produk (Grid)',
        'category_grid' => 'Kategori (Grid)',
        'rich_text' => 'Teks Kaya',
        'banner' => 'Banner (Split)',
        'trust_bar' => 'Bar Kepercayaan (pengaturan)',
        'newsletter' => 'Buletin (Newsletter)',
        'cta' => 'Seruan Aksi (CTA)',
    ];

    protected $fillable = [
        'type', 'title', 'subtitle', 'config', 'sort_order',
        'is_active', 'starts_at', 'ends_at',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->orderBy('sort_order');
    }

    public function config(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }
}
