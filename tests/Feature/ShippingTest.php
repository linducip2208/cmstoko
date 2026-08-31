<?php

namespace Tests\Feature;

use App\Data\ShippingContext;
use App\Livewire\CheckoutPage;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Services\Shipping\ShippingManager;
use App\Support\Settings;
use Database\Seeders\CustomerGroupSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;
use Tests\TestCase;

class ShippingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
        $this->seed(CustomerGroupSeeder::class);
        config(['shop.midtrans.server_key' => null]);
        config(['shop.rajaongkir.api_key' => null]);
    }

    protected function product(int $price = 100000): Product
    {
        return Product::create([
            'category_id' => Category::create(['name' => 'Ship '.uniqid()])->id,
            'name' => 'Ship Product '.uniqid(),
            'price' => $price,
            'stock' => 10,
            'weight' => 1000,
            'is_active' => true,
        ]);
    }

    protected function context(int $subtotal = 100000): ShippingContext
    {
        return new ShippingContext(null, null, 1000, $subtotal);
    }

    public function test_flat_rate_provider_enabled_by_default(): void
    {
        $options = app(ShippingManager::class)->options($this->context());

        $flat = collect($options)->firstWhere('provider', 'flat');

        $this->assertNotNull($flat);
        $this->assertSame(20000, $flat['cost']);
        $this->assertSame('flat:FLAT', $flat['key']);
    }

    public function test_free_shipping_appears_only_above_minimum(): void
    {
        $manager = app(ShippingManager::class);

        $below = $manager->options($this->context(200000));
        $this->assertNull(collect($below)->firstWhere('provider', 'free'));

        $above = $manager->options($this->context(300000));
        $free = collect($above)->firstWhere('provider', 'free');
        $this->assertNotNull($free);
        $this->assertSame(0, $free['cost']);
    }

    public function test_disabling_provider_removes_it(): void
    {
        Settings::set('shipping.flat.enabled', false, 'shipping');

        $options = app(ShippingManager::class)->options($this->context(100000));

        $this->assertNull(collect($options)->firstWhere('provider', 'flat'));
    }

    public function test_store_pickup_provider_opt_in(): void
    {
        Settings::set('shipping.pickup.enabled', true, 'shipping');
        Settings::set('shipping.pickup.address', 'Jl. Toko No. 1', 'shipping');

        $options = app(ShippingManager::class)->options($this->context());

        $pickup = collect($options)->firstWhere('provider', 'pickup');

        $this->assertNotNull($pickup);
        $this->assertSame(0, $pickup['cost']);
        $this->assertStringContainsString('Jl. Toko No. 1', $pickup['description']);
    }

    public function test_resolve_is_server_authoritative(): void
    {
        $manager = app(ShippingManager::class);

        // Unknown key falls back to the first real option (never a fake price).
        $resolved = $manager->resolve('flat:FAKE', $this->context());
        $this->assertSame(20000, $resolved['cost']);

        $resolved = $manager->resolve('flat:FLAT', $this->context());
        $this->assertSame(20000, $resolved['cost']);
    }

    public function test_checkout_shipping_cost_survives_client_tampering(): void
    {
        $product = $this->product();

        Session::put('shop.cart', [$product->id => 1]);

        // Attacker injects a zero-cost option into the public component state.
        Livewire::test(CheckoutPage::class)
            ->fill([
                'customer_name' => 'Spoofer',
                'customer_email' => 'spoof@example.com',
                'customer_phone' => '0812',
                'city_name_manual' => 'Depok',
                'province_name_manual' => 'Jabar',
                'address' => 'Jl. Spoof',
                'service' => '',
            ])
            ->set('shippingOptions', [
                ['provider' => 'flat', 'service' => 'FLAT', 'description' => 'Hacked', 'cost' => 0, 'etd' => '1', 'key' => 'flat:FLAT'],
            ])
            ->set('shippingKey', 'flat:FLAT')
            ->call('placeOrder')
            ->assertHasNoErrors();

        $order = Order::first();

        // Server recomputed: real flat cost 20000 — injected 0 ignored.
        $this->assertSame(20000, $order->shipping_cost);
        $this->assertSame(120000, $order->total);
    }

    public function test_pickup_only_checkout_still_places_order(): void
    {
        Settings::set('shipping.flat.enabled', false, 'shipping');
        Settings::set('shipping.free.enabled', false, 'shipping');
        Settings::set('shipping.pickup.enabled', true, 'shipping');

        $product = $this->product();

        Session::put('shop.cart', [$product->id => 1]);

        Livewire::test(CheckoutPage::class)
            ->fill([
                'customer_name' => 'Picker',
                'customer_email' => 'pickup@example.com',
                'customer_phone' => '0812',
                'city_name_manual' => 'Depok',
                'province_name_manual' => 'Jabar',
                'address' => 'Jl. Pickup',
                'service' => '',
            ])
            ->call('placeOrder')
            ->assertHasNoErrors();

        $order = Order::first();

        $this->assertSame(0, $order->shipping_cost);
        $this->assertSame(100000, $order->total);
    }
}
