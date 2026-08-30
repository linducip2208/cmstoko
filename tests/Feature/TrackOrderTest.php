<?php

namespace Tests\Feature;

use App\Livewire\TrackOrder;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TrackOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    protected function order(string $email = 'buyer@example.com', ?User $user = null): Order
    {
        $product = Product::create([
            'category_id' => Category::create(['name' => 'Track '.uniqid()])->id,
            'name' => 'Track Product '.uniqid(),
            'price' => 100000,
            'stock' => 10,
            'weight' => 300,
            'is_active' => true,
        ]);

        return Order::create([
            'user_id' => $user?->id,
            'customer_name' => 'Track Tester',
            'customer_email' => $email,
            'customer_phone' => '081200000000',
            'province_name' => 'DKI Jakarta',
            'city_name' => 'Jakarta Selatan',
            'address' => 'Jl. Track No. 1',
            'subtotal' => 100000,
            'discount' => 0,
            'shipping_cost' => 20000,
            'total' => 120000,
            'weight' => 300,
            'payment_method' => 'manual_transfer',
        ]);
    }

    public function test_guest_can_track_own_order_with_matching_email(): void
    {
        $order = $this->order();

        Livewire::test(TrackOrder::class)
            ->set('number', $order->order_number)
            ->set('email', 'buyer@example.com')
            ->call('search')
            ->assertSet('searched', true)
            ->assertSet('order', fn ($order) => $order !== null);
    }

    public function test_guest_cannot_track_order_with_wrong_email(): void
    {
        $order = $this->order();

        Livewire::test(TrackOrder::class)
            ->set('number', $order->order_number)
            ->set('email', 'thief@example.com')
            ->call('search')
            ->assertSet('order', null);

        // Case-insensitive match still works.
        Livewire::test(TrackOrder::class)
            ->set('number', strtolower($order->order_number))
            ->set('email', 'BUYER@example.com')
            ->call('search')
            ->assertSet('order', fn ($order) => $order !== null);
    }

    public function test_guest_requires_email_to_track(): void
    {
        $order = $this->order();

        Livewire::test(TrackOrder::class)
            ->set('number', $order->order_number)
            ->call('search')
            ->assertHasErrors(['email']);
    }

    public function test_customer_can_track_own_order_without_email(): void
    {
        $customer = User::factory()->create();
        $order = $this->order(user: $customer);

        Livewire::actingAs($customer)
            ->test(TrackOrder::class)
            ->set('number', $order->order_number)
            ->call('search')
            ->assertSet('order', fn ($order) => $order !== null);
    }

    public function test_customer_cannot_track_other_customers_order_by_number(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $order = $this->order(user: $owner);

        Livewire::actingAs($stranger)
            ->test(TrackOrder::class)
            ->set('number', $order->order_number)
            ->call('search')
            ->assertSet('order', null);
    }
}
