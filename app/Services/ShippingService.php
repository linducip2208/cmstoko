<?php

namespace App\Services;

use App\Models\City;
use App\Models\Province;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShippingService
{
    public function hasApi(): bool
    {
        return (bool) config('shop.rajaongkir.api_key');
    }

    public function provinces()
    {
        return Province::orderBy('name')->get();
    }

    public function cities(int $provinceId)
    {
        return City::where('province_id', $provinceId)->orderBy('name')->get();
    }

    /**
     * @return list<array{service: string, description: string, cost: int, etd: string}>
     */
    public function cost(int $destinationCityId, int $weightInGram, string $courier): array
    {
        if (! $this->hasApi()) {
            return $this->fallback();
        }

        return Cache::remember(
            "ongkir.{$destinationCityId}.{$weightInGram}.{$courier}",
            now()->addHours(6),
            fn () => $this->requestCost($destinationCityId, $weightInGram, $courier),
        );
    }

    /**
     * @return list<array{service: string, description: string, cost: int, etd: string}>
     */
    protected function requestCost(int $destinationCityId, int $weightInGram, string $courier): array
    {
        try {
            $response = Http::withHeaders(['key' => config('shop.rajaongkir.api_key')])
                ->asForm()
                ->timeout(20)
                ->post(config('shop.rajaongkir.base_url').'/cost', [
                    'origin' => config('shop.origin_city_id'),
                    'destination' => $destinationCityId,
                    'weight' => max(1000, $weightInGram),
                    'courier' => $courier,
                ]);

            $results = $response->json('rajaongkir.results.0.costs', []);

            $options = collect($results)
                ->map(fn (array $cost) => [
                    'service' => $cost['service'] ?? '-',
                    'description' => $cost['description'] ?? '-',
                    'cost' => (int) ($cost['cost'][0]['value'] ?? 0),
                    'etd' => $cost['cost'][0]['etd'] ?? '-',
                ])
                ->filter(fn (array $option) => $option['cost'] > 0)
                ->values()
                ->all();

            return $options !== [] ? $options : $this->fallback();
        } catch (ConnectionException $e) {
            Log::warning('RajaOngkir timeout: '.$e->getMessage());

            return $this->fallback();
        }
    }

    /**
     * @return list<array{service: string, description: string, cost: int, etd: string}>
     */
    public function fallback(): array
    {
        return [[
            'service' => config('shop.flat_shipping_service'),
            'description' => 'Tarif flat nasional',
            'cost' => (int) config('shop.flat_shipping_cost'),
            'etd' => config('shop.flat_shipping_etd'),
        ]];
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
