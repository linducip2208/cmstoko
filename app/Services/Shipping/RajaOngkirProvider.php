<?php

namespace App\Services\Shipping;

use App\Contracts\ShippingProvider;
use App\Data\ShippingContext;
use App\Support\Settings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RajaOngkirProvider implements ShippingProvider
{
    public function key(): string
    {
        return 'rajaongkir';
    }

    public function displayName(): string
    {
        return Settings::get('shipping.rajaongkir.display_name', 'Kurir Ekspedisi');
    }

    public function isEnabled(): bool
    {
        return (bool) Settings::get('shipping.rajaongkir.enabled', true)
            && $this->hasApiKey();
    }

    public function hasApiKey(): bool
    {
        return (bool) config('shop.rajaongkir.api_key');
    }

    public function options(ShippingContext $context): array
    {
        if (! $this->isEnabled() || $context->cityId === null) {
            return [];
        }

        return Cache::remember(
            "ongkir.{$context->cityId}.{$context->weightGram}.{$context->courier}",
            now()->addHours(6),
            fn () => $this->requestCost($context->cityId, $context->weightGram, $context->courier),
        );
    }

    /**
     * Graceful failure: an outage returns [] so other providers keep checkout alive.
     *
     * @return list<array{provider: string, service: string, description: string, cost: int, etd: string}>
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

            return collect($results)
                ->map(fn (array $cost) => [
                    'provider' => $this->key(),
                    'service' => $cost['service'] ?? '-',
                    'description' => $this->displayName().' — '.($cost['description'] ?? '-'),
                    'cost' => (int) ($cost['cost'][0]['value'] ?? 0),
                    'etd' => (string) ($cost['cost'][0]['etd'] ?? '-'),
                ])
                ->filter(fn (array $option) => $option['cost'] > 0)
                ->values()
                ->all();
        } catch (ConnectionException $e) {
            Log::warning('RajaOngkir timeout: '.$e->getMessage());

            return [];
        }
    }
}
