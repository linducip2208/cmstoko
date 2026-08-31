<?php

namespace App\Services\Shipping;

use App\Contracts\ShippingProvider;
use App\Data\ShippingContext;
use App\Services\ShippingService;

/**
 * Aggregates all enabled providers into a single option list.
 * Keyed as "{provider}:{service}" so costs are resolved server-side only —
 * the client can never inject a price (the old public-array hole).
 */
class ShippingManager
{
    /** @var list<ShippingProvider> */
    protected array $providers;

    public function __construct(
        protected FlatRateProvider $flat,
        protected FreeShippingProvider $free,
        protected StorePickupProvider $pickup,
        protected RajaOngkirProvider $rajaOngkir,
        protected ShippingService $legacy,
    ) {
        $this->providers = [$this->rajaOngkir, $this->free, $this->flat, $this->pickup];
    }

    /**
     * @return list<array{key: string, provider: string, service: string, description: string, cost: int, etd: string}>
     */
    public function options(ShippingContext $context): array
    {
        $options = [];

        foreach ($this->providers as $provider) {
            if (! $provider->isEnabled()) {
                continue;
            }

            foreach ($provider->options($context) as $option) {
                $options[] = $option + ['key' => $option['provider'].':'.$option['service']];
            }
        }

        return $options;
    }

    /**
     * Resolve a client-supplied option key to the AUTHORITATIVE cost.
     * Unknown/absent key = first available option (or flat fallback).
     */
    public function resolve(?string $key, ShippingContext $context): ?array
    {
        $options = $this->options($context);

        if ($options === []) {
            return null;
        }

        if ($key !== null && $key !== '') {
            $match = collect($options)->firstWhere('key', $key);

            if ($match) {
                return $match;
            }
        }

        return $options[0];
    }

    public function hasAnyOption(ShippingContext $context): bool
    {
        return $this->options($context) !== [];
    }

    public function usesRajaOngkir(): bool
    {
        return $this->rajaOngkir->isEnabled();
    }
}
