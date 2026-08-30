<?php

namespace Database\Seeders;

use App\Models\CustomerGroup;
use Illuminate\Database\Seeder;

class CustomerGroupSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Retail', 'slug' => CustomerGroup::SLUG_RETAIL, 'description' => 'Pelanggan umum terdaftar.', 'sort_order' => 1],
            ['name' => 'VIP', 'slug' => CustomerGroup::SLUG_VIP, 'description' => 'Pelanggan prioritas — dapat menikmati promo khusus.', 'sort_order' => 2],
            ['name' => 'Guest', 'slug' => CustomerGroup::SLUG_GUEST, 'description' => 'Tamu tanpa akun. Tidak dipakai sebagai grup pengguna.', 'sort_order' => 3],
        ] as $group) {
            CustomerGroup::updateOrCreate(['slug' => $group['slug']], $group);
        }

        // Existing customers without a group default to Retail.
        $retail = CustomerGroup::where('slug', CustomerGroup::SLUG_RETAIL)->value('id');

        if ($retail) {
            \App\Models\User::query()
                ->whereNull('customer_group_id')
                ->whereHas('role', fn ($q) => $q->where('slug', 'customer'))
                ->update(['customer_group_id' => $retail]);
        }
    }
}
