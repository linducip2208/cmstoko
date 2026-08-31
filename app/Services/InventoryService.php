<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    /**
     * Atomically deduct stock for a set of order lines.
     * Uses row locks + conditional update; throws on any shortfall (rolls back).
     *
     * @param  iterable<array{product_id: int, variant_id?: ?int, quantity: int, price: int, name: string, image?: ?string}>  $lines
     */
    public function deductForOrder(iterable $lines, Order $order): void
    {
        DB::transaction(function () use ($lines, $order) {
            foreach ($lines as $line) {
                $this->deduct(
                    productId: (int) $line['product_id'],
                    variantId: isset($line['variant_id']) ? (int) $line['variant_id'] : null,
                    quantity: (int) $line['quantity'],
                    type: StockMovement::TYPE_SALE,
                    reference: $order,
                    note: 'Pesanan '.$order->order_number,
                );
            }
        });
    }

    public function deduct(int $productId, ?int $variantId, int $quantity, string $type, $reference = null, ?string $note = null): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Kuantitas stok harus positif.');
        }

        DB::transaction(function () use ($productId, $variantId, $quantity, $type, $reference, $note) {
            $target = $this->lockTarget($productId, $variantId);

            if ($target->stock < $quantity) {
                $label = $target instanceof ProductVariant ? 'Varian '.$target->sku : $target->name;

                throw new InvalidArgumentException("Stok {$label} tidak mencukupi (tersedia {$target->stock}).");
            }

            $before = (int) $target->stock;

            $affected = $this->baseQuery($productId, $variantId)
                ->where('stock', '>=', $quantity)
                ->decrement('stock', $quantity);

            if ($affected === 0) {
                throw new InvalidArgumentException('Stok berubah saat proses. Ulangi operasi.');
            }

            $this->record($productId, $variantId, $type, -$quantity, $before, $before - $quantity, $reference, $note);
        });
    }

    public function increase(int $productId, ?int $variantId, int $quantity, string $type, $reference = null, ?string $note = null): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Kuantitas stok harus positif.');
        }

        DB::transaction(function () use ($productId, $variantId, $quantity, $type, $reference, $note) {
            $target = $this->lockTarget($productId, $variantId);
            $before = (int) $target->stock;

            $this->baseQuery($productId, $variantId)->increment('stock', $quantity);

            $this->record($productId, $variantId, $type, $quantity, $before, $before + $quantity, $reference, $note);
        });
    }

    public function adjust(int $productId, ?int $variantId, int $delta, ?string $note = null, ?int $userId = null): void
    {
        if ($delta === 0) {
            return;
        }

        DB::transaction(function () use ($productId, $variantId, $delta, $note, $userId) {
            $target = $this->lockTarget($productId, $variantId);
            $before = (int) $target->stock;
            $after = $before + $delta;

            if ($after < 0) {
                throw new InvalidArgumentException("Penyesuaian menghasilkan stok negatif ({$after}).");
            }

            $this->baseQuery($productId, $variantId)->update(['stock' => $after]);

            $this->record($productId, $variantId, StockMovement::TYPE_ADJUSTMENT, $delta, $before, $after, null, $note, $userId);

            \App\Support\Audit::record(
                'inventory.adjust',
                subject: $target,
                before: ['stock' => $before],
                after: ['stock' => $after, 'delta' => $delta, 'note' => $note],
            );
        });
    }

    public function history(int $productId, ?int $variantId = null, int $limit = 50)
    {
        return StockMovement::where('product_id', $productId)
            ->when($variantId !== null, fn ($q) => $q->where('variant_id', $variantId))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    // ================================================================
    // Multi-warehouse operations
    // ================================================================

    /**
     * Default (primary) warehouse id.
     */
    public function defaultWarehouseId(): int
    {
        return (int) (Warehouse::where('is_default', true)->value('id')
            ?? Warehouse::query()->min('id'));
    }

    /**
     * Stock in a specific warehouse (creates the level row lazily).
     */
    public function level(int $productId, ?int $variantId, int $warehouseId): int
    {
        return (int) \App\Models\InventoryLevel::findOrCreate($warehouseId, $productId, $variantId)->stock;
    }

    /**
     * Adjust a single warehouse level. Flat product stock stays the
     * authoritative TOTAL and is mirrored atomically.
     */
    public function adjustWarehouse(int $productId, ?int $variantId, int $warehouseId, int $delta, ?string $note = null, ?int $userId = null): void
    {
        if ($delta === 0) {
            return;
        }

        DB::transaction(function () use ($productId, $variantId, $warehouseId, $delta, $note, $userId) {
            $level = \App\Models\InventoryLevel::findOrCreate($warehouseId, $productId, $variantId);

            $level = \App\Models\InventoryLevel::whereKey($level->id)->lockForUpdate()->first();
            $before = (int) $level->stock;
            $after = $before + $delta;

            if ($after < 0) {
                throw new InvalidArgumentException("Stok gudang tidak cukup (tersedia {$before}).");
            }

            $level->update(['stock' => $after]);

            $this->record($productId, $variantId, StockMovement::TYPE_ADJUSTMENT, $delta, $before, $after, null, $note, $userId, $warehouseId);

            \App\Support\Audit::record('inventory.adjust', subject: $level, before: ['stock' => $before], after: ['stock' => $after, 'delta' => $delta, 'warehouse_id' => $warehouseId]);
        });
    }

    /**
     * Allocate order lines across warehouses: default (or given priority)
     * first, then any other active warehouse. Splits across warehouses when
     * needed. Flat product stock (the total) is the lock surface used by
     * deductForOrder; here we mirror the per-warehouse distribution.
     *
     * @param  iterable<array{product_id: int, variant_id?: ?int, quantity: int}>  $lines
     */
    public function allocate(iterable $lines): array
    {
        return DB::transaction(function () use ($lines) {
            $allocations = [];

            foreach ($lines as $line) {
                $remaining = (int) $line['quantity'];
                $productId = (int) $line['product_id'];
                $variantId = isset($line['variant_id']) ? (int) $line['variant_id'] : null;

                $warehouses = Warehouse::where('is_active', true)
                    ->orderByDesc('is_default')
                    ->orderBy('id')
                    ->get();

                foreach ($warehouses as $warehouse) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $level = \App\Models\InventoryLevel::findOrCreate($warehouse->id, $productId, $variantId);
                    $level = \App\Models\InventoryLevel::query()->whereKey($level->id)->lockForUpdate()->first();

                    $take = min($remaining, (int) $level->stock);

                    if ($take <= 0) {
                        continue;
                    }

                    $before = (int) $level->stock;
                    $level->update(['stock' => $before - $take]);
                    $remaining -= $take;

                    $this->record($productId, $variantId, StockMovement::TYPE_SALE, -$take, $before, $before - $take, null, 'Alokasi gudang '.$warehouse->name, null, $warehouse->id);

                    $allocations[] = [
                        'warehouse_id' => $warehouse->id,
                        'product_id' => $productId,
                        'variant_id' => $variantId,
                        'quantity' => $take,
                    ];
                }

                if ($remaining > 0) {
                    throw new InvalidArgumentException("Stok gudang tidak mencukupi untuk {$remaining} unit.");
                }
            }

            return $allocations;
        });
    }

    /**
     * Execute a stock transfer. `in_transit` deducts the source warehouse;
     * `receive` adds the destination. Flat total stock never changes during
     * a transfer — goods only move between locations.
     */
    public function shipTransfer(\App\Models\StockTransfer $transfer, ?int $userId = null): void
    {
        DB::transaction(function () use ($transfer, $userId) {
            $transfer = \App\Models\StockTransfer::whereKey($transfer->id)->lockForUpdate()->first();

            if ($transfer->status !== \App\Models\StockTransfer::STATUS_PENDING) {
                throw new InvalidArgumentException('Transfer hanya bisa dikirim dari status Menunggu.');
            }

            foreach ($transfer->items()->with('product')->get() as $item) {
                $level = \App\Models\InventoryLevel::findOrCreate(
                    $transfer->from_warehouse_id,
                    (int) $item->product_id,
                    $item->variant_id !== null ? (int) $item->variant_id : null,
                );
                $level = \App\Models\InventoryLevel::query()->whereKey($level->id)->lockForUpdate()->first();

                $before = (int) $level->stock;

                if ($before < (int) $item->quantity) {
                    throw new InvalidArgumentException("Stok gudang asal tidak cukup untuk {$item->product->name} (tersedia {$before}).");
                }

                $level->update(['stock' => $before - (int) $item->quantity]);

                $this->record(
                    (int) $item->product_id,
                    $item->variant_id !== null ? (int) $item->variant_id : null,
                    StockMovement::TYPE_TRANSFER_OUT,
                    -(int) $item->quantity,
                    $before,
                    $before - (int) $item->quantity,
                    $transfer,
                    'Transfer keluar '.$transfer->transfer_number,
                    $userId,
                    $transfer->from_warehouse_id,
                );
            }

            $transfer->update([
                'status' => \App\Models\StockTransfer::STATUS_IN_TRANSIT,
                'shipped_at' => now(),
            ]);
        });
    }

    public function receiveTransfer(\App\Models\StockTransfer $transfer, ?int $userId = null): void
    {
        DB::transaction(function () use ($transfer, $userId) {
            $transfer = \App\Models\StockTransfer::whereKey($transfer->id)->lockForUpdate()->first();

            if ($transfer->status !== \App\Models\StockTransfer::STATUS_IN_TRANSIT) {
                throw new InvalidArgumentException('Transfer harus berstatus Dalam Perjalanan untuk diterima.');
            }

            foreach ($transfer->items()->with('product')->get() as $item) {
                $level = \App\Models\InventoryLevel::findOrCreate(
                    $transfer->to_warehouse_id,
                    (int) $item->product_id,
                    $item->variant_id !== null ? (int) $item->variant_id : null,
                );
                $level = \App\Models\InventoryLevel::query()->whereKey($level->id)->lockForUpdate()->first();

                $before = (int) $level->stock;
                $level->update(['stock' => $before + (int) $item->quantity]);

                $this->record(
                    (int) $item->product_id,
                    $item->variant_id !== null ? (int) $item->variant_id : null,
                    StockMovement::TYPE_TRANSFER_IN,
                    (int) $item->quantity,
                    $before,
                    $before + (int) $item->quantity,
                    $transfer,
                    'Transfer masuk '.$transfer->transfer_number,
                    $userId,
                    $transfer->to_warehouse_id,
                );
            }

            $transfer->update([
                'status' => \App\Models\StockTransfer::STATUS_RECEIVED,
                'received_at' => now(),
            ]);
        });
    }

    public function cancelTransfer(\App\Models\StockTransfer $transfer, ?string $reason = null, ?int $userId = null): void
    {
        DB::transaction(function () use ($transfer, $reason, $userId) {
            $transfer = \App\Models\StockTransfer::whereKey($transfer->id)->lockForUpdate()->first();

            if ($transfer->status !== \App\Models\StockTransfer::STATUS_PENDING) {
                throw new InvalidArgumentException('Hanya transfer Menunggu yang bisa dibatalkan.');
            }

            $transfer->update([
                'status' => \App\Models\StockTransfer::STATUS_CANCELLED,
                'note' => trim(($transfer->note ? $transfer->note.' — ' : '').($reason ?? 'Dibatalkan')),
            ]);
        });
    }

    protected function lockTarget(int $productId, ?int $variantId): Product|ProductVariant
    {
        if ($variantId !== null) {
            $variant = ProductVariant::where('id', $variantId)->where('product_id', $productId)->lockForUpdate()->first();

            if (! $variant) {
                throw new InvalidArgumentException('Varian tidak ditemukan.');
            }

            return $variant;
        }

        $product = Product::whereKey($productId)->lockForUpdate()->first();

        if (! $product) {
            throw new InvalidArgumentException('Produk tidak ditemukan.');
        }

        return $product;
    }

    protected function baseQuery(int $productId, ?int $variantId)
    {
        if ($variantId !== null) {
            return ProductVariant::whereKey($variantId);
        }

        return Product::whereKey($productId);
    }

    protected function record(int $productId, ?int $variantId, string $type, int $delta, int $before, int $after, $reference = null, ?string $note = null, ?int $userId = null, ?int $warehouseId = null): void
    {
        $warehouseId = $warehouseId ?? Warehouse::where('is_default', true)->value('id');

        StockMovement::create([
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
            'variant_id' => $variantId,
            'type' => $type,
            'quantity' => $delta,
            'stock_before' => $before,
            'stock_after' => $after,
            'reference_type' => $reference ? $reference::class : null,
            'reference_id' => $reference?->id,
            'note' => $note,
            'user_id' => $userId ?? auth()->id(),
        ]);
    }
}
