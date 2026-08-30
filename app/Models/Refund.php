<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Refund extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSED = 'processed';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'refund_number', 'order_id', 'amount', 'reason', 'status', 'user_id', 'processed_at',
    ];

    protected $casts = ['processed_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(function (Refund $refund) {
            if (blank($refund->refund_number)) {
                do {
                    $number = 'RFD/'.now()->format('Ymd').'/'.strtoupper(Str::random(6));
                } while (static::where('refund_number', $number)->exists());
                $refund->refund_number = $number;
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RefundItem::class);
    }
}
