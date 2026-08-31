<?php

namespace Tests\Feature;

use App\Models\InventoryLevel;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WarehouseTest extends TestCase
{
    use RefreshDatabase;

    protected InventoryService $inventory;

    protected Warehouse $main;

    protected Warehouse $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);

        $this->inventory = app(InventoryService::class);
        $this->main = Warehouse::create(['name' => 'Gudang Utama', 'code' => 'MAIN', 'is_default' => true, 'is_active' => true]);
        $this->branch = Warehouse::create(['name' => 'Gudang Cabang', 'code' => 'BRANCH', 'is_active' => true]);
    }

    protected function product(int $mainStock = 0, int $branchStock = 0): \App\Models\Product
    {
        $product = \App\Models\Product::create([
            'category_id' => \App\Models\Category::create(['name' => 'Wh Cat '.uniqid()])->id,
            'name' => 'Wh Product '.uniqid(),
            'price' => 100000,
            'stock' => $mainStock,
            'weight' => 100,
            'is_active' => true,
        ]);

        if ($mainStock > 0) {
            InventoryLevel::create(['warehouse_id' => $this->main->id, 'product_id' => $product->id, 'variant_id' => null, 'stock' => $mainStock]);
        }

        if ($branchStock > 0) {
            InventoryLevel::create(['warehouse_id' => $this->branch->id, 'product_id' => $product->id, 'variant_id' => null, 'stock' => $branchStock]);
        }

        return $product;
    }

    public function test_only_one_default_warehouse(): void
    {
        $another = Warehouse::create(['name' => 'New Default', 'code' => 'NEW', 'is_default' => true]);

        $this->assertFalse($this->main->fresh()->is_default);
        $this->assertTrue($another->fresh()->is_default);
    }

    public function test_adjust_warehouse_updates_level_only(): void
    {
        $product = $this->product(10);

        $this->inventory->adjustWarehouse($product->id, null, $this->branch->id, 5, 'restock cabang');

        $this->assertSame(5, $this->inventory->level($product->id, null, $this->branch->id));
        $this->assertSame(10, $this->inventory->level($product->id, null, $this->main->id));

        // Negative beyond availability rejected.
        $this->expectException(\InvalidArgumentException::class);
        $this->inventory->adjustWarehouse($product->id, null, $this->branch->id, -6);
    }

    public function test_allocation_uses_default_warehouse_first_then_branch(): void
    {
        // 4 in MAIN, 6 in BRANCH; order of 7 → 4 from MAIN + 3 from BRANCH.
        $product = $this->product(4, 6);

        $allocations = $this->inventory->allocate(collect([
            ['product_id' => $product->id, 'variant_id' => null, 'quantity' => 7],
        ]));

        $this->assertSame(4, collect($allocations)->firstWhere('warehouse_id', $this->main->id)['quantity']);
        $this->assertSame(3, collect($allocations)->firstWhere('warehouse_id', $this->branch->id)['quantity']);

        $this->assertSame(0, $this->inventory->level($product->id, null, $this->main->id));
        $this->assertSame(3, $this->inventory->level($product->id, null, $this->branch->id));
    }

    public function test_allocation_fails_when_total_insufficient(): void
    {
        $product = $this->product(2, 3);

        $this->expectException(\InvalidArgumentException::class);

        $this->inventory->allocate(collect([
            ['product_id' => $product->id, 'variant_id' => null, 'quantity' => 10],
        ]));
    }

    public function test_transfer_full_lifecycle(): void
    {
        $product = $this->product(10);

        $transfer = StockTransfer::create([
            'from_warehouse_id' => $this->main->id,
            'to_warehouse_id' => $this->branch->id,
            'status' => StockTransfer::STATUS_PENDING,
            'user_id' => null,
        ]);

        $transfer->items()->create(['product_id' => $product->id, 'variant_id' => null, 'quantity' => 4]);

        // Ship: stock leaves MAIN, branch untouched, TOTAL unchanged.
        $this->inventory->shipTransfer($transfer);

        $this->assertSame(StockTransfer::STATUS_IN_TRANSIT, $transfer->fresh()->status);
        $this->assertSame(6, $this->inventory->level($product->id, null, $this->main->id));
        $this->assertSame(0, $this->inventory->level($product->id, null, $this->branch->id));
        $this->assertSame(10, $product->fresh()->stock); // total unchanged

        // Receive: branch gains, total still unchanged.
        $this->inventory->receiveTransfer($transfer);

        $this->assertSame(StockTransfer::STATUS_RECEIVED, $transfer->fresh()->status);
        $this->assertSame(6, $this->inventory->level($product->id, null, $this->main->id));
        $this->assertSame(4, $this->inventory->level($product->id, null, $this->branch->id));
        $this->assertSame(10, $product->fresh()->stock);

        // Movement ledger: transfer_out + transfer_in recorded.
        $this->assertSame(1, DB::table('stock_movements')->where('type', 'transfer_out')->where('warehouse_id', $this->main->id)->count());
        $this->assertSame(1, DB::table('stock_movements')->where('type', 'transfer_in')->where('warehouse_id', $this->branch->id)->count());
    }

    public function test_ship_insufficient_source_stock_rejected(): void
    {
        $product = $this->product(2);

        $transfer = StockTransfer::create([
            'from_warehouse_id' => $this->main->id,
            'to_warehouse_id' => $this->branch->id,
        ]);

        $transfer->items()->create(['product_id' => $product->id, 'variant_id' => null, 'quantity' => 5]);

        $this->expectException(\InvalidArgumentException::class);

        $this->inventory->shipTransfer($transfer);
    }

    public function test_cancel_pending_transfer_only(): void
    {
        $product = $this->product(10);

        $transfer = StockTransfer::create([
            'from_warehouse_id' => $this->main->id,
            'to_warehouse_id' => $this->branch->id,
        ]);

        $transfer->items()->create(['product_id' => $product->id, 'variant_id' => null, 'quantity' => 4]);

        $this->inventory->cancelTransfer($transfer, 'ubah rencana');

        $this->assertSame(StockTransfer::STATUS_CANCELLED, $transfer->fresh()->status);
        $this->assertSame(10, $this->inventory->level($product->id, null, $this->main->id));

        // Shipped (in transit) transfer cannot be cancelled.
        $transfer2 = StockTransfer::create([
            'from_warehouse_id' => $this->main->id,
            'to_warehouse_id' => $this->branch->id,
        ]);
        $transfer2->items()->create(['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]);
        $this->inventory->shipTransfer($transfer2);

        $this->expectException(\InvalidArgumentException::class);
        $this->inventory->cancelTransfer($transfer2->fresh());
    }
}
