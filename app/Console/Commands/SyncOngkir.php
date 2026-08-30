<?php

namespace App\Console\Commands;

use App\Models\City;
use App\Models\Province;
use App\Services\ShippingService;
use Illuminate\Console\Command;

class SyncOngkir extends Command
{
    protected $signature = 'shop:sync-ongkir';

    protected $description = 'Sinkronisasi data provinsi & kota RajaOngkir ke database';

    public function handle(ShippingService $shipping): int
    {
        if (! $shipping->hasApi()) {
            $this->error('RAJAONGKIR_API_KEY belum diisi di .env');

            return self::FAILURE;
        }

        $this->info('Mengambil data provinsi & kota dari RajaOngkir…');
        $shipping->syncLocationData();

        $this->info('Selesai! '.Province::count().' provinsi, '.City::count().' kota tersimpan.');

        return self::SUCCESS;
    }
}
