<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MidtransWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected string $serverKey = 'test-server-key';

    protected function setUp(): void
    {
        parent::setUp();

        config(['shop.midtrans.server_key' => $this->serverKey]);
    }

    protected function makeOrder(string $status = Order::STATUS_PENDING, int $total = 150000): Order
    {
        $order = Order::create([
            'customer_name' => 'Webhook Tester',
            'customer_email' => 'hook@example.com',
            'customer_phone' => '081234567890',
            'address' => 'Jl. Hook 1',
            'subtotal' => $total,
            'discount' => 0,
            'shipping_cost' => 0,
            'total' => $total,
            'status' => $status,
        ]);

        $order->items()->create([
            'product_name' => 'Hooked Product',
            'price' => $total,
            'quantity' => 1,
            'subtotal' => $total,
        ]);

        return $order;
    }

    protected function payload(Order $order, string $transactionStatus, array $overrides = []): array
    {
        $payload = array_merge([
            'order_id' => $order->order_number,
            'status_code' => '200',
            'gross_amount' => (string) $order->total,
            'transaction_status' => $transactionStatus,
            'transaction_id' => 'tx-'.Str::random(10),
            'payment_type' => 'qris',
            'fraud_status' => 'accept',
        ], $overrides);

        $payload['signature_key'] = hash(
            'sha512',
            $payload['order_id'].$payload['status_code'].$payload['gross_amount'].$this->serverKey,
        );

        return $payload;
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $order = $this->makeOrder();
        $payload = $this->payload($order, 'settlement');
        $payload['signature_key'] = 'bogus';

        $this->postJson(route('midtrans.webhook'), $payload)->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => Order::STATUS_PENDING]);
        $this->assertDatabaseCount('payment_transactions', 0);
    }

    public function test_wrong_amount_is_rejected(): void
    {
        $order = $this->makeOrder();
        $payload = $this->payload($order, 'settlement', ['gross_amount' => '1']);
        $payload['signature_key'] = hash('sha512', $payload['order_id'].$payload['status_code'].'1'.$this->serverKey);

        $this->postJson(route('midtrans.webhook'), $payload)->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => Order::STATUS_PENDING]);
    }

    public function test_settlement_marks_order_paid(): void
    {
        $order = $this->makeOrder();

        $this->postJson(route('midtrans.webhook'), $this->payload($order, 'settlement'))->assertOk();

        $order->refresh();
        $this->assertSame(Order::STATUS_PAID, $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertDatabaseCount('order_status_history', 1);
    }

    public function test_replayed_webhook_is_idempotent(): void
    {
        $order = $this->makeOrder();
        $payload = $this->payload($order, 'settlement');

        $this->postJson(route('midtrans.webhook'), $payload)->assertOk();
        $this->postJson(route('midtrans.webhook'), $payload)->assertOk();
        $this->postJson(route('midtrans.webhook'), $payload)->assertOk();

        $this->assertDatabaseCount('payment_transactions', 1);
        $this->assertDatabaseCount('order_status_history', 1);
        $order->refresh();
        $this->assertSame(Order::STATUS_PAID, $order->status);
        $this->assertSame(1, PaymentTransaction::count());
    }

    public function test_pending_webhook_cannot_regress_paid_order(): void
    {
        $order = $this->makeOrder(Order::STATUS_PAID);

        $this->postJson(route('midtrans.webhook'), $this->payload($order, 'pending'))->assertOk();

        $order->refresh();
        $this->assertSame(Order::STATUS_PAID, $order->status);
    }

    public function test_settlement_for_cancelled_order_is_ignored(): void
    {
        $order = $this->makeOrder(Order::STATUS_CANCELLED);

        $this->postJson(route('midtrans.webhook'), $this->payload($order, 'settlement'))->assertOk();

        $order->refresh();
        $this->assertSame(Order::STATUS_CANCELLED, $order->status);
        $this->assertNull($order->paid_at);
    }

    public function test_expire_cancels_pending_order_and_restocks(): void
    {
        $order = $this->makeOrder();
        $product = Product::create([
            'category_id' => Category::create(['name' => 'C'])->id,
            'name' => 'Restock Me',
            'price' => 10000,
            'stock' => 0,
            'is_active' => true,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 10000,
            'quantity' => 2,
            'subtotal' => 20000,
        ]);

        $this->postJson(route('midtrans.webhook'), $this->payload($order, 'expire'))->assertOk();

        $order->refresh();
        $this->assertSame(Order::STATUS_CANCELLED, $order->status);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 2]);
    }

    public function test_challenge_stays_pending(): void
    {
        $order = $this->makeOrder();

        $this->postJson(route('midtrans.webhook'), $this->payload($order, 'capture', ['fraud_status' => 'challenge']))->assertOk();

        $order->refresh();
        $this->assertSame(Order::STATUS_PENDING, $order->status);
    }
}
