<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageSettings;
use App\Models\Role;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_no_analytics_scripts_without_ids(): void
    {
        $html = $this->get('/')->getContent();

        $this->assertStringNotContainsString('googletagmanager', $html);
        $this->assertStringNotContainsString('connect.facebook.net', $html);
    }

    public function test_ga4_script_renders_when_configured(): void
    {
        Settings::set('analytics.ga4_id', 'G-TEST123', 'seo');

        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('googletagmanager.com/gtag/js?id=G-TEST123', $html);
        $this->assertStringContainsString('G-TEST123', $html);
    }

    public function test_meta_and_tiktok_pixels_render_when_configured(): void
    {
        Settings::set('analytics.meta_pixel_id', '1234567890', 'seo');
        Settings::set('analytics.tiktok_pixel_id', 'TIKTOKID', 'seo');

        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('fbq(\'init\', "1234567890")', $html);
        $this->assertStringContainsString('ttq.load', $html);
        $this->assertStringContainsString('TIKTOKID', $html);
    }

    public function test_gtm_script_renders_when_configured(): void
    {
        Settings::set('analytics.gtm_id', 'GTM-ABC123', 'seo');

        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('GTM-ABC123', $html);
    }

    public function test_invalid_ga4_id_rejected_by_validation(): void
    {
        $admin = User::factory()->create(['role_id' => Role::where('slug', Role::SUPER_ADMIN)->value('id')]);

        $this->actingAs($admin);

        Livewire::test(ManageSettings::class)
            ->fillForm(['analytics.ga4_id' => 'G-WRONG!'])
            ->call('save');

        // Invalid ID (bad format) must not be persisted.
        $this->assertNull(Settings::get('analytics.ga4_id'));
    }
}
