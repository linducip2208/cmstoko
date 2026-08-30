<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Refund;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderFulfillmentService
{
    public function __construct(
        protected InventoryService $inventory,
    ) {}

    public function createInvoice(Order $order): Invoice
    {
        if ($order->invoices()->where('status', '!=', Invoice::STATUS_CANCELLED)->exists()) {
            throw new InvalidArgumentException('Invoice sudah ada untuk pesanan ini.');
        }

        return $order->invoices()->create([
            'amount' => $order->total,
            'status' => Invoice::STATUS_ISSUED,
            'due_at' => now()->addDays(2),
        ]);
    }

    /**
     * Ship (part of) an order. Quantities default to the remaining unshipped amounts.
     *
     * @param  array<int, array{order_item_id: int, quantity: int}>  $lines
     */
    public function ship(Order $order, array $lines, string $courier, string $service, ?string $trackingNumber = null, int $cost = 0): Shipment
    {
        return DB::transaction(function () use ($order, $lines, $courier, $service, $trackingNumber, $cost) {
            $order = Order::whereKey($order->id)->lockForUpdate()->first();

            if (! in_array($order->status, [
                Order::STATUS_PAID, Order::STATUS_PROCESSING, Order::STATUS_READY_TO_SHIP,
                Order::STATUS_PARTIALLY_SHIPPED,
            ], true)) {
                throw new InvalidArgumentException('Pesanan dalam status '.$order->statusLabel().' tidak dapat dikirim.');
            }

            $shipped = $order->shippedQuantities();
            $validLines = [];

            foreach ($lines as $line) {
                $quantity = (int) ($line['quantity'] ?? 0);

                if ($quantity <= 0) {
                    continue;
                }

                $item = OrderItem::whereKey($line['order_item_id'])->where('order_id', $order->id)->first();

                if (! $item) {
                    continue;
                }

                $alreadyShipped = $shipped[$item->id] ?? 0;
                $remaining = $item->quantity - $alreadyShipped;

                if ($quantity > $remaining) {
                    throw new InvalidArgumentException(
                        "Jumlah kirim untuk {$item->product_name} melebihi sisa ({$remaining})."
                    );
                }

                $validLines[] = ['item' => $item, 'quantity' => $quantity];
            }

            if ($validLines === []) {
                throw new InvalidArgumentException('Tidak ada item untuk dikirim.');
            }

            $shipment = $order->shipments()->create([
                'courier' => $courier,
                'service' => $service,
                'tracking_number' => $trackingNumber,
                'status' => Shipment::STATUS_SHIPPED,
                'cost' => $cost,
                'shipped_at' => now(),
            ]);

            foreach ($validLines as ['item' => $item, 'quantity' => $quantity]) {
                ShipmentItem::create([
                    'shipment_id' => $shipment->id,
                    'order_item_id' => $item->id,
                    'quantity' => $quantity,
                ]);
            }

            // Determine full vs partial shipment.
            $fresh = $order->fresh();
            $totals = $fresh->items()->sum('quantity');
            $nowShipped = array_sum($fresh->shippedQuantities());

            $newStatus = $nowShipped >= $totals ? Order::STATUS_SHIPPED : Order::STATUS_PARTIALLY_SHIPPED;

            if ($fresh->status !== $newStatus) {
                $fresh->transitionTo($newStatus, 'Pengiriman dibuat: '.$shipment->shipment_number);
            }

            return $shipment;
        });
    }

    /**
     * Process a refund. Caps at refundable amount, records ledger rows.
     */
    public function refund(Order $order, int $amount, ?string $reason = null, ?array $lines = null): Refund
    {
        return DB::transaction(function () use ($order, $amount, $reason, $lines) {
            $order = Order::whereKey($order->id)->lockForUpdate()->first();

            if (! $order->isRefundable()) {
                throw new InvalidArgumentException('Pesanan tidak dapat dikembalikan dananya.');
            }

            if ($amount <= 0) {
                throw new InvalidArgumentException('Jumlah pengembalian harus positif.');
            }

            if ($amount > $order->refundableAmount()) {
                throw new InvalidArgumentException(
                    'Jumlah pengembalian melebihi sisa dana ('.$order->refundableAmount().').'
                );
            }

            $refund = $order->refunds()->create([
                'amount' => $amount,
                'reason' => $reason,
                'status' => Refund::STATUS_PROCESSED,
                'user_id' => auth()->id(),
                'processed_at' => now(),
            ]);

            foreach ((array) $lines as $line) {
                $item = OrderItem::whereKey($line['order_item_id'] ?? 0)->where('order_id', $order->id)->first();

                if ($item) {
                    $refund->items()->create([
                        'order_item_id' => $item->id,
                        'quantity' => (int) ($line['quantity'] ?? 0),
                        'amount' => (int) ($line['amount'] ?? 0),
                    ]);
                }
            }

            $fresh = $order->fresh();
            $remaining = $fresh->refundableAmount();

            $newStatus = $remaining <= 0
                ? Order::STATUS_REFUNDED
                : Order::STATUS_PARTIALLY_REFUNDED;

            if ($fresh->canTransitionTo($newStatus)) {
                $fresh->transitionTo($newStatus, 'Refund '.$refund->refund_number);
            }

            return $refund;
        });
    }
}
