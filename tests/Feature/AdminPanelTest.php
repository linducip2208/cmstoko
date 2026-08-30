<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    protected function staffUser(string $roleSlug = Role::SUPER_ADMIN): User
    {
        return User::factory()->create(['role_id' => Role::where('slug', $roleSlug)->value('id')]);
    }

    public function test_admin_dashboard_requires_authentication(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_admin_dashboard_renders_for_authenticated_user(): void
    {
        $user = $this->staffUser();

        $this->actingAs($user)
            ->get('/admin')
            ->assertSuccessful();
    }

    public function test_sales_report_page_renders(): void
    {
        $user = $this->staffUser(Role::SUPER_ADMIN);

        $this->actingAs($user)
            ->get('/admin/sales-report')
            ->assertSuccessful();
    }

    public function test_sales_report_denied_without_permission(): void
    {
        $user = $this->staffUser(Role::ORDER_STAFF);

        $this->actingAs($user)
            ->get('/admin/sales-report')
            ->assertForbidden();
    }

    public function test_product_resource_renders(): void
    {
        $user = $this->staffUser(Role::CATALOG_MANAGER);

        $this->actingAs($user)
            ->get('/admin/products')
            ->assertSuccessful();
    }

    public function test_order_resource_renders(): void
    {
        $user = $this->staffUser(Role::ORDER_STAFF);

        $this->actingAs($user)
            ->get('/admin/orders')
            ->assertSuccessful();
    }

    public function test_customer_role_cannot_reach_panel(): void
    {
        $user = $this->staffUser(Role::CUSTOMER);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }
}
