<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Refund;
use App\Models\Shipment;
use App\Services\OrderFulfillmentService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FulfillmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingsSeeder::class);
    }

    protected function paidOrder(int $qty = 2, int $price = 100000): Order
    {
        $product = Product::create([
            'category_id' => Category::create(['name' => 'F Cat '.uniqid()])->id,
            'name' => 'Fulfillment Product '.uniqid(),
            'price' => $price,
            'stock' => 50,
            'weight' => 400,
            'is_active' => true,
        ]);

        $order = Order::create([
            'customer_name' => 'F Tester',
            'customer_email' => 'f@example.com',
            'customer_phone' => '081200000001',
            'address' => 'Jl. F 1',
            'subtotal' => $price * $qty,
            'discount' => 0,
            'shipping_cost' => 20000,
            'total' => $price * $qty + 20000,
            'status' => Order::STATUS_PENDING,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => $price,
            'quantity' => $qty,
            'subtotal' => $price * $qty,
        ]);

        $order->transitionTo(Order::STATUS_PAID, 'Pembayaran masuk');

        return $order;
    }

    public function test_invoice_created_once(): void
    {
        $order = $this->paidOrder();
        $service = app(OrderFulfillmentService::class);

        $invoice = $service->createInvoice($order);
        $this->assertSame($order->total, $invoice->amount);
        $this->assertStringStartsWith('INV/', $invoice->invoice_number);

        $this->expectException(\InvalidArgumentException::class);
        $service->createInvoice($order);
    }

    public function test_ship_marks_order_shipped(): void
    {
        $order = $this->paidOrder(2);
        $item = $order->items()->first();
        $service = app(OrderFulfillmentService::class);

        $shipment = $service->ship($order, [
            ['order_item_id' => $item->id, 'quantity' => 2],
        ], 'jne', 'REG', 'JNE123456');

        $this->assertSame(Shipment::STATUS_SHIPPED, $shipment->status);
        $this->assertStringStartsWith('SHP/', $shipment->shipment_number);
        $order->refresh();
        $this->assertSame(Order::STATUS_SHIPPED, $order->status);

        // Ship beyond ordered quantity is rejected.
        $this->expectException(\InvalidArgumentException::class);
        $service->ship($order->fresh(), [
            ['order_item_id' => $item->id, 'quantity' => 1],
        ], 'jne', 'REG');
    }

    public function test_partial_shipment_sets_partial_status(): void
    {
        $order = $this->paidOrder(4);
        $item = $order->items()->first();
        $service = app(OrderFulfillmentService::class);

        $service->ship($order, [
            ['order_item_id' => $item->id, 'quantity' => 2],
        ], 'jne', 'REG');

        $order->refresh();
        $this->assertSame(Order::STATUS_PARTIALLY_SHIPPED, $order->status);

        // Second shipment completes it.
        $service->ship($order->fresh(), [
            ['order_item_id' => $item->id, 'quantity' => 2],
        ], 'jne', 'REG');

        $order->refresh();
        $this->assertSame(Order::STATUS_SHIPPED, $order->status);
    }

    public function test_refund_caps_at_refundable_amount(): void
    {
        $order = $this->paidOrder(2, 150000); // total = 320000
        $service = app(OrderFulfillmentService::class);

        $refund = $service->refund($order, 100000, 'Barang rusak sebagian');
        $this->assertSame(Refund::STATUS_PROCESSED, $refund->status);

        $order->refresh();
        $this->assertSame(Order::STATUS_PARTIALLY_REFUNDED, $order->status);
        $this->assertSame(220000, $order->refundableAmount());

        // Exceeding cap throws.
        $this->expectException(\InvalidArgumentException::class);
        $service->refund($order->fresh(), 999999999, 'Kepentingan sendiri');
    }

    public function test_full_refund_transitions_to_refunded(): void
    {
        $order = $this->paidOrder(1, 100000);
        $service = app(OrderFulfillmentService::class);

        $service->refund($order, $order->total, 'Cancel');

        $order->refresh();
        $this->assertSame(Order::STATUS_REFUNDED, $order->status);
        $this->assertSame(0, $order->refundableAmount());
    }

    public function test_unpaid_order_cannot_be_shipped_or_refunded(): void
    {
        $order = Order::create([
            'customer_name' => 'Guest',
            'customer_email' => 'g@example.com',
            'customer_phone' => '0812',
            'address' => 'Jl. G 1',
            'subtotal' => 100000,
            'discount' => 0,
            'shipping_cost' => 0,
            'total' => 100000,
            'status' => Order::STATUS_PENDING,
        ]);

        $this->assertFalse($order->isRefundable());

        $service = app(OrderFulfillmentService::class);

        $this->expectException(\InvalidArgumentException::class);
        $service->ship($order, [], 'jne', 'REG');
    }

    public function test_shipment_action_on_item_level_tracking(): void
    {
        $order = $this->paidOrder(5);
        $item = $order->items()->first();
        $service = app(OrderFulfillmentService::class);

        $service->ship($order, [
            ['order_item_id' => $item->id, 'quantity' => 5],
        ], 'pos', 'Reguler');

        $this->assertDatabaseHas('shipment_items', [
            'order_item_id' => $item->id,
            'quantity' => 5,
        ]);

        $this->assertSame(5, $order->fresh()->shippedQuantities()[$item->id]);
    }

    public function test_invalid_status_transition_throws(): void
    {
        $order = $this->paidOrder();

        $this->expectException(\InvalidArgumentException::class);
        $order->transitionTo(Order::STATUS_COMPLETED);
    }
}
