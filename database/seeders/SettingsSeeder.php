<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use App\Support\Settings;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        Warehouse::updateOrCreate(
            ['code' => 'MAIN'],
            ['name' => 'Gudang Utama', 'is_default' => true, 'is_active' => true],
        );

        $defaults = [
            // Branding
            'store.name' => [config('shop.name', 'TokoKita'), 'branding'],
            'store.tagline' => ['Pilihan baik untuk hidup sehari-hari.', 'branding'],
            'store.logo' => [null, 'branding'],
            'store.logo_dark' => [null, 'branding'],
            'store.favicon' => [null, 'branding'],
            'store.email' => ['halo@tokokita.test', 'branding'],
            'store.phone' => ['+62 812-3456-7890', 'branding'],
            'store.whatsapp' => ['6281234567890', 'branding'],
            'store.address' => ['Jl. Contoh Alamat No. 10, Jakarta', 'branding'],
            'store.social.instagram' => ['https://instagram.com/', 'branding'],
            'store.social.tiktok' => [null, 'branding'],
            'store.social.facebook' => [null, 'branding'],
            'store.social.youtube' => [null, 'branding'],

            // Header / announcement
            'header.announcement' => ['Gratis ongkir untuk pembelian di atas Rp300.000 — hanya hari ini.', 'header'],
            'header.announcement_enabled' => [true, 'header'],

            // Footer
            'footer.about' => ['Toko online dengan kurasi produk yang tahan uji. Kami memilih yang terbaik agar kamu tidak perlu ragu.', 'footer'],
            'footer.copyright' => ['Semua hak dilindungi.', 'footer'],

            // SEO
            'seo.home_title' => [null, 'seo'],
            'seo.home_description' => [null, 'seo'],
            'seo.og_image' => [null, 'seo'],

            // Payments
            'payments.bank_accounts' => [
                [
                    ['bank' => 'BCA', 'number' => '1234567890', 'holder' => 'PT TokoKita Sejahtera'],
                    ['bank' => 'Mandiri', 'number' => '9876543210', 'holder' => 'PT TokoKita Sejahtera'],
                ],
                'payments',
            ],

            // Returns policy (rendered only as CMS content, never as claims)
            'policy.return_days' => [7, 'policies'],
            'policy.free_shipping_min' => [300000, 'policies'],
        ];

        foreach ($defaults as $key => [$value, $group]) {
            Settings::set($key, $value, $group);
        }

        Settings::flush();
    }
}
