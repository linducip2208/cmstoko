<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function makeProduct(array $overrides = []): Product
    {
        $category = Category::create(['name' => 'Kat Test']);

        return Product::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Produk Uji '.uniqid(),
            'price' => 100000,
            'stock' => 10,
            'weight' => 500,
            'is_active' => true,
        ], $overrides));
    }

    protected function makeVariantProduct(array $options = ['hitam', 'putih']): Product
    {
        $color = Attribute::create(['name' => 'Warna', 'type' => Attribute::TYPE_COLOR, 'is_variant' => true]);
        $optionIds = [];

        foreach ($options as $value) {
            $option = AttributeOption::create([
                'attribute_id' => $color->id,
                'value' => $value,
                'label' => ucfirst($value),
            ]);
            $optionIds[$value] = $option->id;
        }

        $product = $this->makeProduct(['type' => Product::TYPE_CONFIGURABLE, 'price' => 200000]);

        foreach ($optionIds as $value => $optionId) {
            $variant = $product->variants()->create([
                'sku' => 'VAR-'.$value,
                'stock' => $value === 'hitam' ? 0 : 5,
                'weight' => 500,
                'is_active' => true,
            ]);

            $variant->attributeValues()->create([
                'attribute_id' => $color->id,
                'attribute_option_id' => $optionId,
            ]);
        }

        return $product;
    }

    public function test_variant_price_falls_back_to_product_price(): void
    {
        $product = $this->makeProduct(['price' => 200000]);
        $variant = $product->variants()->create(['sku' => 'V1', 'stock' => 3, 'is_active' => true]);

        $this->assertSame(200000, $variant->effectivePrice());

        $variant->update(['price' => 250000]);
        $this->assertSame(250000, $variant->fresh()->effectivePrice());

        $variant->update(['price' => 250000, 'sale_price' => 199000]);
        $this->assertSame(199000, $variant->fresh()->effectivePrice());
        $this->assertTrue($variant->fresh()->hasDiscount());
    }

    public function test_find_matching_variant(): void
    {
        $product = $this->makeVariantProduct();
        $color = Attribute::where('slug', 'warna')->first();
        $putih = $color->options()->where('value', 'putih')->first();
        $hitam = $color->options()->where('value', 'hitam')->first();

        $match = ProductVariant::findMatching($product, [$putih->id]);
        $this->assertNotNull($match);
        $this->assertSame('VAR-putih', $match->sku);

        $match = ProductVariant::findMatching($product, [$hitam->id]);
        $this->assertNotNull($match);
        $this->assertSame('VAR-hitam', $match->sku);
        $this->assertSame(0, $match->stock);
    }

    public function test_configurable_product_stock_reflects_variants(): void
    {
        $product = $this->makeVariantProduct();

        $this->assertTrue($product->isConfigurable());
        $this->assertTrue($product->inStock());

        // All variants out of stock.
        $product->variants()->update(['stock' => 0]);
        $this->assertFalse($product->fresh()->inStock());
    }

    public function test_slug_uniqueness(): void
    {
        $a = $this->makeProduct(['name' => 'Same Name']);
        $b = $this->makeProduct(['name' => 'Same Name']);

        $this->assertNotSame($a->slug, $b->slug);
        $this->assertStringStartsWith('same-name', $b->slug);
    }

    public function test_brand_relation_and_scope(): void
    {
        $brand = Brand::create(['name' => 'Merek Uji']);
        $product = $this->makeProduct(['brand_id' => $brand->id]);

        $this->assertSame('Merek Uji', $product->brand->name);
        $this->assertSame(1, Brand::active()->count());

        $brand->update(['is_active' => false]);
        $this->assertSame(0, Brand::active()->count());
    }

    public function test_rule_based_collection_resolves_products(): void
    {
        $this->makeProduct(['name' => 'Featured Item', 'is_featured' => true]);
        $this->makeProduct(['name' => 'Normal Item']);

        $collection = Collection::create([
            'name' => 'Unggulan Otomatis',
            'type' => Collection::TYPE_RULES,
            'rules' => [['field' => 'featured', 'value' => 1]],
        ]);

        $resolved = $collection->resolveProducts()->get();
        $this->assertSame(1, $resolved->count());
        $this->assertSame('Featured Item', $resolved->first()->name);

        // Manual collection via pivot.
        $manual = Collection::create([
            'name' => 'Manual Collection',
            'type' => Collection::TYPE_MANUAL,
        ]);
        $product = Product::where('name', 'Normal Item')->first();
        $manual->products()->sync([$product->id => ['sort_order' => 0]]);

        $this->assertSame(1, $manual->resolveProducts()->count());
    }

    public function test_product_search_scopes(): void
    {
        $product = $this->makeProduct(['name' => 'Headphone Keren', 'sku' => 'TK-9999']);
        $this->makeProduct(['name' => 'Mouse Biasa']);

        $results = Product::active()->search('headphone')->get();
        $this->assertSame(1, $results->count());
        $this->assertTrue($results->first()->is($product));

        $bySku = Product::active()->search('TK-9999')->get();
        $this->assertSame(1, $bySku->count());
    }

    public function test_attribute_value_pivot_integrity(): void
    {
        $product = $this->makeVariantProduct();

        $variant = $product->variants()->where('sku', 'VAR-putih')->first();
        $this->assertSame(1, $variant->attributeValues()->count());

        $attributeValue = $variant->attributeValues()->first();
        $this->assertSame('Putih', $attributeValue->option->label);
        $this->assertSame('warna', $attributeValue->attribute->slug);
    }
}
