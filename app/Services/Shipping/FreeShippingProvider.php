<?php

namespace App\Services\Shipping;

use App\Contracts\ShippingProvider;
use App\Data\ShippingContext;
use App\Support\Settings;

class FreeShippingProvider implements ShippingProvider
{
    public function key(): string
    {
        return 'free';
    }

    public function displayName(): string
    {
        return Settings::get('shipping.free.display_name', 'Gratis Ongkir');
    }

    public function isEnabled(): bool
    {
        return (bool) Settings::get('shipping.free.enabled', true);
    }

    public function options(ShippingContext $context): array
    {
        $min = (int) Settings::get('shipping.free.min_subtotal', Settings::get('policy.free_shipping_min', 300000));

        if ($context->subtotal < $min) {
            return [];
        }

        return [[
            'provider' => $this->key(),
            'service' => 'FREE',
            'description' => $this->displayName().' (min. belanja '.rupiah($min).')',
            'cost' => 0,
            'etd' => (string) Settings::get('shipping.flat.etd', config('shop.flat_shipping_etd')),
        ]];
    }
}
