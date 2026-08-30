<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariantAttributeValue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->brands();
        $categories = $this->categories();
        $attributes = $this->attributes();
        $this->products($categories, $attributes);
        $this->collections($categories);
        $this->coupons();
    }

    protected function brands(): void
    {
        foreach (['Aurum', 'Kaya Living', 'Nordic Wave', 'Voltix'] as $index => $name) {
            Brand::updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name,
                'description' => $name.' menghadirkan produk dengan material pilihan dan desain yang tahan uji.',
                'sort_order' => $index,
            ]);
        }
    }

    protected function categories(): array
    {
        $roots = collect([
            ['name' => 'Audio', 'description' => 'Headphone, earbuds, dan speaker pilihan.', 'short' => 'Dengar lebih jernih.'],
            ['name' => 'Aksesoris', 'description' => 'Pelengkap gaya hidup sehari-hari.', 'short' => 'Detail yang berarti.'],
            ['name' => 'Rumah', 'description' => 'Peralatan rumah tangga modern.', 'short' => 'Rumah lebih nyaman.'],
            ['name' => 'Gadget', 'description' => 'Teknologi pintar untuk produktivitas.', 'short' => 'Saatnya upgrade.'],
        ])->mapWithKeys(function (array $data, int $index) {
            $category = Category::updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'short_description' => $data['short'],
                    'sort_order' => $index,
                ],
            );

            return [$data['name'] => $category];
        });

        // Nested subcategory example.
        Category::updateOrCreate(
            ['slug' => 'speaker'],
            [
                'parent_id' => $roots['Audio']->id,
                'name' => 'Speaker',
                'short_description' => 'Suara mengisi ruangan.',
                'sort_order' => 0,
            ],
        );

        Category::updateOrCreate(
            ['slug' => 'headphone-earbuds'],
            [
                'parent_id' => $roots['Audio']->id,
                'name' => 'Headphone & Earbuds',
                'short_description' => 'Untuk perjalanan dan fokus.',
                'sort_order' => 1,
            ],
        );

        return $roots->all();
    }

    protected function attributes(): array
    {
        $color = Attribute::updateOrCreate(['slug' => 'warna'], [
            'name' => 'Warna',
            'type' => Attribute::TYPE_COLOR,
            'is_variant' => true,
            'is_required' => true,
            'position' => 0,
        ]);

        foreach ([
            ['hitam', 'Hitam', '#1f2937'],
            ['putih', 'Putih', '#f3f4f6'],
            ['biru', 'Biru', '#2563eb'],
            ['krem', 'Krem', '#d6c7a1'],
        ] as $i => [$value, $label, $hex]) {
            AttributeOption::updateOrCreate(['attribute_id' => $color->id, 'value' => $value], [
                'label' => $label,
                'color' => $hex,
                'position' => $i,
            ]);
        }

        $size = Attribute::updateOrCreate(['slug' => 'ukuran'], [
            'name' => 'Ukuran',
            'type' => Attribute::TYPE_SELECT,
            'is_variant' => true,
            'is_required' => true,
            'position' => 1,
        ]);

        foreach ([
            ['s', 'S'],
            ['m', 'M'],
            ['l', 'L'],
        ] as $i => [$value, $label]) {
            AttributeOption::updateOrCreate(['attribute_id' => $size->id, 'value' => $value], [
                'label' => $label,
                'position' => $i,
            ]);
        }

        $capacity = Attribute::updateOrCreate(['slug' => 'kapasitas'], [
            'name' => 'Kapasitas',
            'type' => Attribute::TYPE_SELECT,
            'is_variant' => true,
            'is_required' => true,
            'position' => 2,
        ]);

        foreach ([
            ['10rb', '10.000 mAh'],
            ['20rb', '20.000 mAh'],
        ] as $i => [$value, $label]) {
            AttributeOption::updateOrCreate(['attribute_id' => $capacity->id, 'value' => $value], [
                'label' => $label,
                'position' => $i,
            ]);
        }

        return [
            'warna' => $color,
            'ukuran' => $size,
            'kapasitas' => $capacity,
        ];
    }

    protected function products(array $categories, array $attributes): void
    {
        $brandBySlug = Brand::pluck('id', 'slug');

        $products = [
            ['cat' => 'Audio', 'brand' => 'aurum', 'name' => 'Aurora Studio Headphone', 'price' => 1299000, 'sale' => 999000, 'stock' => 42, 'weight' => 450, 'featured' => true, 'desc' => 'Headphone over-ear dengan noise cancelling aktif, driver 40mm, dan baterai 40 jam. Bantalan memory foam yang nyaman dipakai seharian.', 'short' => 'ANC aktif, 40 jam pemakaian.'],
            ['cat' => 'Audio', 'brand' => 'aurum', 'name' => 'Pulse Mini Earbuds', 'price' => 749000, 'sale' => null, 'stock' => 88, 'weight' => 120, 'featured' => true, 'desc' => 'True wireless earbuds dengan Bluetooth 5.3, latensi rendah untuk gaming, dan charging case kompak tahan air IPX5.', 'short' => 'IPX5, Bluetooth 5.3.'],
            ['cat' => 'Audio', 'brand' => 'nordic-wave', 'name' => 'Boom Room Speaker', 'price' => 1599000, 'sale' => 1399000, 'stock' => 25, 'weight' => 1200, 'featured' => true, 'desc' => 'Speaker Bluetooth dengan suara stereo 360 derajat, bass mendalam, dan lampu RGB yang bisa disesuaikan suasana.', 'short' => 'Stereo 360, bass dalam.'],
            ['cat' => 'Aksesoris', 'brand' => 'kaya-living', 'name' => 'Nomad Canvas Backpack', 'price' => 899000, 'sale' => null, 'stock' => 64, 'weight' => 850, 'featured' => true, 'desc' => 'Tas ransel canvas water-resistant dengan kompartemen laptop 15 inci, port USB, dan desain minimalis untuk kerja maupun traveling.', 'short' => 'Muat laptop 15 inci.'],
            ['cat' => 'Aksesoris', 'brand' => 'kaya-living', 'name' => 'Solaris Watch Classic', 'price' => 2199000, 'sale' => 1799000, 'stock' => 18, 'weight' => 300, 'featured' => true, 'desc' => 'Jam tangan analog dengan kaca safir, strap kulit asli, dan movement quartz Jepang yang presisi.', 'short' => 'Kaca safir, kulit asli.'],
            ['cat' => 'Rumah', 'brand' => 'kaya-living', 'name' => 'Halo Ceramic Mug Set', 'price' => 259000, 'sale' => null, 'stock' => 120, 'weight' => 700, 'featured' => false, 'desc' => 'Set 4 mug keramik premium dengan glaze matte, aman untuk microwave dan dishwasher.', 'short' => 'Isi 4, glaze matte.'],
            ['cat' => 'Rumah', 'brand' => 'nordic-wave', 'name' => 'Lumen Ambient Lamp', 'price' => 459000, 'sale' => 399000, 'stock' => 55, 'weight' => 600, 'featured' => false, 'desc' => 'Lampu meja dengan 16 juta warna, kontrol sentuh, dan mode tidur. Sempurna untuk kamar dan ruang kerja.', 'short' => '16 juta warna.'],
            ['cat' => 'Gadget', 'brand' => 'voltix', 'name' => 'Volt 65W GaN Charger', 'price' => 349000, 'sale' => null, 'stock' => 150, 'weight' => 150, 'featured' => false, 'desc' => 'Charger GaN super ringkas 65W dengan dua port USB-C, mampu mengisi laptop dan ponsel sekaligus.', 'short' => 'Dua port USB-C.'],
            ['cat' => 'Gadget', 'brand' => 'voltix', 'name' => 'Glide Pro Mouse Wireless', 'price' => 599000, 'sale' => 499000, 'stock' => 72, 'weight' => 110, 'featured' => false, 'desc' => 'Mouse ergonomis dengan sensor 16.000 DPI, klik senyap, dan baterai tahan 90 hari.', 'short' => '16.000 DPI, senyap.'],
            ['cat' => 'Gadget', 'brand' => 'voltix', 'name' => 'Stream Desk Mic', 'price' => 849000, 'sale' => null, 'stock' => 33, 'weight' => 520, 'featured' => false, 'desc' => 'Mikrofon kondensor USB untuk podcast dan streaming dengan pola kartioid dan stand shock mount.', 'short' => 'Pola kartioid.'],
            ['cat' => 'Rumah', 'brand' => 'kaya-living', 'name' => 'Brew One Coffee Press', 'price' => 529000, 'sale' => 449000, 'stock' => 47, 'weight' => 900, 'featured' => false, 'desc' => 'French press borosilikat dengan filter presisi stainless, menghasilkan seduhan bersih dan pekat.', 'short' => 'Filter stainless.'],
            ['cat' => 'Aksesoris', 'brand' => 'kaya-living', 'name' => 'Orbit Key Organizer', 'price' => 189000, 'sale' => null, 'stock' => 200, 'weight' => 80, 'featured' => false, 'desc' => 'Pengorganisir kunci kompak dari aluminium dengan loop peluit dan hingga 8 kunci dalam genggaman rapi.', 'short' => 'Aluminium, 8 kunci.'],
        ];

        $index = 0;
        foreach ($products as $data) {
            $product = Product::updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'category_id' => $categories[$data['cat']]->id,
                    'brand_id' => $brandBySlug[$data['brand']] ?? null,
                    'name' => $data['name'],
                    'sku' => 'TK-'.str_pad(++$index, 4, '0', STR_PAD_LEFT),
                    'short_description' => $data['short'],
                    'description' => $data['desc'],
                    'price' => $data['price'],
                    'sale_price' => $data['sale'],
                    'stock' => $data['stock'],
                    'weight' => $data['weight'],
                    'images' => [$this->generateProductImage($data['name'], $index - 1)],
                    'is_active' => true,
                    'is_featured' => $data['featured'],
                ],
            );
        }

        $this->configurableProducts($categories, $attributes, $brandBySlug);
    }

    protected function configurableProducts(array $categories, array $attributes, $brandBySlug): void
    {
        // Nomad Canvas Backpack — Color x Size
        $backpack = Product::where('slug', 'nomad-canvas-backpack')->first();

        if ($backpack) {
            $backpack->update(['type' => Product::TYPE_CONFIGURABLE]);
            $this->generateVariants($backpack, [
                [$attributes['warna'], ['hitam', 'biru', 'krem']],
                [$attributes['ukuran'], ['s', 'm', 'l']],
            ], fn (array $labels) => 'Nomad Canvas Backpack — '.implode(' / ', $labels));
        }

        // Lumen Ambient Lamp — Color only
        $lamp = Product::where('slug', 'lumen-ambient-lamp')->first();

        if ($lamp) {
            $lamp->update(['type' => Product::TYPE_CONFIGURABLE]);
            $this->generateVariants($lamp, [
                [$attributes['warna'], ['putih', 'hitam']],
            ], fn (array $labels) => 'Lumen Ambient Lamp — '.implode(' / ', $labels));
        }

        // A configurable-only demo: powerbank
        $powerbank = Product::updateOrCreate(
            ['slug' => 'voltix-powerbank-nova'],
            [
                'category_id' => $categories['Gadget']->id,
                'brand_id' => $brandBySlug['voltix'] ?? null,
                'type' => Product::TYPE_CONFIGURABLE,
                'name' => 'Voltix Powerbank Nova',
                'sku' => 'TK-0200',
                'short_description' => 'Powerbank cepat dengan kapasitas pilihan.',
                'description' => 'Powerbank dengan fast charging 22.5W, indikator LED, dan perlindungan panas berlapis. Tersedia dua kapasitas.',
                'price' => 429000,
                'sale_price' => 389000,
                'stock' => 0,
                'weight' => 320,
                'images' => [$this->generateProductImage('Voltix Powerbank Nova', 6)],
                'is_active' => true,
                'is_featured' => false,
            ],
        );

        $this->generateVariants($powerbank, [
            [$attributes['kapasitas'], ['10rb', '20rb']],
        ], fn (array $labels) => 'Voltix Powerbank Nova — '.implode(' / ', $labels));
    }

    protected function generateVariants(Product $product, array $sets, \Closure $labelFn): void
    {
        $combinations = [[]];

        foreach ($sets as [$attribute, $values]) {
            $append = [];
            $options = AttributeOption::where('attribute_id', $attribute->id)->whereIn('value', $values)->get();

            foreach ($combinations as $combination) {
                foreach ($options as $option) {
                    $append[] = array_merge($combination, [$option]);
                }
            }

            $combinations = $append;
        }

        $position = (int) ($product->variants()->max('position') ?? 0);

        foreach ($combinations as $options) {
            $key = collect($options)->pluck('id')->sort()->implode('-');

            $variant = $product->variants()->whereHas('attributeValues', function ($q) use ($key) {
                $ids = explode('-', $key);
                $q->whereIn('attribute_option_id', $ids);
            }, '=', count($options))->first();

            if ($variant) {
                continue;
            }

            $labels = collect($options)->pluck('label')->all();

            $variant = $product->variants()->create([
                'sku' => ($product->sku ?? 'VAR').'-'.collect($options)->pluck('value')->implode('-'),
                'stock' => random_int(4, 30),
                'weight' => $product->weight,
                'is_active' => true,
                'position' => ++$position,
            ]);

            foreach ($options as $option) {
                ProductVariantAttributeValue::create([
                    'variant_id' => $variant->id,
                    'attribute_id' => $option->attribute_id,
                    'attribute_option_id' => $option->id,
                ]);
            }

            unset($labelFn); // labels derivable from options on the frontend
        }
    }

    protected function collections(array $categories): void
    {
        Collection::updateOrCreate(['slug' => 'pilihan-editor'], [
            'name' => 'Pilihan Editor',
            'type' => Collection::TYPE_RULES,
            'rules' => [['field' => 'featured', 'value' => 1]],
            'description' => 'Produk yang lulus kurasi tim kami.',
            'is_featured' => true,
            'sort_order' => 0,
        ]);

        Collection::updateOrCreate(['slug' => 'teknologi-rumah'], [
            'name' => 'Teknologi Rumah',
            'type' => Collection::TYPE_MANUAL,
            'description' => 'Koleksi perlengkapan rumah yang cerdas.',
            'sort_order' => 1,
        ]);

        $collection = Collection::where('slug', 'teknologi-rumah')->first();
        $collection->products()->sync(
            Product::whereIn('slug', ['lumen-ambient-lamp', 'brew-one-coffee-press', 'halo-ceramic-mug-set'])
                ->pluck('id')
                ->mapWithKeys(fn ($id) => [$id => ['sort_order' => 0]])
                ->all()
        );
    }

    protected function coupons(): void
    {
        Coupon::updateOrCreate(['code' => 'WELCOME10K'], [
            'type' => Coupon::TYPE_FIXED,
            'value' => 10000,
            'min_purchase' => 100000,
            'is_active' => true,
        ]);

        Coupon::updateOrCreate(['code' => 'DISKON10'], [
            'type' => Coupon::TYPE_PERCENT,
            'value' => 10,
            'min_purchase' => 200000,
            'max_uses' => 100,
            'is_active' => true,
        ]);
    }

    protected function generateProductImage(string $name, int $index): string
    {
        $palettes = [
            ['#e0e7ff', '#818cf8', '#312e81'],
            ['#fef3c7', '#fbbf24', '#78350f'],
            ['#d1fae5', '#34d399', '#064e3b'],
            ['#ffe4e6', '#fb7185', '#881337'],
            ['#e0f2fe', '#38bdf8', '#0c4a6e'],
            ['#f3e8ff', '#c084fc', '#4c1d95'],
        ];
        [$a, $b, $c] = $palettes[$index % count($palettes)];

        $initial = mb_strtoupper(mb_substr($name, 0, 1));
        $slug = Str::slug($name);
        $fileName = "{$slug}.svg";
        $dir = public_path('images/products');

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="800" height="1000" viewBox="0 0 800 1000">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="{$a}"/>
      <stop offset="100%" stop-color="{$b}"/>
    </linearGradient>
  </defs>
  <rect width="800" height="1000" fill="url(#g)"/>
  <circle cx="640" cy="200" r="220" fill="{$c}" opacity="0.12"/>
  <circle cx="120" cy="840" r="260" fill="{$c}" opacity="0.10"/>
  <circle cx="400" cy="460" r="150" fill="#ffffff" opacity="0.9"/>
  <text x="400" y="530" font-family="'Plus Jakarta Sans', Arial, sans-serif" font-size="160" font-weight="800" fill="{$c}" text-anchor="middle">{$initial}</text>
</svg>
SVG;

        file_put_contents($dir.'/'.$fileName, $svg);

        return "/images/products/{$fileName}";
    }
}
