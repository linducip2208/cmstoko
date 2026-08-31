<?php

namespace Tests\Feature;

use App\Livewire\CheckoutPage;
use App\Models\CartRule;
use App\Models\Category;
use App\Models\CustomerGroup;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CustomerGroupSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;
use Tests\TestCase;

class CartRuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
        $this->seed(CustomerGroupSeeder::class);
        config(['shop.midtrans.server_key' => null]);
    }

    protected function product(int $price = 100000, ?int $categoryId = null, ?int $brandId = null): Product
    {
        return Product::create([
            'category_id' => $categoryId ?? Category::create(['name' => 'Rule Cat '.uniqid()])->id,
            'brand_id' => $brandId,
            'name' => 'Rule Product '.uniqid(),
            'price' => $price,
            'stock' => 20,
            'weight' => 100,
            'is_active' => true,
        ]);
    }

    protected function customer(?string $groupSlug = null): User
    {
        $user = User::factory()->create([
            'role_id' => Role::where('slug', Role::CUSTOMER)->value('id'),
        ]);

        if ($groupSlug) {
            $user->update(['customer_group_id' => CustomerGroup::where('slug', $groupSlug)->value('id')]);
        }

        return $user;
    }

    protected function rule(array $attributes): CartRule
    {
        return CartRule::create(array_merge([
            'name' => 'Rule '.uniqid(),
            'action_type' => CartRule::ACTION_PERCENT,
            'action_value' => 10,
            'is_active' => true,
        ], $attributes));
    }

    public function test_percent_rule_discounts_subtotal(): void
    {
        $product = $this->product();
        $this->rule(['action_type' => CartRule::ACTION_PERCENT, 'action_value' => 25]);

        Session::put('shop.cart', [$product->id => 2]); // 200.000

        $user = $this->customer();

        Livewire::actingAs($user)->test(CheckoutPage::class)->fill([
            'customer_name' => 'Rule Buyer',
            'customer_email' => $user->email,
            'customer_phone' => '0812',
            'city_name_manual' => 'Depok',
            'province_name_manual' => 'Jabar',
            'address' => 'Jl. Rule',
            'service' => '',
        ])->call('placeOrder')->assertHasNoErrors();

        $order = Order::where('user_id', $user->id)->first();

        $this->assertSame(200000, $order->subtotal);
        $this->assertSame(50000, $order->rule_discount);
        $this->assertSame(170000, $order->total); // 200k - 50k + 20k ongkir
    }

    public function test_free_shipping_rule_zeros_shipping_cost(): void
    {
        $product = $this->product();
        $this->rule(['action_type' => CartRule::ACTION_FREE_SHIPPING]);

        Session::put('shop.cart', [$product->id => 1]);

        $user = $this->customer();

        Livewire::actingAs($user)->test(CheckoutPage::class)->fill([
            'customer_name' => 'Free Ship',
            'customer_email' => $user->email,
            'customer_phone' => '0812',
            'city_name_manual' => 'Depok',
            'province_name_manual' => 'Jabar',
            'address' => 'Jl. Rule',
            'service' => '',
        ])->call('placeOrder')->assertHasNoErrors();

        $order = Order::where('user_id', $user->id)->first();

        $this->assertSame(0, $order->shipping_cost);
        $this->assertSame(100000, $order->total);
        $this->assertTrue(collect($order->applied_rules)->first()['free_shipping']);
    }

    public function test_group_targeting_excludes_other_groups_and_guests(): void
    {
        $product = $this->product();
        $this->rule([
            'action_type' => CartRule::ACTION_PERCENT,
            'action_value' => 50,
            'customer_group_id' => CustomerGroup::where('slug', CustomerGroup::SLUG_VIP)->value('id'),
        ]);

        // VIP member gets the discount.
        Session::put('shop.cart', [$product->id => 1]);
        $vip = $this->customer(CustomerGroup::SLUG_VIP);

        Livewire::actingAs($vip)->test(CheckoutPage::class)->fill([
            'customer_name' => 'VIP Buyer',
            'customer_email' => $vip->email,
            'customer_phone' => '0812',
            'city_name_manual' => 'Depok',
            'province_name_manual' => 'Jabar',
            'address' => 'Jl. Rule',
            'service' => '',
        ])->call('placeOrder')->assertHasNoErrors();

        $vipOrder = Order::where('user_id', $vip->id)->first();
        $this->assertSame(50000, $vipOrder->rule_discount);

        // Retail member does not.
        Session::put('shop.cart', [$product->id => 1]);
        $retail = $this->customer(CustomerGroup::SLUG_RETAIL);

        Livewire::actingAs($retail)->test(CheckoutPage::class)->fill([
            'customer_name' => 'Retail Buyer',
            'customer_email' => $retail->email,
            'customer_phone' => '0812',
            'city_name_manual' => 'Depok',
            'province_name_manual' => 'Jabar',
            'address' => 'Jl. Rule',
            'service' => '',
        ])->call('placeOrder')->assertHasNoErrors();

        $retailOrder = Order::where('user_id', $retail->id)->first();
        $this->assertSame(0, $retailOrder->rule_discount);
    }

    public function test_category_condition_matches_descendants_only(): void
    {
        $parent = Category::create(['name' => 'Parent Rule']);
        $child = Category::create(['name' => 'Child Rule', 'parent_id' => $parent->id]);

        $inChild = $this->product(categoryId: $child->id);
        $other = $this->product();

        $this->rule([
            'action_type' => CartRule::ACTION_FIXED,
            'action_value' => 20000,
            'conditions' => ['category_ids' => [$parent->id]],
        ]);

        // Product in child category matches the parent rule.
        $result = CartRule::evaluate(
            collect([['product' => $inChild, 'qty' => 1, 'price' => $inChild->price]]),
            100000,
            null,
        );

        $this->assertSame(20000, $result['discount']);

        // Product outside the tree does not.
        $miss = CartRule::evaluate(
            collect([['product' => $other, 'qty' => 1, 'price' => $other->price]]),
            100000,
            null,
        );

        $this->assertSame(0, $miss['discount']);
    }

    public function test_discount_is_capped_at_subtotal(): void
    {
        $product = $this->product();
        $this->rule(['action_type' => CartRule::ACTION_FIXED, 'action_value' => 500000]);

        $result = CartRule::evaluate(
            collect([['product' => $product, 'qty' => 1, 'price' => $product->price]]),
            100000,
            null,
        );

        $this->assertSame(100000, $result['discount']);
    }

    public function test_expired_rule_ignored(): void
    {
        $product = $this->product();
        $this->rule(['action_type' => CartRule::ACTION_PERCENT, 'action_value' => 30, 'ends_at' => now()->subHour()]);

        $result = CartRule::evaluate(
            collect([['product' => $product, 'qty' => 1, 'price' => $product->price]]),
            100000,
            null,
        );

        $this->assertSame(0, $result['discount']);
    }

    public function test_usage_limit_is_respected(): void
    {
        $product = $this->product();
        $rule = $this->rule(['action_type' => CartRule::ACTION_PERCENT, 'action_value' => 10, 'usage_limit' => 1]);

        CartRule::consume([$rule->id]);

        $result = CartRule::evaluate(
            collect([['product' => $product, 'qty' => 1, 'price' => $product->price]]),
            100000,
            null,
        );

        $this->assertSame(0, $result['discount']);
    }
}
