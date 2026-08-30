<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_requires_authentication(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_admin_dashboard_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin')
            ->assertSuccessful()
            ->assertSee('TokoKita');
    }

    public function test_sales_report_page_renders(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin/sales-report')
            ->assertSuccessful();
    }

    public function test_product_resource_renders(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin/products')
            ->assertSuccessful();
    }

    public function test_order_resource_renders(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin/orders')
            ->assertSuccessful();
    }
}
