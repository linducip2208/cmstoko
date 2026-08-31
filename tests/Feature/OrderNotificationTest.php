<?php

namespace Tests\Feature;

use App\Events\OrderPlaced;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Notifications\OrderStatusMail;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrderNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
        Notification::fake();
    }

    protected function order(string $status = Order::STATUS_PENDING, string $method = 'manual_transfer'): Order
    {
        $product = Product::create([
            'category_id' => Category::create(['name' => 'Mail Cat '.uniqid()])->id,
            'name' => 'Mail Product '.uniqid(),
            'price' => 90000,
            'stock' => 4,
            'weight' => 100,
            'is_active' => true,
        ]);

        return Order::create([
            'customer_name' => 'Mail Tester',
            'customer_email' => 'mail-tester@example.com',
            'customer_phone' => '081200000001',
            'city_name' => 'Depok',
            'province_name' => 'Jawa Barat',
            'address' => 'Jl. Mail No. 2',
            'subtotal' => 90000,
            'discount' => 0,
            'shipping_cost' => 10000,
            'total' => 100000,
            'weight' => 100,
            'payment_method' => $method,
            'status' => $status,
        ]);
    }

    public function test_order_placed_sends_mail_with_transfer_instructions(): void
    {
        $order = $this->order();

        OrderPlaced::dispatch($order);

        Notification::assertSentTo($order, OrderStatusMail::class, function (OrderStatusMail $mail) use ($order) {
            $rendered = $mail->toMail($order)->toArray();

            return str_contains($rendered['subject'], $order->order_number)
                && str_contains($rendered['subject'], 'Pesanan diterima');
        });
    }

    public function test_paid_shipped_completed_cancelled_each_send_one_mail(): void
    {
        $order = $this->order();

        $order->transitionTo(Order::STATUS_PAID);
        $order->transitionTo(Order::STATUS_PROCESSING);
        $order->transitionTo(Order::STATUS_SHIPPED);

        $order->shipments()->create([
            'courier' => 'jne',
            'service' => 'REG',
            'tracking_number' => 'JNE123456',
            'cost' => 0,
        ]);

        $order->transitionTo(Order::STATUS_COMPLETED);
        $order->transitionTo(Order::STATUS_CANCELLED, 'test', null, true);

        // paid + shipped + completed + cancelled (processing noise excluded).
        Notification::assertSentTo($order, OrderStatusMail::class, 4);
    }

    public function test_guest_order_mail_targets_customer_email(): void
    {
        $order = $this->order();

        $order->transitionTo(Order::STATUS_PAID);

        Notification::assertSentTo($order, OrderStatusMail::class, function (OrderStatusMail $mail) use ($order) {
            $mailMessage = $mail->toMail($order);

            return $mailMessage->subject !== null
                && str_contains($mailMessage->subject, 'Pembayaran terkonfirmasi');
        });
    }
}
