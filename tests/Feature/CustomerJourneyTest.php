<?php

namespace Tests\Feature;

use App\Livewire\CheckoutPage;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ReturnRequest;
use App\Models\Review;
use App\Models\Role;
use App\Models\User;
use App\Models\Wishlist;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
        config(['shop.midtrans.server_key' => null]);
    }

    protected function product(int $stock = 5): Product
    {
        return Product::create([
            'category_id' => Category::create(['name' => 'Journey '.uniqid()])->id,
            'name' => 'Journey Product '.uniqid(),
            'price' => 150000,
            'stock' => $stock,
            'weight' => 400,
            'is_active' => true,
        ]);
    }

    protected function addToCart(Product $product, int $qty = 1): void
    {
        Session::put('shop.cart', [$product->id => $qty]);
    }

    public function test_guest_browse_checkout_and_order_success(): void
    {
        $product = $this->product();

        // Homepage renders.
        $this->get('/')->assertOk();

        // Shop + product pages render.
        $this->get('/produk')->assertOk();
        $this->get('/produk/'.$product->slug)->assertOk();

        // Checkout as guest.
        $this->addToCart($product, 2);

        Livewire::test(CheckoutPage::class)
            ->fill([
                'customer_name' => 'Journey Tester',
                'customer_email' => 'journey@example.com',
                'customer_phone' => '081234567891',
                'city_name_manual' => 'Jakarta Selatan',
                'province_name_manual' => 'DKI Jakarta',
                'address' => 'Jl. Journey No. 8',
                'service' => '',
            ])
            ->call('placeOrder');

        $orderNumber = \DB::table('orders')->value('order_number');
        $this->assertNotNull($orderNumber);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 3]);

        // Placing guest sees the order success page.
        $this->get(route('order.success', $orderNumber))->assertOk();

        // Track order renders for the order number.
        $this->get('/lacak')->assertOk();
    }

    public function test_customer_register_login_wishlist_review_flow(): void
    {
        $product = $this->product();

        // Register.
        $this->post('/daftar', [
            'name' => 'Customer Journey',
            'email' => 'journey2@example.com',
            'phone' => '081234567892',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('account'));

        $customer = User::where('email', 'journey2@example.com')->first();
        $this->assertNotNull($customer->role_id);
        $this->assertSame(Role::CUSTOMER, $customer->role->slug);

        // Dashboard accessible.
        $this->actingAs($customer)->get('/akun')->assertOk();
        $this->actingAs($customer)->get('/akun/pesanan')->assertOk();
        $this->actingAs($customer)->get('/akun/profil')->assertOk();

        // Wishlist toggle.
        $this->actingAs($customer)->post('/akun/wishlist', ['product_id' => $product->id])->assertRedirect();
        $this->assertDatabaseHas('wishlists', ['user_id' => $customer->id, 'product_id' => $product->id]);
        $this->get('/akun/wishlist')->assertOk();

        // Order + review flow.
        $this->addToCart($product, 1);
        Livewire::test(CheckoutPage::class)
            ->fill([
                'customer_name' => 'Customer Journey',
                'customer_email' => 'journey2@example.com',
                'customer_phone' => '081234567892',
                'city_name_manual' => 'Jakarta Selatan',
                'province_name_manual' => 'DKI Jakarta',
                'address' => 'Jl. Journey No. 9',
                'service' => '',
            ])
            ->call('placeOrder');

        $order = Order::first();
        $this->assertNotNull($order);

        // Review a paid item.
        $order->transitionTo(Order::STATUS_PAID, 'paid via test');

        $itemId = $order->items()->value('id');

        $this->actingAs($customer)->post(route('account.orders.review', $order->order_number), [
            'order_item_id' => $itemId,
            'rating' => 5,
            'content' => 'Bagus sekali, sesuai deskripsi.',
        ])->assertRedirect();

        $this->assertDatabaseHas('reviews', [
            'product_id' => $product->id,
            'status' => Review::STATUS_PENDING,
            'is_verified' => true,
        ]);

        // Review is NOT visible until approved.
        $this->get('/produk/'.$product->slug)->assertOk();
        $this->assertStringNotContainsString('Bagus sekali', $this->get('/produk/'.$product->slug)->getContent());

        // Approve the review → aggregate rating must reflect real data.
        Review::first()->update(['status' => Review::STATUS_APPROVED, 'approved_at' => now()]);

        $page = $this->get('/produk/'.$product->slug)->getContent();
        $this->assertStringContainsString('Bagus sekali', $page);
        $this->assertStringContainsString('dari 1 ulasan', $page);

        // Return request window check (paid order, within window).
        $this->actingAs($customer)->post('/akun/pengembalian', [
            'order_number' => $order->order_number,
            'reason' => 'Ukuran terlalu kecil',
            'items' => [['order_item_id' => $itemId, 'quantity' => 1]],
        ])->assertRedirect();

        $this->assertDatabaseHas('return_requests', [
            'order_id' => $order->id,
            'status' => ReturnRequest::STATUS_REQUESTED,
        ]);
    }

    public function test_wishlist_persists_after_removal_toggle(): void
    {
        $product = $this->product();
        $customer = User::factory()->create(['role_id' => Role::where('slug', Role::CUSTOMER)->value('id')]);

        $this->actingAs($customer)->post('/akun/wishlist', ['product_id' => $product->id]);
        $this->assertDatabaseCount('wishlists', 1);

        // Toggling again removes it.
        $this->actingAs($customer)->post('/akun/wishlist', ['product_id' => $product->id])->assertRedirect();
        $this->assertDatabaseCount('wishlists', 0);
    }
}
