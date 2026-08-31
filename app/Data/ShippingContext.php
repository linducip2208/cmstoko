<?php

namespace App\Data;

class ShippingContext
{
    public function __construct(
        public readonly ?int $provinceId,
        public readonly ?int $cityId,
        public readonly int $weightGram,
        public readonly int $subtotal,
        public readonly string $courier = 'jne',
    ) {}
}
