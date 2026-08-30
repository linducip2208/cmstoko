<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SETTLED = 'settlement';

    public const STATUS_EXPIRE = 'expire';

    public const STATUS_DENY = 'deny';

    public const STATUS_CANCEL = 'cancel';

    public const STATUS_CHALLENGE = 'challenge';

    protected $fillable = [
        'order_id', 'gateway', 'transaction_id', 'status', 'payment_type',
        'fraud_status', 'gross_amount', 'payload', 'signature', 'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public static function record(string $gateway, ?string $transactionId, string $status): ?static
    {
        if (! $transactionId) {
            return null;
        }

        return static::firstOrCreate(
            ['gateway' => $gateway, 'transaction_id' => $transactionId],
            ['status' => $status],
        );
    }
}
