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

    protected function record(int $productId, ?int $variantId, string $type, int $delta, int $before, int $after, $reference = null, ?string $note = null, ?int $userId = null): void
    {
        $warehouseId = Warehouse::where('is_default', true)->value('id');

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
