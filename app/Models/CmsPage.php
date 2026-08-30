<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CmsPage extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_SCHEDULED => 'Terjadwal',
        self::STATUS_PUBLISHED => 'Terbit',
        self::STATUS_ARCHIVED => 'Arsip',
    ];

    protected $fillable = [
        'title', 'slug', 'excerpt', 'content', 'featured_image', 'template',
        'status', 'published_at', 'user_id', 'seo',
    ];

    protected $casts = [
        'seo' => 'array',
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (CmsPage $page) {
            if (blank($page->slug)) {
                $page->slug = static::uniqueSlug($page->title);
            }
        });
    }

    public static function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $base.'-'.++$i;
        }

        return $slug;
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    /**
     * Render the content safely: purify basic scripts/styles, allow controlled HTML.
     */
    public function renderableContent(): string
    {
        $content = (string) $this->content;

        $content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $content);
        $content = preg_replace('/\son\w+="[^"]*"/i', '', $content);
        $content = preg_replace('/\son\w+=\S+/i', '', $content);

        return $content;
    }
}
