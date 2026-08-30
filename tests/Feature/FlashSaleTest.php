<?php

namespace Tests\Feature;

use App\Livewire\CheckoutPage;
use App\Models\FlashSale;
use App\Models\Product;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;
use Tests\TestCase;

class FlashSaleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
        config(['shop.midtrans.server_key' => null]);
    }

    protected function product(int $price = 200000, ?int $salePrice = null): Product
    {
        return Product::create([
            'category_id' => \App\Models\Category::create(['name' => 'Flash '.uniqid()])->id,
            'name' => 'Flash Product '.uniqid(),
            'price' => $price,
            'sale_price' => $salePrice,
            'stock' => 10,
            'weight' => 100,
            'is_active' => true,
        ]);
    }

    protected function sale(Product $product, int $flashPrice, string $from = '-1 hour', string $to = '+1 hour'): FlashSale
    {
        $sale = FlashSale::create([
            'name' => 'Flash '.uniqid(),
            'slug' => 'flash-'.uniqid(),
            'starts_at' => now()->modify($from),
            'ends_at' => now()->modify($to),
            'is_active' => true,
        ]);

        $sale->products()->attach($product->id, ['flash_price' => $flashPrice]);

        FlashSale::flushPriceMap();

        return $sale;
    }

    public function test_active_flash_sale_lowers_price(): void
    {
        $product = $this->product();

        $this->assertSame(200000, $product->effectivePrice());

        $this->sale($product, 100000);

        $this->assertSame(100000, $product->fresh()->effectivePrice());
        $this->assertSame(50, $product->fresh()->discountPercent());
        $this->assertTrue($product->fresh()->hasDiscount());
    }

    public function test_expired_flash_sale_reverts_price_immediately(): void
    {
        $product = $this->product();

        $sale = $this->sale($product, 100000);

        $this->assertSame(100000, $product->fresh()->effectivePrice());

        // Expire the sale — pricing must revert without any intervention.
        $sale->update(['ends_at' => now()->subMinute()]);
        FlashSale::flushPriceMap();

        $this->assertSame(200000, $product->fresh()->effectivePrice());
    }

    public function test_flash_price_never_beats_base_when_higher(): void
    {
        $product = $this->product(price: 200000, salePrice: 150000);

        // Flash price above the sale price must be ignored.
        $this->sale($product, 180000);

        $this->assertSame(150000, $product->fresh()->effectivePrice());
    }

    public function test_uses_cheapest_when_multiple_sales_overlap(): void
    {
        $product = $this->product();

        $this->sale($product, 120000);
        $this->sale($product, 90000);

        $this->assertSame(90000, $product->fresh()->effectivePrice());
    }

    public function test_checkout_totals_use_flash_price_server_side(): void
    {
        $product = $this->product();

        // Cart filled before the flash sale starts.
        Session::put('shop.cart', [$product->id => 2]);

        $this->sale($product, 80000);

        Livewire::test(CheckoutPage::class)->fill([
            'customer_name' => 'Flash Buyer',
            'customer_email' => 'flash@example.com',
            'customer_phone' => '081200000004',
            'city_name_manual' => 'Depok',
            'province_name_manual' => 'Jawa Barat',
            'address' => 'Jl. Flash No. 1',
            'service' => '',
        ])->call('placeOrder')->assertHasNoErrors();

        $order = \App\Models\Order::first();

        // 2 × flash 80000 — server recomputed at checkout time, not cart time.
        $this->assertSame(160000, $order->subtotal);
        $this->assertSame(180000, $order->total); // + flat shipping 20000
    }
}
