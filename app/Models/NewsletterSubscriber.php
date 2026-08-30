<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsletterSubscriber extends Model
{
    protected $fillable = [
        'email', 'token', 'source', 'subscribed_at', 'unsubscribed_at',
    ];

    protected $casts = [
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (NewsletterSubscriber $subscriber) {
            $subscriber->token ??= Str::random(32);
            $subscriber->subscribed_at ??= now();
        });
    }

    public function scopeSubscribed($query)
    {
        return $query->whereNull('unsubscribed_at');
    }

    public function scopeUnsubscribed($query)
    {
        return $query->whereNotNull('unsubscribed_at');
    }
}
