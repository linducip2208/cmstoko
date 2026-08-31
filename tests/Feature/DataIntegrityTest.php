<?php

namespace Tests\Feature;

use App\Livewire\CheckoutPage;
use App\Models\CartRule;
use App\Models\Category;
use App\Models\CustomerGroup;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\TaxClass;
use App\Models\TaxRate;
use App\Models\User;
use Database\Seeders\CustomerGroupSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;
use Tests\TestCase;

class DataIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
        $this->seed(CustomerGroupSeeder::class);
    }

    // ---------- Registration ----------

    public function test_registration_stores_exact_fields_and_defaults(): void
    {
        $this->post('/daftar', [
            'name' => 'Integritas Tester',
            'email' => 'integritas@example.com',
            'phone' => '081299999999',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect();

        $user = User::where('email', 'integritas@example.com')->first();

        // Exactly ONE row, correct role + default retail group, hashed password.
        $this->assertSame(1, User::where('email', 'integritas@example.com')->count());
        $this->assertSame('Integritas Tester', $user->name);
        $this->assertSame('081299999999', $user->phone);
        $this->assertSame(CustomerGroup::SLUG_RETAIL, $user->customerGroup->slug);
        $this->assertSame('customer', $user->role->slug);
        $this->assertNotSame('password123', $user->password);
    }

    public function test_registration_rejects_extra_fields(): void
    {
        // Mass-assignment probe: is_admin / role hijack must be ignored.
        $this->post('/daftar', [
            'name' => 'Sneaky',
            'email' => 'sneaky@example.com',
            'phone' => '081200000000',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => Role::where('slug', Role::SUPER_ADMIN)->value('id'),
            'customer_group_id' => CustomerGroup::where('slug', CustomerGroup::SLUG_VIP)->value('id'),
        ])->assertRedirect();

        $user = User::where('email', 'sneaky@example.com')->first();

        $this->assertSame('customer', $user->role->slug); // forced, never admin
        $this->assertSame(CustomerGroup::SLUG_RETAIL, $user->customerGroup->slug); // forced retail
    }

    // ---------- Tax classes / rates round-trip ----------

    public function test_only_one_default_tax_class(): void
    {
        $a = TaxClass::create(['name' => 'PPN', 'is_default' => true]);
        $b = TaxClass::create(['name' => 'Luxury Tax', 'is_default' => true]);

        $this->assertFalse($a->fresh()->is_default);
        $this->assertTrue($b->fresh()->is_default);

        $b->update(['is_default' => false]);
        $c = TaxClass::create(['name' => 'Bebas', 'is_default' => false]);
        $this->assertSame(0, TaxClass::where('is_default', true)->count());
    }

    public function test_tax_rate_stores_basis_points_exactly(): void
    {
        $class = TaxClass::create(['name' => 'PPN', 'is_default' => true]);

        $rate = TaxRate::create([
            'tax_class_id' => $class->id,
            'name' => 'PPN 11%',
            'rate_bp' => 1100,
            'type' => 'exclusive',
        ]);

        $this->assertSame(1100, $rate->rate_bp);
        $this->assertSame(1100, TaxRate::activeMap()[0]['rate_bp']);
    }

    // ---------- Cart rules ----------

    public function test_cart_rule_conditions_round_trip(): void
    {
        $rule = CartRule::create([
            'name' => 'Rule JSON',
            'action_type' => 'fixed',
            'action_value' => 15000,
            'conditions' => ['min_subtotal' => 500000, 'product_ids' => [3, 7], 'category_ids' => [1]],
        ]);

        $fresh = $rule->fresh();

        $this->assertSame(500000, $fresh->conditions['min_subtotal']);
        $this->assertSame([3, 7], array_map('intval', $fresh->conditions['product_ids']));
        $this->assertSame(15000, $fresh->action_value);
    }

    // ---------- Orders ----------

    public function test_order_persist_exact_rows_and_no_duplicates(): void
    {
        $product = Product::create([
            'category_id' => Category::create(['name' => 'Cat '.uniqid()])->id,
            'name' => 'Order Product',
            'price' => 150000,
            'stock' => 5,
            'weight' => 100,
            'is_active' => true,
        ]);

        Session::put('shop.cart', [$product->id => 1]); // 150000 — below free-shipping min

        Livewire::test(CheckoutPage::class)->fill([
            'customer_name' => 'Order Integrity',
            'customer_email' => 'order@example.com',
            'customer_phone' => '0812',
            'city_name_manual' => 'Depok',
            'province_name_manual' => 'Jabar',
            'address' => 'Jl. Integrity',
            'service' => '',
        ])->call('placeOrder')->assertHasNoErrors();

        // Exactly one order, one item, one history row, one stock movement.
        $this->assertSame(1, Order::count());
        $order = Order::first();
        $this->assertSame(1, $order->items()->count());
        $this->assertSame(1, $order->histories()->count());

        // Totals are exact — no hidden additions.
        $this->assertSame(150000, $order->subtotal);
        $this->assertSame(0, $order->discount);
        $this->assertSame(0, $order->rule_discount);
        $this->assertSame(0, $order->tax_amount);
        $this->assertSame(20000, $order->shipping_cost);
        $this->assertSame(170000, $order->total);

        // Stock deducted exactly once: 5 - 1 = 4, and ONE ledger row.
        $this->assertSame(4, $product->fresh()->stock);
        $this->assertSame(1, StockMovement::where('product_id', $product->id)->count());
        $this->assertSame(-1, StockMovement::where('product_id', $product->id)->value('quantity'));
    }

    // ---------- Menus ----------

    public function test_menu_items_persist_without_ghost_rows(): void
    {
        $menu = Menu::create(['name' => 'Main', 'slug' => 'main-ghost', 'location' => 'header', 'is_active' => true]);

        $parent = MenuItem::create(['menu_id' => $menu->id, 'label' => 'Katalog', 'target_type' => 'custom', 'url' => '/produk', 'sort_order' => 1]);
        MenuItem::create(['menu_id' => $menu->id, 'parent_id' => $parent->id, 'label' => 'Anak', 'target_type' => 'custom', 'url' => '/produk?sort=new', 'sort_order' => 1]);

        $this->assertSame(2, MenuItem::where('menu_id', $menu->id)->count());
        $this->assertSame(1, MenuItem::where('menu_id', $menu->id)->whereNull('parent_id')->count());
        $this->assertNull($parent->fresh()->parent_id);
    }

    // ---------- Newsletter ----------

    public function test_newsletter_re_subscribe_creates_no_duplicate(): void
    {
        foreach ([1, 2, 3] as $i) {
            $this->post('/newsletter', ['email' => 'dupe@example.com'])->assertRedirect();
        }

        $this->assertSame(1, NewsletterSubscriber::where('email', 'dupe@example.com')->count());
    }

    // ---------- Tax round-trip through checkout ----------

    public function test_checkout_with_tax_persists_exact_snapshot(): void
    {
        $class = TaxClass::create(['name' => 'PPN', 'is_default' => true]);
        TaxRate::create(['tax_class_id' => $class->id, 'name' => 'PPN 11%', 'rate_bp' => 1100, 'type' => 'exclusive']);

        $product = Product::create([
            'category_id' => Category::create(['name' => 'Cat '.uniqid()])->id,
            'name' => 'Taxed Product',
            'price' => 100000,
            'stock' => 5,
            'weight' => 100,
            'is_active' => true,
        ]);

        Session::put('shop.cart', [$product->id => 1]);

        Livewire::test(CheckoutPage::class)->fill([
            'customer_name' => 'Tax Buyer',
            'customer_email' => 'tax@example.com',
            'customer_phone' => '0812',
            'city_name_manual' => 'Depok',
            'province_name_manual' => 'Jabar',
            'address' => 'Jl. Tax',
            'service' => '',
        ])->call('placeOrder')->assertHasNoErrors();

        $order = Order::first();

        $this->assertSame(11000, $order->tax_amount); // exact 11% of 100000
        $this->assertSame(131000, $order->total); // 100000 + 20000 ongkir + 11000 pajak
        $this->assertSame('PPN 11%', $order->tax_snapshot[0]['name']);
        $this->assertSame(11000, $order->tax_snapshot[0]['amount']);
    }
}
