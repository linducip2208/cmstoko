<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingsSeeder::class);
    }

    protected function product(int $stock = 10): Product
    {
        return Product::create([
            'category_id' => Category::create(['name' => 'Inv Cat'])->id,
            'name' => 'Inventory Product '.uniqid(),
            'price' => 50000,
            'stock' => $stock,
            'weight' => 300,
            'is_active' => true,
        ]);
    }

    public function test_deduct_writes_movement_and_updates_stock(): void
    {
        $product = $this->product(10);
        $service = app(InventoryService::class);

        $service->deduct($product->id, null, 4, StockMovement::TYPE_SALE);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 6]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovement::TYPE_SALE,
            'quantity' => -4,
            'stock_before' => 10,
            'stock_after' => 6,
        ]);
    }

    public function test_deduct_rejects_insufficient_stock(): void
    {
        $product = $this->product(2);
        $service = app(InventoryService::class);

        $this->expectException(\InvalidArgumentException::class);

        $service->deduct($product->id, null, 5, StockMovement::TYPE_SALE);
    }

    public function test_adjust_can_go_up_and_down_but_not_negative(): void
    {
        $product = $this->product(5);
        $service = app(InventoryService::class);

        $service->adjust($product->id, null, 20, 'Restock supplier');
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 25]);

        $service->adjust($product->id, null, -3, 'Rusak');
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 22]);

        $this->expectException(\InvalidArgumentException::class);
        $service->adjust($product->id, null, -100, 'Salah input');
    }

    public function test_cancel_order_restocks_with_ledger(): void
    {
        $product = $this->product(5);
        $service = app(InventoryService::class);

        $order = Order::create([
            'customer_name' => 'Stok Tester',
            'customer_email' => 'stok@example.com',
            'customer_phone' => '081200000000',
            'address' => 'Jl. Stok 1',
            'subtotal' => 100000,
            'discount' => 0,
            'shipping_cost' => 0,
            'total' => 100000,
            'status' => Order::STATUS_PENDING,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 100000,
            'quantity' => 3,
            'subtotal' => 100000,
        ]);

        $service->deduct($product->id, null, 3, StockMovement::TYPE_SALE, $order);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 2]);

        $order->transitionTo(Order::STATUS_CANCELLED, 'Webhook expire');
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 5]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovement::TYPE_SALE_CANCEL,
            'quantity' => 3,
            'stock_after' => 5,
        ]);

        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->id,
            'from' => Order::STATUS_PENDING,
            'to' => Order::STATUS_CANCELLED,
        ]);
    }

    public function test_movement_history_is_queryable(): void
    {
        $product = $this->product(10);
        $service = app(InventoryService::class);

        $service->deduct($product->id, null, 2, StockMovement::TYPE_SALE);
        $service->increase($product->id, null, 5, StockMovement::TYPE_RETURN);

        $history = $service->history($product->id);

        $this->assertSame(2, $history->count());
        $this->assertSame(StockMovement::TYPE_RETURN, $history->first()->type);
        $this->assertSame(13, $history->first()->stock_after);
    }
}
