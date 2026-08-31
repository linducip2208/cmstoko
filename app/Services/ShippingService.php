<?php

namespace App\Services;

use App\Models\City;
use App\Models\Province;
use Illuminate\Support\Facades\Http;

/**
 * Location data service (provinces/cities + RajaOngkir sync).
 * Shipping cost logic moved to App\Services\Shipping\* providers.
 */
class ShippingService
{
    public function provinces()
    {
        return Province::orderBy('name')->get(['id', 'name']);
    }

    public function cities(int $provinceId)
    {
        return City::where('province_id', $provinceId)->orderBy('name')->get(['id', 'province_id', 'name']);
    }

    public function syncLocationData(): void
    {
        $key = config('shop.rajaongkir.api_key');

        $provinces = Http::withHeaders(['key' => $key])
            ->timeout(30)
            ->get(config('shop.rajaongkir.base_url').'/province')
            ->json('rajaongkir.results', []);

        foreach ($provinces as $province) {
            Province::updateOrCreate(['id' => $province['province_id']], ['name' => $province['province']]);
        }

        foreach (range(1, (int) ceil(Province::count() / 50)) as $page) {
            $cities = Http::withHeaders(['key' => $key])
                ->timeout(30)
                ->get(config('shop.rajaongkir.base_url').'/city', ['page' => $page])
                ->json('rajaongkir.results', []);

            foreach ($cities as $city) {
                City::updateOrCreate(
                    ['id' => $city['city_id']],
                    [
                        'province_id' => $city['province_id'],
                        'name' => $city['city_name'].' '.$city['type'],
                        'type' => $city['type'],
                        'postal_code' => $city['postal_code'],
                    ],
                );
            }
        }
    }
}
