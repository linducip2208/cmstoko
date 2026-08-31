<?php

namespace App\Services\Shipping;

use App\Contracts\ShippingProvider;
use App\Data\ShippingContext;
use App\Support\Settings;

class FlatRateProvider implements ShippingProvider
{
    public function key(): string
    {
        return 'flat';
    }

    public function displayName(): string
    {
        return Settings::get('shipping.flat.display_name', 'Reguler');
    }

    public function isEnabled(): bool
    {
        return (bool) Settings::get('shipping.flat.enabled', true);
    }

    public function options(ShippingContext $context): array
    {
        return [[
            'provider' => $this->key(),
            'service' => 'FLAT',
            'description' => $this->displayName(),
            'cost' => (int) Settings::get('shipping.flat.cost', config('shop.flat_shipping_cost')),
            'etd' => (string) Settings::get('shipping.flat.etd', config('shop.flat_shipping_etd')),
        ]];
    }
}
