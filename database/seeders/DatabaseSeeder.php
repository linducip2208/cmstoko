<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RbacSeeder::class,
            SettingsSeeder::class,
            CustomerGroupSeeder::class,
        ]);

        $admin = User::updateOrCreate(
            ['email' => 'admin@tokokita.test'],
            [
                'name' => 'Admin TokoKita',
                'phone' => '081234567890',
                'password' => 'password',
                'role_id' => Role::where('slug', Role::SUPER_ADMIN)->value('id'),
            ],
        );

        User::updateOrCreate(
            ['email' => 'customer@tokokita.test'],
            [
                'name' => 'Pelanggan Contoh',
                'phone' => '081234567891',
                'password' => 'password',
                'role_id' => Role::where('slug', Role::CUSTOMER)->value('id'),
            ],
        );

        $this->call([
            CatalogSeeder::class,
            CmsSeeder::class,
            HomepageSeeder::class,
        ]);
    }
}
