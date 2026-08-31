<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\TaxClass;
use App\Models\TaxRate;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    protected function superAdmin(): User
    {
        return User::factory()->create([
            'role_id' => Role::where('slug', Role::SUPER_ADMIN)->value('id'),
        ]);
    }

    protected function product(int $stock = 5, int $price = 100000): Product
    {
        return Product::create([
            'category_id' => Category::create(['name' => 'Rep '.uniqid()])->id,
            'name' => 'Report Product '.uniqid(),
            'price' => $price,
            'stock' => $stock,
            'weight' => 100,
            'is_active' => true,
        ]);
    }

    public function test_sales_report_page_renders_with_permission(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/admin/sales-report')
            ->assertOk();
    }

    public function test_inventory_report_shows_low_stock_and_value(): void
    {
        $this->product(stock: 0);
        $this->product(stock: 3, price: 200000);

        $html = $this->actingAs($this->superAdmin())
            ->get('/admin/inventory-report')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Stok Habis', $html);
        $this->assertStringContainsString('Nilai Stok', $html);
    }

    public function test_tax_report_aggregates_from_snapshots(): void
    {
        $class = TaxClass::create(['name' => 'PPN', 'is_default' => true]);
        TaxRate::create(['tax_class_id' => $class->id, 'name' => 'PPN 11%', 'rate_bp' => 1100, 'type' => 'exclusive']);

        $warehouse = Warehouse::create(['name' => 'MAIN', 'code' => 'MAIN2', 'is_default' => true, 'is_active' => true]);

        $order = Order::create([
            'customer_name' => 'Tax Reporter',
            'customer_email' => 'rep@example.com',
            'customer_phone' => '0812',
            'city_name' => 'Depok',
            'province_name' => 'Jabar',
            'address' => 'Jl',
            'subtotal' => 100000,
            'discount' => 0,
            'rule_discount' => 0,
            'tax_amount' => 11000,
            'tax_snapshot' => [['name' => 'PPN 11%', 'rate_bp' => 1100, 'type' => 'exclusive', 'amount' => 11000]],
            'shipping_cost' => 0,
            'total' => 111000,
            'weight' => 100,
            'payment_method' => 'manual_transfer',
            'status' => Order::STATUS_PAID,
        ]);

        $order->items()->create([
            'product_id' => $this->product()->id,
            'product_name' => 'X',
            'price' => 100000,
            'quantity' => 1,
            'subtotal' => 100000,
        ]);

        $html = $this->actingAs($this->superAdmin())
            ->get('/admin/tax-report')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('PPN 11%', $html);
        $this->assertStringContainsString('Rp 11.000', $html);
    }

    public function test_pending_orders_excluded_from_tax_report(): void
    {
        $order = Order::create([
            'customer_name' => 'Pending',
            'customer_email' => 'p@example.com',
            'customer_phone' => '0812',
            'city_name' => 'Depok',
            'province_name' => 'Jabar',
            'address' => 'Jl',
            'subtotal' => 100000,
            'discount' => 0,
            'rule_discount' => 0,
            'tax_amount' => 11000,
            'tax_snapshot' => [['name' => 'PPN 11%', 'amount' => 11000]],
            'shipping_cost' => 0,
            'total' => 111000,
            'weight' => 100,
            'payment_method' => 'manual_transfer',
            'status' => Order::STATUS_PENDING, // NOT paid — must not count
        ]);

        $order->items()->create([
            'product_id' => $this->product()->id,
            'product_name' => 'X',
            'price' => 100000,
            'quantity' => 1,
            'subtotal' => 100000,
        ]);

        $html = $this->actingAs($this->superAdmin())
            ->get('/admin/tax-report')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Rp 0', $html);
    }

    public function test_reports_hidden_without_permission(): void
    {
        $customer = User::factory()->create([
            'role_id' => Role::where('slug', Role::CUSTOMER)->value('id'),
        ]);

        $this->actingAs($customer)
            ->get('/admin/inventory-report')
            ->assertForbidden();

        $this->actingAs($customer)
            ->get('/admin/tax-report')
            ->assertForbidden();

        $this->actingAs($customer)
            ->get('/admin/customers-report')
            ->assertForbidden();
    }
}
