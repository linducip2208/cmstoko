<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    public const TYPE_OPENING = 'opening';

    public const TYPE_PURCHASE = 'purchase';

    public const TYPE_SALE = 'sale';

    public const TYPE_SALE_CANCEL = 'sale_cancel';

    public const TYPE_RETURN = 'return';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPE_TRANSFER_IN = 'transfer_in';

    public const TYPE_TRANSFER_OUT = 'transfer_out';

    public const TYPES = [
        self::TYPE_OPENING => 'Stok Awal',
        self::TYPE_PURCHASE => 'Pembelian Stok',
        self::TYPE_SALE => 'Penjualan',
        self::TYPE_SALE_CANCEL => 'Pembatalan Penjualan',
        self::TYPE_RETURN => 'Retur',
        self::TYPE_ADJUSTMENT => 'Penyesuaian',
        self::TYPE_TRANSFER_IN => 'Transfer Masuk',
        self::TYPE_TRANSFER_OUT => 'Transfer Keluar',
    ];

    protected $fillable = [
        'warehouse_id', 'product_id', 'variant_id', 'type', 'quantity',
        'stock_before', 'stock_after', 'reference_type', 'reference_id', 'note', 'user_id',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
