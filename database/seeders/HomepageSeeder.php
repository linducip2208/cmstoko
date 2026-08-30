<?php

namespace Database\Seeders;

use App\Models\HomepageSection;
use App\Support\Settings;
use Illuminate\Database\Seeder;

class HomepageSeeder extends Seeder
{
    public function run(): void
    {
        HomepageSection::query()->where('type', '!=', 'hero')->delete();

        $freeShippingMin = Settings::get('policy.free_shipping_min', 300000);
        $shippingMinText = 'Rp '.number_format((float) $freeShippingMin, 0, ',', '.');

        HomepageSection::updateOrCreate(['type' => 'hero'], [
            'title' => 'Pilihan baik',
            'subtitle' => 'Kurasi produk sehari-hari dengan material pilihan dan desain yang tahan uji. Kamu memilih, kami urus sisanya.',
            'config' => [
                'eyebrow' => 'Koleksi Terbaru',
                'highlight' => 'untuk hidupmu.',
                'primary_cta' => ['label' => 'Jelajahi Katalog', 'url' => route('shop')],
                'secondary_cta' => ['label' => 'Lacak Pesanan', 'url' => route('track-order')],
                'source' => 'featured',
                'padding' => 'large',
            ],
            'sort_order' => 0,
            'is_active' => true,
        ]);

        HomepageSection::updateOrCreate(['type' => 'trust_bar'], [
            'title' => null,
            'config' => ['padding' => 'compact'],
            'sort_order' => 1,
            'is_active' => true,
        ]);

        HomepageSection::updateOrCreate(['type' => 'category_grid'], [
            'title' => 'Jelajahi kategori',
            'subtitle' => 'Mulai dari kebutuhanmu — audio, rumah, sampai gadget.',
            'config' => ['limit' => 4, 'padding' => 'normal'],
            'sort_order' => 2,
            'is_active' => true,
        ]);

        HomepageSection::updateOrCreate(['type' => 'product_grid', 'sort_order' => 3], [
            'title' => 'Pilihan Editor',
            'subtitle' => 'Produk yang lulus kurasi tim kami.',
            'config' => ['source' => 'featured', 'limit' => 8, 'columns' => 4, 'padding' => 'normal', 'link_url' => route('shop'), 'link_label' => 'Lihat Semua'],
            'is_active' => true,
        ]);

        HomepageSection::updateOrCreate(['type' => 'product_grid', 'sort_order' => 4], [
            'title' => 'Baru Datang',
            'subtitle' => 'Yang paling baru masuk katalog.',
            'config' => ['source' => 'new', 'limit' => 4, 'columns' => 4, 'padding' => 'normal'],
            'is_active' => true,
        ]);

        HomepageSection::updateOrCreate(['type' => 'cta', 'sort_order' => 5], [
            'title' => 'Siap memulai?',
            'subtitle' => "Gratis ongkir untuk pembelian di atas {$shippingMinText}.",
            'config' => ['cta' => ['label' => 'Mulai Belanja', 'url' => route('shop')], 'padding' => 'normal'],
            'is_active' => true,
        ]);

        // Trust bar items come from settings so merchants can edit claims.
        if (! Settings::get('trust_bar.items')) {
            Settings::set('trust_bar.items', [
                ['text' => "Gratis ongkir min. {$shippingMinText}"],
                ['text' => 'Pengiriman dari gudang sendiri'],
                ['text' => 'Pembayaran aman via Midtrans'],
                ['text' => 'Lacak pesanan real-time'],
            ], 'policies');
        }

        Settings::flush();
    }
}
