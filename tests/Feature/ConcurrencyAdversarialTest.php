<?php

namespace Tests\Feature;

use App\Livewire\CheckoutPage;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantAttributeValue;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\ReturnRequest;
use App\Models\Role;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;
use Tests\TestCase;

class ConcurrencyAdversarialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
        config(['shop.midtrans.server_key' => null]);
    }

    protected function product(int $stock = 5, int $price = 100000): Product
    {
        return Product::create([
            'category_id' => Category::create(['name' => 'Race '.uniqid()])->id,
            'name' => 'Race Product '.uniqid(),
            'price' => $price,
            'stock' => $stock,
            'weight' => 100,
            'is_active' => true,
        ]);
    }

    protected function checkoutAs(?User $user = null): \Livewire\Features\SupportTesting\Testable
    {
        $test = Livewire::withQueryParams([]);
        $component = $user ? Livewire::actingAs($user)->test(CheckoutPage::class) : Livewire::test(CheckoutPage::class);

        return $component->fill([
            'customer_name' => $user?->name ?? 'Guest Buyer',
            'customer_email' => $user?->email ?? 'guest@example.com',
            'customer_phone' => '081200000009',
            'city_name_manual' => 'Depok',
            'province_name_manual' => 'Jawa Barat',
            'address' => 'Jl. Race No. 1',
            'service' => '',
        ]);
    }

    public function test_two_buyers_cannot_oversell_last_variant_stock(): void
    {
        $product = $this->product(0);
        $product->update(['type' => 'configurable']);

        $attribute = Attribute::create(['name' => 'Warna', 'slug' => 'warna-'.uniqid(), 'type' => 'select']);
        $red =         AttributeOption::create(['attribute_id' => $attribute->id, 'label' => 'Merah', 'value' => 'merah', 'slug' => 'merah-'.uniqid(), 'sort_order' => 1]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'VAR-'.uniqid(),
            'price' => $product->price,
            'stock' => 1,
            'is_active' => true,
        ]);

        ProductVariantAttributeValue::create([
            'variant_id' => $variant->id,
            'attribute_id' => $attribute->id,
            'attribute_option_id' => $red->id,
        ]);

        // Buyer 1 takes the only variant unit.
        Session::put('shop.cart', [$product->id.':'.$variant->id => 1]);

        $this->checkoutAs()
            ->call('placeOrder')
            ->assertHasNoErrors();

        $this->assertSame(0, $variant->fresh()->stock);
        $this->assertSame(1, Order::count());

        // Buyer 2 (new session) tries to take the now-empty variant.
        Session::put('shop.cart', [$product->id.':'.$variant->id => 1]);
        Session::forget('shop.orders');

        $this->checkoutAs()
            ->call('placeOrder')
            ->assertHasErrors(['stock']);

        $this->assertSame(1, Order::count());
        $this->assertSame(0, $variant->fresh()->stock);
    }

    public function test_checkout_price_is_server_authoritative_not_client_cached(): void
    {
        // Disable free shipping so flat cost applies and totals are predictable.
        Settings::set('shipping.free.enabled', false, 'shipping');

        $product = $this->product(stock: 5, price: 100000);

        Session::put('shop.cart', [$product->id => 2]);

        // Price changes in DB AFTER the product is added to the cart.
        $product->update(['price' => 250000]);

        $this->checkoutAs()->call('placeOrder')->assertHasNoErrors();

        $order = Order::first();

        // Server recalculated: 2 × fresh 250000. Client-cached 100000 price is ignored.
        $this->assertSame(500000, $order->subtotal);
        $this->assertSame(520000, $order->total); // + flat shipping 20000
    }

    public function test_duplicate_checkout_submit_creates_only_one_order(): void
    {
        $product = $this->product(stock: 10);

        Session::put('shop.cart', [$product->id => 1]);

        $component = $this->checkoutAs()->call('placeOrder')->assertHasNoErrors();

        $this->assertSame(1, Order::count());

        // Cart is cleared after the first submission; a retried submit cannot double-order.
        $component->call('placeOrder');

        $this->assertSame(1, Order::count());
    }

    public function test_final_coupon_use_cannot_be_double_spent(): void
    {
        $coupon = Coupon::create([
            'code' => 'LASTONE',
            'type' => 'fixed',
            'value' => 20000,
            'min_spend' => 0,
            'max_uses' => 1,
            'used_count' => 0,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'is_active' => true,
        ]);

        $product = $this->product(stock: 10);

        // Buyer 1 spends the final coupon slot.
        Session::put('shop.cart', [$product->id => 1]);
        Session::put('shop.coupon', 'LASTONE');

        $this->checkoutAs()->call('placeOrder')->assertHasNoErrors();

        $this->assertSame(1, $coupon->fresh()->used_count);
        $this->assertSame(1, Order::count());

        // Buyer 2 in a fresh session — coupon quota exhausted server-side.
        Session::put('shop.cart', [$product->id => 1]);
        Session::forget('shop.orders');

        $component = $this->checkoutAs();
        $component->set('couponCode', 'LASTONE');
        $component->call('applyCoupon');

        $this->assertFalse($component->get('couponSuccess'));

        $component->call('placeOrder');

        $order = Order::latest('id')->first();
        $this->assertSame(0, $order->discount); // no discount applied
        $this->assertSame(1, $coupon->fresh()->used_count);
    }

    public function test_return_quantity_capped_across_multiple_requests(): void
    {
        $customer = User::factory()->create(['role_id' => Role::where('slug', Role::CUSTOMER)->value('id')]);
        $product = $this->product(stock: 10);

        $order = Order::create([
            'user_id' => $customer->id,
            'customer_name' => 'Return Tester',
            'customer_email' => 'return@example.com',
            'customer_phone' => '0812',
            'city_name' => 'Depok',
            'province_name' => 'Jabar',
            'address' => 'Jl. Return',
            'subtotal' => 300000,
            'discount' => 0,
            'shipping_cost' => 0,
            'total' => 300000,
            'weight' => 100,
            'payment_method' => 'manual_transfer',
        ]);

        $item = $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 100000,
            'quantity' => 3,
            'subtotal' => 300000,
        ]);

        $order->transitionTo(Order::STATUS_PAID);

        // First request: 2 of 3 units.
        $this->actingAs($customer)->post('/akun/pengembalian', [
            'order_number' => $order->order_number,
            'reason' => 'Salah ukuran',
            'items' => [['order_item_id' => $item->id, 'quantity' => 2]],
        ])->assertRedirect();

        // Second request: tries 3 more — capped at the 1 remaining unit.
        $this->actingAs($customer)->post('/akun/pengembalian', [
            'order_number' => $order->order_number,
            'reason' => 'Rusak',
            'items' => [['order_item_id' => $item->id, 'quantity' => 3]],
        ])->assertRedirect();

        $totals = \App\Models\ReturnItem::where('order_item_id', $item->id)->sum('quantity');

        $this->assertSame(3, $totals);
    }

    public function test_refund_above_paid_total_rejected(): void
    {
        $order = Order::create([
            'customer_name' => 'Refund Race',
            'customer_email' => 'r@example.com',
            'customer_phone' => '0813',
            'city_name' => 'Depok',
            'province_name' => 'Jabar',
            'address' => 'Jl. R',
            'subtotal' => 50000,
            'discount' => 0,
            'shipping_cost' => 0,
            'total' => 50000,
            'weight' => 100,
            'payment_method' => 'manual_transfer',
        ]);

        $order->transitionTo(Order::STATUS_PAID);

        $this->expectException(\InvalidArgumentException::class);

        app(\App\Services\OrderFulfillmentService::class)->refund($order, 999999, 'over');
    }
}
