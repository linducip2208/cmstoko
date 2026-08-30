<?php

namespace App\Models;

use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use InvalidArgumentException;

class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_READY_TO_SHIP = 'ready_to_ship';

    public const STATUS_PARTIALLY_SHIPPED = 'partially_shipped';

    public const STATUS_SHIPPED = 'shipped';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_PARTIALLY_REFUNDED = 'partially_refunded';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUSES = [
        self::STATUS_PENDING => 'Menunggu Pembayaran',
        self::STATUS_PAID => 'Dibayar',
        self::STATUS_PROCESSING => 'Diproses',
        self::STATUS_READY_TO_SHIP => 'Siap Kirim',
        self::STATUS_PARTIALLY_SHIPPED => 'Sebagian Dikirim',
        self::STATUS_SHIPPED => 'Dikirim',
        self::STATUS_COMPLETED => 'Selesai',
        self::STATUS_CANCELLED => 'Dibatalkan',
        self::STATUS_PARTIALLY_REFUNDED => 'Sebagian Dikembalikan',
        self::STATUS_REFUNDED => 'Dana Dikembalikan',
    ];

    /** Statuses that count as revenue (money received). */
    public const PAID_STATUSES = [
        self::STATUS_PAID, self::STATUS_PROCESSING, self::STATUS_READY_TO_SHIP,
        self::STATUS_PARTIALLY_SHIPPED, self::STATUS_SHIPPED, self::STATUS_COMPLETED,
        self::STATUS_PARTIALLY_REFUNDED,
    ];

    public const TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_PAID, self::STATUS_CANCELLED],
        self::STATUS_PAID => [
            self::STATUS_PROCESSING, self::STATUS_READY_TO_SHIP, self::STATUS_SHIPPED,
            self::STATUS_PARTIALLY_SHIPPED, self::STATUS_PARTIALLY_REFUNDED, self::STATUS_REFUNDED,
            self::STATUS_CANCELLED,
        ],
        self::STATUS_PROCESSING => [
            self::STATUS_READY_TO_SHIP, self::STATUS_SHIPPED, self::STATUS_PARTIALLY_SHIPPED,
            self::STATUS_PARTIALLY_REFUNDED, self::STATUS_REFUNDED, self::STATUS_CANCELLED,
        ],
        self::STATUS_READY_TO_SHIP => [
            self::STATUS_SHIPPED, self::STATUS_PARTIALLY_SHIPPED, self::STATUS_PARTIALLY_REFUNDED,
            self::STATUS_REFUNDED, self::STATUS_CANCELLED,
        ],
        self::STATUS_PARTIALLY_SHIPPED => [
            self::STATUS_SHIPPED, self::STATUS_PARTIALLY_REFUNDED, self::STATUS_REFUNDED,
        ],
        self::STATUS_SHIPPED => [self::STATUS_COMPLETED, self::STATUS_PARTIALLY_REFUNDED, self::STATUS_REFUNDED],
        self::STATUS_COMPLETED => [self::STATUS_PARTIALLY_REFUNDED, self::STATUS_REFUNDED],
        self::STATUS_CANCELLED => [],
        self::STATUS_PARTIALLY_REFUNDED => [self::STATUS_REFUNDED],
        self::STATUS_REFUNDED => [],
    ];

    protected $fillable = [
        'order_number', 'user_id', 'customer_name', 'customer_email', 'customer_phone',
        'province_id', 'city_id', 'province_name', 'city_name', 'address', 'postal_code',
        'notes', 'subtotal', 'discount', 'shipping_cost', 'total', 'coupon_code', 'weight',
        'shipping_courier', 'shipping_service', 'shipping_etd', 'status',
        'payment_method', 'payment_type', 'transaction_id', 'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (blank($order->order_number)) {
                $order->order_number = static::generateNumber();
            }
        });
    }

    public static function generateNumber(): string
    {
        do {
            $number = 'INV-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (static::where('order_number', $number)->exists());

        return $number;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(OrderNote::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function returnRequests(): HasMany
    {
        return $this->hasMany(ReturnRequest::class);
    }

    /**
     * Amount refunded so far (processed refunds only).
     */
    public function refundedAmount(): int
    {
        return (int) $this->refunds()->where('status', Refund::STATUS_PROCESSED)->sum('amount');
    }

    public function refundableAmount(): int
    {
        return max(0, (int) $this->total - $this->refundedAmount());
    }

    /**
     * Shipped quantity per order item.
     */
    public function shippedQuantities(): array
    {
        return $this->shipments()->with('items')
            ->get()
            ->flatMap(fn (Shipment $shipment) => $shipment->items)
            ->groupBy('order_item_id')
            ->map(fn ($items) => (int) $items->sum('quantity'))
            ->all();
    }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function statusLabel(): string
    {
        return static::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canTransitionTo(string $to): bool
    {
        return in_array($to, static::TRANSITIONS[$this->status] ?? [], true);
    }

    /**
     * Guarded status transition with history recording.
     *
     * @throws InvalidArgumentException on invalid transition
     */
    public function transitionTo(string $to, ?string $note = null, ?int $userId = null, bool $force = false): static
    {
        if (! array_key_exists($to, static::STATUSES)) {
            throw new InvalidArgumentException("Unknown order status [{$to}].");
        }

        if (! $force && ! $this->canTransitionTo($to)) {
            throw new InvalidArgumentException(
                "Transisi status tidak valid dari [{$this->status}] ke [{$to}]."
            );
        }

        $from = $this->status;

        $this->forceFill([
            'status' => $to,
            'paid_at' => $to === self::STATUS_PAID ? ($this->paid_at ?? now()) : $this->paid_at,
        ])->save();

        if ($to === self::STATUS_CANCELLED) {
            $this->restock();
        }

        $this->histories()->create([
            'from' => $from,
            'to' => $to,
            'note' => $note,
            'user_id' => $userId,
        ]);

        return $this;
    }

    /**
     * Return reserved stock to inventory when an order is cancelled.
     */
    protected function restock(): void
    {
        $inventory = app(InventoryService::class);

        foreach ($this->items()->whereNotNull('product_id')->get() as $item) {
            try {
                $inventory->increase(
                    (int) $item->product_id,
                    $item->variant_id !== null ? (int) $item->variant_id : null,
                    (int) $item->quantity,
                    StockMovement::TYPE_SALE_CANCEL,
                    $this,
                    'Pembatalan pesanan '.$this->order_number,
                );
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    /**
     * Amount still owed for payment tracking purposes.
     */
    public function isPaid(): bool
    {
        return in_array($this->status, [
            self::STATUS_PAID, self::STATUS_PROCESSING, self::STATUS_READY_TO_SHIP,
            self::STATUS_PARTIALLY_SHIPPED, self::STATUS_SHIPPED, self::STATUS_COMPLETED,
            self::STATUS_PARTIALLY_REFUNDED,
        ], true);
    }

    /**
     * Whether this order can accept refunds.
     */
    public function isRefundable(): bool
    {
        return in_array($this->status, [
            self::STATUS_PAID, self::STATUS_PROCESSING, self::STATUS_READY_TO_SHIP,
            self::STATUS_PARTIALLY_SHIPPED, self::STATUS_SHIPPED, self::STATUS_COMPLETED,
            self::STATUS_PARTIALLY_REFUNDED,
        ], true) && $this->refundableAmount() > 0;
    }
}
