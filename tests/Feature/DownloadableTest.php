<?php

namespace Tests\Feature;

use App\Livewire\CheckoutPage;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DownloadableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
        Storage::fake('downloads');
    }

    protected function downloadableProduct(array $attrs = []): Product
    {
        $product = Product::create(array_merge([
            'category_id' => Category::create(['name' => 'DL '.uniqid()])->id,
            'name' => 'E-Book '.uniqid(),
            'type' => Product::TYPE_DOWNLOADABLE,
            'price' => 150000,
            'stock' => 999,
            'weight' => 0,
            'is_active' => true,
            'requires_shipping' => false,
        ], $attrs));

        Storage::disk('downloads')->put('files/ebook.pdf', 'PDF-CONTENT-'.uniqid());

        $product->downloads()->create([
            'file_path' => 'files/ebook.pdf',
            'file_name' => 'e-book.pdf',
            'label' => 'E-Book Utama',
        ]);

        return $product;
    }

    protected function paidOrder(User $user, Product $product): Order
    {
        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => '0812',
            'city_name' => 'Depok',
            'province_name' => 'Jabar',
            'address' => 'Jl',
            'subtotal' => $product->price,
            'discount' => 0,
            'shipping_cost' => 0,
            'total' => $product->price,
            'weight' => 0,
            'payment_method' => 'manual_transfer',
        ]);

        $item = $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => $product->price,
            'quantity' => 1,
            'subtotal' => $product->price,
        ]);

        $order->forceFill(['status' => Order::STATUS_PAID, 'paid_at' => now()])->save();

        return $order->fresh();
    }

    public function test_owner_of_paid_order_can_download(): void
    {
        $user = User::factory()->create();
        $product = $this->downloadableProduct();
        $order = $this->paidOrder($user, $product);
        $item = $order->items->first();

        $response = $this->actingAs($user)->get(route('account.download', $item));

        $response->assertOk();
        $this->assertStringContainsString('PDF-CONTENT', (string) $response->streamedContent());

        // Log row written.
        $this->assertSame(1, DB::table('product_download_logs')->where('order_item_id', $item->id)->count());
    }

    public function test_non_owner_gets_404(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $order = $this->paidOrder($owner, $this->downloadableProduct());
        $item = $order->items->first();

        $this->actingAs($stranger)
            ->get(route('account.download', $item))
            ->assertNotFound();
    }

    public function test_guest_gets_404(): void
    {
        $order = $this->paidOrder(User::factory()->create(), $this->downloadableProduct());

        $this->get(route('account.download', $order->items->first()))
            ->assertRedirect(route('login'));
    }

    public function test_unpaid_order_cannot_download(): void
    {
        $user = User::factory()->create();
        $product = $this->downloadableProduct();

        $order = $this->paidOrder($user, $product);
        $order->forceFill(['status' => Order::STATUS_PENDING, 'paid_at' => null])->save();
        $item = $order->items->first();

        $this->actingAs($user)
            ->get(route('account.download', $item))
            ->assertForbidden();
    }

    public function test_expired_download_window_forbidden(): void
    {
        $user = User::factory()->create();
        $product = $this->downloadableProduct(['download_expiry_days' => 7]);
        $order = $this->paidOrder($user, $product);
        $order->forceFill(['paid_at' => now()->subDays(8)])->save();
        $item = $order->items->first();

        $this->actingAs($user)
            ->get(route('account.download', $item))
            ->assertForbidden();
    }

    public function test_download_limit_enforced(): void
    {
        $user = User::factory()->create();
        $product = $this->downloadableProduct(['download_limit' => 2]);
        $order = $this->paidOrder($user, $product);
        $item = $order->items->first();

        $this->actingAs($user)->get(route('account.download', $item))->assertOk();
        $this->actingAs($user)->get(route('account.download', $item))->assertOk();
        $this->actingAs($user)->get(route('account.download', $item))->assertForbidden();

        $this->assertSame(2, DB::table('product_download_logs')->where('order_item_id', $item->id)->count());
    }

    public function test_virtual_cart_checkout_needs_no_shipping(): void
    {
        config(['shop.midtrans.server_key' => null]);

        $virtual = Product::create([
            'category_id' => Category::create(['name' => 'V '.uniqid()])->id,
            'name' => 'Virtual Service',
            'type' => Product::TYPE_VIRTUAL,
            'price' => 200000,
            'stock' => 999,
            'weight' => 0,
            'requires_shipping' => false,
            'is_active' => true,
        ]);

        Session::put('shop.cart', [$virtual->id => 1]);

        Livewire::test(CheckoutPage::class)->fill([
            'customer_name' => 'Digital Buyer',
            'customer_email' => 'digital@example.com',
            'customer_phone' => '0812',
            'city_name_manual' => 'Depok',
            'province_name_manual' => 'Jabar',
            'address' => 'Jl. Digital',
            'service' => '',
        ])->call('placeOrder')->assertHasNoErrors();

        $order = Order::latest('id')->first();

        $this->assertSame(0, $order->shipping_cost);
        $this->assertSame(200000, $order->total);
        $this->assertSame(0, $order->weight);
    }
}
