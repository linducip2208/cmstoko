<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\Theme;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class ThemeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
        Theme::flush();
    }

    public function test_default_preset_tokens_render_in_storefront_head(): void
    {
        $response = $this->get('/');

        $response->assertOk();

        $html = $response->getContent();

        $this->assertStringContainsString('--color-paper: #f6f4ef', $html); // editorial default
        $this->assertStringContainsString('--color-accent: #9a4a2b', $html);
    }

    public function test_switching_preset_changes_tokens_immediately(): void
    {
        \App\Support\Settings::set('theme.preset', 'bold', 'appearance');
        Theme::flush();

        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('--color-paper: #16181d', $html); // bold dark preset
        $this->assertStringContainsString('--color-accent: #f5b83d', $html);
    }

    public function test_custom_color_overrides_preset(): void
    {
        \App\Support\Settings::set('theme.custom', ['--color-accent' => '#00ff41'], 'appearance');
        Theme::flush();

        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('--color-accent: #00ff41', $html);
    }

    public function test_theme_page_saves_and_audits(): void
    {
        $admin = User::factory()->create(['role_id' => Role::where('slug', Role::SUPER_ADMIN)->value('id')]);

        $this->actingAs($admin);

        Livewire::test(\App\Filament\Pages\ManageTheme::class)
            ->fillForm(['preset' => 'minimal'])
            ->call('save');

        $this->assertSame('minimal', Theme::activePreset());

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'settings.update',
            'user_id' => $admin->id,
        ]);
    }

    public function test_customer_cannot_access_theme_page(): void
    {
        $customer = User::factory()->create([
            'role_id' => Role::where('slug', Role::CUSTOMER)->value('id'),
        ]);

        $this->actingAs($customer)->get('/admin/manage-theme')->assertForbidden();
    }
}
