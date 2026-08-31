<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLevel extends Model
{
    protected $fillable = ['warehouse_id', 'product_id', 'variant_id', 'stock', 'reserved'];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Get-or-create the level row for a product/variant/warehouse triple.
     */
    public static function findOrCreate(int $warehouseId, int $productId, ?int $variantId): self
    {
        return static::query()
            ->firstOrCreate([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'variant_id' => $variantId,
            ], ['stock' => 0, 'reserved' => 0]);
    }
}
