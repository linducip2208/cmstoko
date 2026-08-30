<?php

namespace Tests\Feature;

use App\Livewire\CheckoutPage;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
        config(['shop.midtrans.server_key' => null]);
    }

    protected function seedProduct(int $stock = 1): Product
    {
        $category = Category::create(['name' => 'Test Cat']);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'Race Condition Product',
            'price' => 100000,
            'stock' => $stock,
            'weight' => 500,
            'is_active' => true,
        ]);
    }

    protected function actAsGuestWithCart(Product $product, int $qty = 1): void
    {
        Session::put('shop.cart', [$product->id => $qty]);
    }

    protected function checkoutPayload(): array
    {
        return [
            'customer_name' => 'Budi Pelanggan',
            'customer_email' => 'budi@example.com',
            'customer_phone' => '081234567890',
            'city_name_manual' => 'Jakarta Selatan',
            'province_name_manual' => 'DKI Jakarta',
            'address' => 'Jl. Test No. 123, RT 001/RW 002',
            'postal_code' => '12345',
            'notes' => null,
            'courier' => 'jne',
            'service' => '',
        ];
    }

    public function test_oversell_is_prevented_when_stock_is_one(): void
    {
        $product = $this->seedProduct(1);
        $this->actAsGuestWithCart($product);

        // First purchase drains stock.
        $component = Livewire::test(CheckoutPage::class)
            ->fill($this->checkoutPayload())
            ->call('placeOrder');

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 0]);

        // Second purchase with same cart (simulating a stale client) must fail.
        Session::put('shop.cart', [$product->id => 1]);

        $component = Livewire::test(CheckoutPage::class)
            ->fill($this->checkoutPayload())
            ->call('placeOrder');

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 0]);
    }

    public function test_stock_never_goes_negative_with_conditional_decrement(): void
    {
        $product = $this->seedProduct(3);
        $this->actAsGuestWithCart($product, 5);

        Livewire::test(CheckoutPage::class)
            ->fill($this->checkoutPayload())
            ->call('placeOrder');

        // Validation error, no order, stock untouched.
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 3]);
    }

    public function test_coupon_usage_limit_is_atomic(): void
    {
        $coupon = Coupon::create([
            'code' => 'LIMITED',
            'type' => Coupon::TYPE_PERCENT,
            'value' => 10,
            'min_purchase' => 0,
            'max_uses' => 2,
            'is_active' => true,
        ]);

        $product = $this->seedProduct(10);
        $this->actAsGuestWithCart($product, 1);

        // Simulate the quota being consumed concurrently right before checkout.
        Coupon::whereKey($coupon->id)->update(['used_count' => 2]);

        Session::put('shop.coupon', 'LIMITED');

        Livewire::test(CheckoutPage::class)
            ->fill($this->checkoutPayload())
            ->call('placeOrder');

        // Stale coupon is dropped; order still placed but without the discount.
        $this->assertDatabaseHas('coupons', ['id' => $coupon->id, 'used_count' => 2]);
        $this->assertDatabaseHas('orders', ['discount' => 0, 'coupon_code' => null]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 9]);
    }

    public function test_coupon_applied_when_quota_available(): void
    {
        Coupon::create([
            'code' => 'STOKOK',
            'type' => Coupon::TYPE_FIXED,
            'value' => 15000,
            'min_purchase' => 0,
            'max_uses' => 5,
            'is_active' => true,
        ]);

        $product = $this->seedProduct(10);
        $this->actAsGuestWithCart($product, 1);
        Session::put('shop.coupon', 'STOKOK');

        Livewire::test(CheckoutPage::class)
            ->fill($this->checkoutPayload())
            ->call('placeOrder');

        $this->assertDatabaseHas('coupons', ['code' => 'STOKOK', 'used_count' => 1]);
        $this->assertDatabaseHas('orders', ['discount' => 15000]);
    }

    public function test_guest_order_success_page_blocked_for_other_session(): void
    {
        $product = $this->seedProduct(5);
        $this->actAsGuestWithCart($product, 1);

        Livewire::test(CheckoutPage::class)
            ->fill($this->checkoutPayload())
            ->call('placeOrder');

        $orderNumber = DB::table('orders')->value('order_number');
        $this->assertNotNull($orderNumber);

        // New session (different guest) is denied.
        $this->flushSession();
        $this->get(route('order.success', $orderNumber))->assertForbidden();
    }

    public function test_owner_and_staff_can_view_order_page(): void
    {
        $product = $this->seedProduct(5);
        $this->actAsGuestWithCart($product, 1);

        Livewire::test(CheckoutPage::class)
            ->fill($this->checkoutPayload())
            ->call('placeOrder');

        $orderNumber = DB::table('orders')->value('order_number');

        // Placing guest still sees it.
        $this->get(route('order.success', $orderNumber))->assertOk();

        // Staff sees it.
        $admin = User::factory()->create(['role_id' => Role::where('slug', Role::ORDER_STAFF)->value('id')]);
        $this->actingAs($admin)->get(route('order.success', $orderNumber))->assertOk();
    }
}
