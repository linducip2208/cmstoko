<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\CmsPage;
use App\Models\Menu;
use App\Models\MenuItem;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_header_falls_back_to_categories_without_menu(): void
    {
        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('Katalog', $html);
    }

    public function test_header_menu_replaces_default_navigation(): void
    {
        $menu = Menu::create(['name' => 'Main', 'slug' => 'main', 'location' => 'header', 'is_active' => true]);

        MenuItem::create(['menu_id' => $menu->id, 'label' => 'Promo Spesial', 'target_type' => 'custom', 'url' => '/produk?sort=discount', 'sort_order' => 1]);

        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('Promo Spesial', $html);
    }

    public function test_menu_items_resolve_entity_targets_and_skip_removed(): void
    {
        $menu = Menu::create(['name' => 'Footer', 'slug' => 'footer-menu', 'location' => 'footer', 'is_active' => true]);

        $page = CmsPage::create(['title' => 'Tentang Kami', 'slug' => 'tentang-kami', 'content' => 'x', 'status' => 'published']);

        MenuItem::create(['menu_id' => $menu->id, 'label' => 'Tentang', 'target_type' => 'page', 'target_id' => $page->id, 'sort_order' => 1]);

        // Target that does not exist must be skipped, not rendered as a broken link.
        MenuItem::create(['menu_id' => $menu->id, 'label' => 'Merek Hilang', 'target_type' => 'brand', 'target_id' => 999999, 'sort_order' => 2]);

        MenuItem::create(['menu_id' => $menu->id, 'label' => 'Eksternal Blokir', 'target_type' => 'custom', 'url' => 'https://evil.example.com', 'sort_order' => 3]);

        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('Tentang', $html);
        $this->assertStringContainsString('/halaman/tentang-kami', $html);
        $this->assertStringNotContainsString('Merek Hilang', $html);
        $this->assertStringNotContainsString('evil.example.com', $html);
    }

    public function test_nested_menu_children_render_in_dropdown(): void
    {
        $menu = Menu::create(['name' => 'Main', 'slug' => 'main-2', 'location' => 'header', 'is_active' => true]);

        $parent = MenuItem::create(['menu_id' => $menu->id, 'label' => 'Belanja', 'target_type' => 'custom', 'url' => '/produk', 'sort_order' => 1]);

        $category = \App\Models\Category::create(['name' => 'Menu Cat '.uniqid(), 'slug' => 'menu-cat-'.uniqid(), 'is_active' => true]);

        MenuItem::create(['menu_id' => $menu->id, 'parent_id' => $parent->id, 'label' => $category->name, 'target_type' => 'category', 'target_id' => $category->id, 'sort_order' => 1]);

        $html = $this->get('/')->getContent();

        $this->assertStringContainsString($category->name, $html);
    }

    public function test_inactive_menu_is_ignored(): void
    {
        Menu::create(['name' => 'Off', 'slug' => 'off', 'location' => 'header', 'is_active' => false]);

        MenuItem::create(['menu_id' => Menu::where('slug', 'off')->value('id'), 'label' => 'UNIQUE-INACTIVE-ITEM', 'target_type' => 'custom', 'url' => '/produk']);

        $html = $this->get('/')->getContent();

        $this->assertStringNotContainsString('UNIQUE-INACTIVE-ITEM', $html);
    }
}
