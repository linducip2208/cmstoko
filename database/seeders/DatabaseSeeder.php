<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@tokokita.test'],
            ['name' => 'Admin TokoKita', 'phone' => '081234567890', 'password' => 'password'],
        );

        $categories = collect([
            ['name' => 'Audio', 'description' => 'Headphone, earbuds, dan speaker pilihan.'],
            ['name' => 'Aksesoris', 'description' => 'Pelengkap gaya hidup sehari-hari.'],
            ['name' => 'Rumah', 'description' => 'Peralatan rumah tangga modern.'],
            ['name' => 'Gadget', 'description' => 'Teknologi pintar untuk produktivitas.'],
        ])->mapWithKeys(function (array $data) {
            $category = Category::create(['name' => $data['name'], 'description' => $data['description'], 'sort_order' => 0]);

            return [$data['name'] => $category];
        });

        $products = [
            ['cat' => 'Audio', 'name' => 'Aurora Studio Headphone', 'price' => 1299000, 'sale' => 999000, 'stock' => 42, 'weight' => 450, 'featured' => true, 'desc' => 'Headphone over-ear dengan noise cancelling aktif, driver 40mm, dan baterai 40 jam. Desain premium dengan bantalan memory foam yang nyaman dipakai seharian.'],
            ['cat' => 'Audio', 'name' => 'Pulse Mini Earbuds', 'price' => 749000, 'sale' => null, 'stock' => 88, 'weight' => 120, 'featured' => true, 'desc' => 'True wireless earbuds dengan Bluetooth 5.3, latensi rendah untuk gaming, dan charging case kompak tahan air IPX5.'],
            ['cat' => 'Audio', 'name' => 'Boom Room Speaker', 'price' => 1599000, 'sale' => 1399000, 'stock' => 25, 'weight' => 1200, 'featured' => true, 'desc' => 'Speaker Bluetooth dengan suara stereo 360°, bass mendalam, dan lampu RGB yang bisa disesuaikan suasana.'],
            ['cat' => 'Aksesoris', 'name' => 'Nomad Canvas Backpack', 'price' => 899000, 'sale' => null, 'stock' => 64, 'weight' => 850, 'featured' => true, 'desc' => 'Tas ransel canvas water-resistant dengan kompartemen laptop 15", port USB, dan desain minimalis untuk kerja maupun traveling.'],
            ['cat' => 'Aksesoris', 'name' => 'Solaris Watch Classic', 'price' => 2199000, 'sale' => 1799000, 'stock' => 18, 'weight' => 300, 'featured' => true, 'desc' => 'Jam tangan analog dengan kaca safir, strap kulit asli, dan movement quartz Jepang yang presisi.'],
            ['cat' => 'Rumah', 'name' => 'Halo Ceramic Mug Set', 'price' => 259000, 'sale' => null, 'stock' => 120, 'weight' => 700, 'featured' => false, 'desc' => 'Set 4 mug keramik premium dengan glaze matte, aman untuk microwave dan dishwasher.'],
            ['cat' => 'Rumah', 'name' => 'Lumen Ambient Lamp', 'price' => 459000, 'sale' => 399000, 'stock' => 55, 'weight' => 600, 'featured' => false, 'desc' => 'Lampu meja dengan 16 juta warna, kontrol sentuh, dan mode tidur. Sempurna untuk kamar dan ruang kerja.'],
            ['cat' => 'Gadget', 'name' => 'Volt 65W GaN Charger', 'price' => 349000, 'sale' => null, 'stock' => 150, 'weight' => 150, 'featured' => false, 'desc' => 'Charger GaN super ringkas 65W dengan dua port USB-C, mampu mengisi laptop dan ponsel sekaligus.'],
            ['cat' => 'Gadget', 'name' => 'Glide Pro Mouse Wireless', 'price' => 599000, 'sale' => 499000, 'stock' => 72, 'weight' => 110, 'featured' => false, 'desc' => 'Mouse ergonomis dengan sensor 16.000 DPI, klik senyap, dan baterai tahan 90 hari.'],
            ['cat' => 'Gadget', 'name' => 'Stream Desk Mic', 'price' => 849000, 'sale' => null, 'stock' => 33, 'weight' => 520, 'featured' => false, 'desc' => 'Mikrofon kondensor USB untuk podcast dan streaming dengan pola kartioid dan stand shock mount.'],
            ['cat' => 'Rumah', 'name' => 'Brew One Coffee Press', 'price' => 529000, 'sale' => 449000, 'stock' => 47, 'weight' => 900, 'featured' => false, 'desc' => 'French press borosilikat dengan filter presisi stainless, menghasilkan seduhan bersih dan pekat.'],
            ['cat' => 'Aksesoris', 'name' => 'Orbit Key Organizer', 'price' => 189000, 'sale' => null, 'stock' => 200, 'weight' => 80, 'featured' => false, 'desc' => 'Pengorganisir kunci kompak dari aluminium dengan loop peluit dan hingga 8 kunci dalam genggaman rapi.'],
        ];

        foreach ($products as $index => $data) {
            $product = Product::create([
                'category_id' => $categories[$data['cat']]->id,
                'name' => $data['name'],
                'sku' => 'TK-'.str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                'description' => $data['desc'],
                'price' => $data['price'],
                'sale_price' => $data['sale'],
                'stock' => $data['stock'],
                'weight' => $data['weight'],
                'images' => [$this->generateProductImage($data['name'], $index)],
                'is_active' => true,
                'is_featured' => $data['featured'],
            ]);
        }

        foreach ($categories as $category) {
            $category->update(['sort_order' => $categories->keys()->search($category->name)]);
        }

        Coupon::create([
            'code' => 'WELCOME10K',
            'type' => Coupon::TYPE_FIXED,
            'value' => 10000,
            'min_purchase' => 100000,
            'is_active' => true,
        ]);

        Coupon::create([
            'code' => 'DISKON10',
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
