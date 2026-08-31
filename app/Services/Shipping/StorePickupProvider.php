<?php

namespace App\Services\Shipping;

use App\Contracts\ShippingProvider;
use App\Data\ShippingContext;
use App\Support\Settings;

class StorePickupProvider implements ShippingProvider
{
    public function key(): string
    {
        return 'pickup';
    }

    public function displayName(): string
    {
        return Settings::get('shipping.pickup.display_name', 'Ambil di Toko');
    }

    public function isEnabled(): bool
    {
        return (bool) Settings::get('shipping.pickup.enabled', false);
    }

    public function options(ShippingContext $context): array
    {
        $address = (string) Settings::get('shipping.pickup.address', '');

        return [[
            'provider' => $this->key(),
            'service' => 'PICKUP',
            'description' => $this->displayName().($address !== '' ? ' — '.$address : ''),
            'cost' => 0,
            'etd' => 'Ambil sendiri',
        ]];
    }
}
