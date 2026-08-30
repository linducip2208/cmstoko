<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    public const TYPE_FIXED = 'fixed';

    public const TYPE_PERCENT = 'percent';

    protected $fillable = [
        'code', 'type', 'value', 'min_purchase', 'max_uses', 'used_count',
        'starts_at', 'expires_at', 'is_active',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function discountFor(int $subtotal): int
    {
        if ($subtotal < (int) $this->min_purchase) {
            return 0;
        }

        $discount = $this->type === self::TYPE_PERCENT
            ? (int) round($subtotal * $this->value / 100)
            : (int) $this->value;

        return min($discount, $subtotal);
    }

    public function isUsable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->expires_at && $now->gt($this->expires_at)) {
            return false;
        }

        if ($this->max_uses && $this->used_count >= $this->max_uses) {
            return false;
        }

        return true;
    }
}
