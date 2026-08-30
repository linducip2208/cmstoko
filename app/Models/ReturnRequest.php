<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ReturnRequest extends Model
{
    public const STATUS_REQUESTED = 'requested';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_REQUESTED => 'Diajukan',
        self::STATUS_APPROVED => 'Disetujui',
        self::STATUS_REJECTED => 'Ditolak',
        self::STATUS_RECEIVED => 'Barang Diterima',
        self::STATUS_REFUNDED => 'Dana Dikembalikan',
        self::STATUS_CANCELLED => 'Dibatalkan',
    ];

    protected $fillable = [
        'return_number', 'order_id', 'user_id', 'status', 'reason', 'note',
    ];

    protected static function booted(): void
    {
        static::creating(function (ReturnRequest $request) {
            if (blank($request->return_number)) {
                do {
                    $number = 'RET/'.now()->format('Ymd').'/'.strtoupper(Str::random(6));
                } while (static::where('return_number', $number)->exists());
                $request->return_number = $number;
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class);
    }
}
