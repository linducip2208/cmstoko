<?php

namespace Tests\Feature;

use App\Contracts\SearchEngine;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    protected function product(string $name, ?Category $category = null, ?Brand $brand = null): Product
    {
        return Product::create([
            'category_id' => $category?->id ?? Category::create(['name' => 'Search Cat '.uniqid()])->id,
            'brand_id' => $brand?->id,
            'name' => $name,
            'price' => 100000,
            'stock' => 5,
            'weight' => 100,
            'is_active' => true,
        ]);
    }

    public function test_database_driver_finds_products_by_name_and_sku(): void
    {
        $engine = app(SearchEngine::class);
        $this->assertInstanceOf(\App\Services\DatabaseSearchEngine::class, $engine);

        $this->product('Kabel USB-C Unik', brand: Brand::create(['name' => 'Kabelku', 'slug' => 'kabelku-'.uniqid(), 'is_active' => true]));

        $hits = $engine->products('USB-C')->get();
        $this->assertCount(1, $hits);

        $suggest = $engine->suggest('Kabel');
        $this->assertGreaterThanOrEqual(1, $suggest['products']->count());
        $this->assertGreaterThanOrEqual(1, $suggest['brands']->count());
    }

    public function test_inactive_products_never_suggested(): void
    {
        $product = Product::create([
            'category_id' => Category::create(['name' => 'Cat '.uniqid()])->id,
            'name' => 'Produk Tersembunyi',
            'price' => 1000,
            'stock' => 1,
            'is_active' => false,
        ]);

        $suggest = app(SearchEngine::class)->suggest('Tersembunyi');

        $this->assertCount(0, $suggest['products']);
    }

    public function test_suggest_endpoint_returns_json(): void
    {
        $category = Category::create(['name' => 'Earphone', 'slug' => 'earphone-'.uniqid(), 'is_active' => true]);
        $this->product('Earphone Nirkabel', category: $category);

        $this->get('/pencarian/saran?q=earphone')
            ->assertOk()
            ->assertJsonStructure([
                'products' => [['name', 'url', 'price', 'cover']],
                'categories' => [['name', 'url']],
                'brands',
            ]);
    }

    public function test_suggest_endpoint_validates_input(): void
    {
        $this->get('/pencarian/saran?q='.str_repeat('a', 150))->assertStatus(302);
        $this->get('/pencarian/saran')->assertStatus(302);
    }

    public function test_shop_page_uses_search_contract(): void
    {
        $this->product('Tumpukan Keramik');

        $this->get('/produk?q=Keramik')
            ->assertOk()
            ->assertSee('Tumpukan Keramik');
    }
}
