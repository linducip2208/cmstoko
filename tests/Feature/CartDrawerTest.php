<?php

namespace Tests\Feature;

use App\Livewire\CartDrawer;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;
use Tests\TestCase;

class CartDrawerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    protected function product(): Product
    {
        return Product::create([
            'category_id' => Category::create(['name' => 'Drawer '.uniqid()])->id,
            'name' => 'Drawer Product '.uniqid(),
            'price' => 50000,
            'stock' => 5,
            'weight' => 100,
            'is_active' => true,
        ]);
    }

    public function test_drawer_shows_items_and_updates_quantity(): void
    {
        $product = $this->product();

        Session::put('shop.cart', [$product->id => 1]);

        $component = Livewire::test(CartDrawer::class);

        $component->assertSee($product->name);

        $component->call('increment', (string) $product->id);

        $this->assertSame(2, Session::get('shop.cart')[$product->id]);

        $component->call('decrement', (string) $product->id);

        $this->assertSame(1, Session::get('shop.cart')[$product->id]);
    }

    public function test_drawer_remove_item_leads_to_empty_state(): void
    {
        $product = $this->product();

        Session::put('shop.cart', [$product->id => 1]);

        $component = Livewire::test(CartDrawer::class);

        $component->call('removeItem', (string) $product->id);

        $this->assertSame([], Session::get('shop.cart', []));
        $component->assertSee('Keranjang masih kosong');
    }

    public function test_drawer_applies_and_removes_coupon(): void
    {
        Coupon::create([
            'code' => 'DRAWER10',
            'type' => 'percent',
            'value' => 10,
            'min_spend' => 0,
            'max_uses' => null,
            'used_count' => 0,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'is_active' => true,
        ]);

        $product = $this->product();

        Session::put('shop.cart', [$product->id => 2]);

        $component = Livewire::test(CartDrawer::class)
            ->set('couponCode', 'DRAWER10')
            ->call('applyCoupon');

        $this->assertTrue($component->get('couponSuccess'));

        $component->call('removeCoupon');

        $this->assertNull(Session::get('shop.coupon'));
    }
}
