<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\CmsPage;
use App\Models\Product;
use App\Models\SeoRedirect;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    protected function product(): Product
    {
        return Product::create([
            'category_id' => Category::create(['name' => 'Seo Cat '.uniqid()])->id,
            'brand_id' => Brand::create(['name' => 'Seo Brand '.uniqid(), 'slug' => 'seo-brand-'.uniqid(), 'is_active' => true])->id,
            'name' => 'Seo Product '.uniqid(),
            'slug' => 'seo-product-'.uniqid(),
            'price' => 120000,
            'stock' => 5,
            'weight' => 200,
            'is_active' => true,
        ]);
    }

    public function test_sitemap_contains_public_pages_only(): void
    {
        $product = $this->product();

        Product::create([
            'category_id' => $product->category_id,
            'name' => 'Draft Product',
            'slug' => 'draft-product',
            'price' => 50000,
            'stock' => 1,
            'is_active' => false, // excluded
        ]);

        CmsPage::create([
            'slug' => 'tentang-kami',
            'title' => 'Tentang Kami',
            'content' => '<p>Konten tentang kami.</p>',
            'status' => CmsPage::STATUS_PUBLISHED,
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $xml = $response->getContent();

        $this->assertStringContainsString($product->slug, $xml);
        $this->assertStringContainsString('tentang-kami', $xml);
        $this->assertStringNotContainsString('draft-product', $xml);
    }

    public function test_robots_blocks_private_areas(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Disallow: /admin')
            ->assertSee('Disallow: /akun')
            ->assertSee('Disallow: /checkout')
            ->assertSee('Sitemap: ');
    }

    public function test_product_page_emits_seo_meta_and_schema(): void
    {
        $product = $this->product();

        $response = $this->get('/produk/'.$product->slug);

        $response->assertOk();

        $html = $response->getContent();

        $this->assertStringContainsString('<link rel="canonical"', $html);
        $this->assertStringContainsString('property="og:title"', $html);
        $this->assertStringContainsString('name="twitter:card"', $html);
        $this->assertStringContainsString('"@type":"Product"', $html);
        $this->assertStringContainsString('"@type":"BreadcrumbList"', $html);

        // No AggregateRating without approved reviews.
        $this->assertStringNotContainsString('aggregateRating', $html);
    }

    public function test_filtered_shop_pages_are_noindex(): void
    {
        $category = Category::create(['name' => 'Seo Noindex '.uniqid(), 'slug' => 'seo-noindex-'.uniqid(), 'is_active' => true]);

        $this->get('/produk?category='.$category->slug.'&sort=price_asc')
            ->assertOk()
            ->assertSee('noindex, follow');
    }

    public function test_redirect_manager_redirects_404_paths(): void
    {
        SeoRedirect::create(['source' => 'produk-lama', 'destination' => '/produk', 'status_code' => 301]);

        $this->get('/produk-lama')->assertRedirect('/produk', 301);

        // Redirects only fire on 404s — real routes unaffected.
        $this->get('/produk')->assertOk();
    }

    public function test_redirect_manager_blocks_open_redirects_and_loops(): void
    {
        SeoRedirect::create(['source' => 'evil', 'destination' => 'https://evil.example.com/steal', 'status_code' => 301]);
        SeoRedirect::create(['source' => 'js', 'destination' => 'javascript:alert(1)', 'status_code' => 301]);
        SeoRedirect::create(['source' => 'self-loop', 'destination' => '/self-loop', 'status_code' => 301]);

        // External host: falls through to normal 404.
        $this->get('/evil')->assertNotFound();
        // Dangerous scheme: falls through to normal 404.
        $this->get('/js')->assertNotFound();
        // Self loop: falls through to normal 404.
        $this->get('/self-loop')->assertNotFound();
    }

    public function test_redirect_manager_counts_hits_and_respects_status(): void
    {
        SeoRedirect::create(['source' => 'sementara', 'destination' => '/produk', 'status_code' => 302]);

        $this->get('/sementara')->assertRedirect('/produk', 302);

        $redirect = SeoRedirect::query()->where('source', 'sementara')->first();

        $this->assertSame(1, $redirect->hit_count);
        $this->assertNotNull($redirect->last_hit_at);
    }

    public function test_inactive_redirects_are_ignored(): void
    {
        SeoRedirect::create(['source' => 'mati', 'destination' => '/produk', 'status_code' => 301, 'is_active' => false]);

        $this->get('/mati')->assertNotFound();
    }

    public function test_cms_page_emits_entity_meta(): void
    {
        CmsPage::create([
            'slug' => 'halaman-seo',
            'title' => 'Halaman SEO',
            'content' => '<p>Isi halaman.</p>',
            'seo' => ['meta_title' => 'Judul Meta Kustom', 'meta_description' => 'Deskripsi meta kustom.'],
            'status' => CmsPage::STATUS_PUBLISHED,
        ]);

        $this->get('/halaman/halaman-seo')
            ->assertOk()
            ->assertSee('<title>Judul Meta Kustom', false)
            ->assertSee('Deskripsi meta kustom.', false);
    }

    public function test_content_editor_role_cannot_access_redirect_admin_without_grant(): void
    {
        // Content Editor HAS redirects grant; finance does NOT.
        $finance = User::factory()->create([
            'role_id' => \App\Models\Role::where('slug', \App\Models\Role::FINANCE)->value('id'),
        ]);

        $this->actingAs($finance)
            ->get('/admin/seo-redirects')
            ->assertForbidden();
    }
}
