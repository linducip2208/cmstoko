<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\Wishlist;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    protected function customer(): User
    {
        return User::factory()->create(['role_id' => Role::where('slug', Role::CUSTOMER)->value('id')]);
    }

    /**
     * Sanctum resolves the guard once per app instance; in-process test requests
     * share it, so reset between requests that use different tokens.
     */
    protected function resetAuth(): void
    {
        $this->app->make('auth')->forgetGuards();
        $this->app->forgetInstance('auth');
    }

    protected function product(): Product
    {
        return Product::create([
            'category_id' => Category::create(['name' => 'Api Cat '.uniqid()])->id,
            'brand_id' => Brand::create(['name' => 'Api Brand '.uniqid(), 'slug' => 'api-brand-'.uniqid(), 'is_active' => true])->id,
            'name' => 'Api Product '.uniqid(),
            'slug' => 'api-product-'.uniqid(),
            'price' => 150000,
            'stock' => 5,
            'weight' => 100,
            'is_active' => true,
        ]);
    }

    public function test_public_catalog_endpoints(): void
    {
        $product = $this->product();

        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonPath('data.0.name', $product->name)
            ->assertJsonStructure(['data', 'links', 'meta']);

        $this->getJson('/api/v1/products/'.$product->slug)
            ->assertOk()
            ->assertJsonPath('data.slug', $product->slug)
            ->assertJsonPath('data.in_stock', true);

        $this->getJson('/api/v1/categories')->assertOk();
        $this->getJson('/api/v1/brands')->assertOk();
        $this->getJson('/api/v1/collections')->assertOk();
    }

    public function test_inactive_products_hidden_from_api(): void
    {
        $product = Product::create([
            'category_id' => Category::create(['name' => 'Api Cat '.uniqid()])->id,
            'name' => 'Hidden Product',
            'slug' => 'hidden-product',
            'price' => 50000,
            'stock' => 1,
            'is_active' => false,
        ]);

        $this->getJson('/api/v1/products/hidden-product')->assertNotFound();
    }

    public function test_token_issue_with_valid_credentials(): void
    {
        $user = $this->customer();

        $this->postJson('/api/v1/auth/token', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'test',
        ])
            ->assertStatus(201)
            ->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'name', 'email']]);
    }

    public function test_token_issue_rejects_bad_credentials_and_is_rate_limited(): void
    {
        $user = $this->customer();

        // Throttle:5,1 — the first 5 attempts are validated (422), the 6th is throttled.
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/token', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertStatus(422);
        }

        $this->postJson('/api/v1/auth/token', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertStatus(429);
    }

    public function test_authenticated_endpoints_require_token(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
        $this->getJson('/api/v1/orders')->assertUnauthorized();
        $this->getJson('/api/v1/wishlist')->assertUnauthorized();
    }

    public function test_me_orders_and_wishlist_with_token(): void
    {
        $user = $this->customer();
        $product = $this->product();

        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);

        $this->withToken($token)->getJson('/api/v1/orders')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->withToken($token)->postJson('/api/v1/wishlist', ['product_id' => $product->id])
            ->assertOk()
            ->assertJsonPath('data.wishlisted', true);

        $this->assertDatabaseHas('wishlists', ['user_id' => $user->id, 'product_id' => $product->id]);

        $this->withToken($token)->getJson('/api/v1/wishlist')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_order_api_enforces_ownership_no_idor(): void
    {
        $owner = $this->customer();
        $stranger = $this->customer();

        $order = Order::create([
            'user_id' => $owner->id,
            'customer_name' => 'Owner',
            'customer_email' => $owner->email,
            'customer_phone' => '0812',
            'city_name' => 'Depok',
            'province_name' => 'Jabar',
            'address' => 'Jl. Api',
            'subtotal' => 100000,
            'discount' => 0,
            'shipping_cost' => 0,
            'total' => 100000,
            'weight' => 100,
            'payment_method' => 'manual_transfer',
        ]);

        $this->withToken($owner->createToken('t')->plainTextToken)
            ->getJson('/api/v1/orders/'.$order->order_number)
            ->assertOk()
            ->assertJsonPath('data.order_number', $order->order_number);

        $this->resetAuth();

        // Stranger cannot read the owner's order.
        $this->withToken($stranger->createToken('t')->plainTextToken)
            ->getJson('/api/v1/orders/'.$order->order_number)
            ->assertNotFound();
    }

    public function test_order_api_exposes_no_internal_notes_or_secrets(): void
    {
        $user = $this->customer();

        $order = Order::create([
            'user_id' => $user->id,
            'customer_name' => 'Owner',
            'customer_email' => $user->email,
            'customer_phone' => '0812',
            'city_name' => 'Depok',
            'province_name' => 'Jabar',
            'address' => 'Jl. Api',
            'subtotal' => 100000,
            'discount' => 0,
            'shipping_cost' => 0,
            'total' => 100000,
            'weight' => 100,
            'payment_method' => 'manual_transfer',
        ]);

        $order->notes()->create(['body' => 'INTERNAL-SECRET-NOTE', 'user_id' => null, 'is_internal' => true]);

        $response = $this->withToken($user->createToken('t')->plainTextToken)
            ->getJson('/api/v1/orders/'.$order->order_number);

        $response->assertOk();
        $this->assertStringNotContainsString('INTERNAL-SECRET-NOTE', $response->getContent());
    }

    public function test_token_revoke(): void
    {
        $user = $this->customer();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->deleteJson('/api/v1/auth/token')->assertOk();

        $this->resetAuth();

        $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();
    }
}
