<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    protected function seedRbac(): void
    {
        $this->seed(RbacSeeder::class);
    }

    protected function userWithRole(string $slug): User
    {
        return User::factory()->create(['role_id' => Role::where('slug', $slug)->value('id')]);
    }

    public function test_customer_cannot_access_admin_panel(): void
    {
        $this->seedRbac();
        $customer = $this->userWithRole(Role::CUSTOMER);

        $this->actingAs($customer)->get('/admin')->assertForbidden();
    }

    public function test_user_without_role_cannot_access_admin_panel(): void
    {
        $this->seedRbac();
        $plain = User::factory()->create(['role_id' => null]);

        $this->actingAs($plain)->get('/admin')->assertForbidden();
    }

    public function test_super_admin_sees_full_panel(): void
    {
        $this->seedRbac();
        $admin = $this->userWithRole(Role::SUPER_ADMIN);

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->actingAs($admin)->get('/admin/roles')->assertOk();
        $this->actingAs($admin)->get('/admin/products')->assertOk();
    }

    public function test_catalog_manager_blocked_from_users_and_settings(): void
    {
        $this->seedRbac();
        $manager = $this->userWithRole(Role::CATALOG_MANAGER);

        $this->actingAs($manager)->get('/admin')->assertOk();
        $this->actingAs($manager)->get('/admin/products')->assertOk();
        $this->actingAs($manager)->get('/admin/categories')->assertOk();
        $this->actingAs($manager)->get('/admin/users')->assertForbidden();
        $this->actingAs($manager)->get('/admin/roles')->assertForbidden();
    }

    public function test_order_staff_can_view_but_not_create_products(): void
    {
        $this->seedRbac();
        $staff = $this->userWithRole(Role::ORDER_STAFF);

        $this->actingAs($staff)->get('/admin/orders')->assertOk();
        $this->actingAs($staff)->get('/admin/products')->assertForbidden();
    }

    public function test_content_editor_cannot_manage_products(): void
    {
        $this->seedRbac();
        $editor = $this->userWithRole(Role::CONTENT_EDITOR);

        // Products list is forbidden because editor lacks products.view.
        $this->actingAs($editor)->get('/admin/products')->assertForbidden();
        // Blog/CMS resources arrive in B7; reports page is visible to editor? No — reports.view not granted.
        $this->actingAs($editor)->get('/admin/sales-report')->assertForbidden();
    }

    public function test_gate_reflects_permissions(): void
    {
        $this->seedRbac();

        $catalog = $this->userWithRole(Role::CATALOG_MANAGER);
        $this->assertTrue($catalog->hasPermission('products.view'));
        $this->assertTrue($catalog->hasPermission('products.create'));
        $this->assertFalse($catalog->hasPermission('orders.refund'));
        $this->assertFalse($catalog->hasPermission('users.create'));

        $super = $this->userWithRole(Role::SUPER_ADMIN);
        $this->assertTrue($super->hasPermission('anything.at.all'));

        $customer = $this->userWithRole(Role::CUSTOMER);
        $this->assertFalse($customer->hasPermission('products.view'));
    }

    public function test_customer_role_has_no_permissions(): void
    {
        $this->seedRbac();

        $customer = $this->userWithRole(Role::CUSTOMER);

        $this->assertSame(0, $customer->role->permissions()->count());
    }
}
