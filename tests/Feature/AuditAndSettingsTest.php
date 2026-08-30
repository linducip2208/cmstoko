<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\OrderFulfillmentService;
use App\Support\Csv;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuditAndSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    protected function adminWithRole(string $slug): User
    {
        return User::factory()->create(['role_id' => Role::where('slug', $slug)->value('id')]);
    }

    public function test_settings_page_saves_values_and_writes_audit(): void
    {
        $admin = $this->adminWithRole(Role::SUPER_ADMIN);

        $this->actingAs($admin);

        Livewire::test(\App\Filament\Pages\ManageSettings::class)
            ->fillForm([
                'store.name' => 'Toko Audit',
                'store.tagline' => 'Tagline baru.',
            ])
            ->call('save');

        $this->assertSame('Toko Audit', \App\Support\Settings::get('store.name'));
        $this->assertSame('Tagline baru.', \App\Support\Settings::get('store.tagline'));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'settings.update',
            'user_id' => $admin->id,
        ]);
    }

    public function test_customer_cannot_open_settings_page(): void
    {
        $customer = $this->adminWithRole(Role::CUSTOMER);

        $this->actingAs($customer)
            ->get('/admin/manage-settings')
            ->assertForbidden();
    }

    public function test_inventory_adjust_is_audited(): void
    {
        $admin = $this->adminWithRole(Role::SUPER_ADMIN);
        $this->actingAs($admin);

        $product = Product::create([
            'category_id' => Category::create(['name' => 'Audit Cat '.uniqid()])->id,
            'name' => 'Audit Product '.uniqid(),
            'price' => 50000,
            'stock' => 10,
            'weight' => 100,
            'is_active' => true,
        ]);

        app(InventoryService::class)->adjust($product->id, null, -3, 'stok rusak');

        $log = AuditLog::where('action', 'inventory.adjust')->first();

        $this->assertNotNull($log);
        $this->assertSame(10, $log->before['stock']);
        $this->assertSame(7, $log->after['stock']);
        $this->assertSame('stok rusak', $log->after['note']);
    }

    public function test_refund_is_audited(): void
    {
        $admin = $this->adminWithRole(Role::SUPER_ADMIN);
        $this->actingAs($admin);

        $order = Order::create([
            'customer_name' => 'Refund Tester',
            'customer_email' => 'refund@example.com',
            'customer_phone' => '0811',
            'city_name' => 'Depok',
            'province_name' => 'Jabar',
            'address' => 'Jl. Refund',
            'subtotal' => 100000,
            'discount' => 0,
            'shipping_cost' => 0,
            'total' => 100000,
            'weight' => 100,
            'payment_method' => 'manual_transfer',
        ]);

        $order->transitionTo(Order::STATUS_PAID);

        app(OrderFulfillmentService::class)->refund($order, 40000, 'sebagian rusak');

        $log = AuditLog::where('action', 'refund.create')->first();

        $this->assertNotNull($log);
        $this->assertSame(40000, $log->after['amount']);
    }

    public function test_audit_secrets_are_redacted(): void
    {
        $redacted = \App\Support\Audit::redact([
            'name' => 'safe',
            'password' => 'hunter2',
            'server_key' => 'sk-xyz',
            'nested' => ['token' => 'abc', 'visible' => 'yes'],
        ]);

        $this->assertSame('safe', $redacted['name']);
        $this->assertSame('[redacted]', $redacted['password']);
        $this->assertSame('[redacted]', $redacted['server_key']);
        $this->assertSame('[redacted]', $redacted['nested']['token']);
        $this->assertSame('yes', $redacted['nested']['visible']);
    }

    public function test_audit_viewer_hidden_from_finance_but_open_to_super_admin(): void
    {
        $finance = $this->adminWithRole(Role::FINANCE);
        $super = $this->adminWithRole(Role::SUPER_ADMIN);

        $this->actingAs($finance)->get('/admin/audit-logs')->assertForbidden();
        $this->actingAs($super)->get('/admin/audit-logs')->assertOk();
    }

    public function test_csv_stream_download_produces_bom_and_rows(): void
    {
        $response = Csv::streamDownload('test.csv', ['A', 'B'], [['1', 'x,y'], ['2', 'baris "kutip"']]);

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('A,B', $content);
        $this->assertStringContainsString('"x,y"', $content);
        $this->assertStringContainsString('baris ""kutip""', $content);
    }
}
