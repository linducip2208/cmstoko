<?php

namespace App\Contracts;

use App\Data\ShippingContext;

/**
 * Shipping provider contract. Providers return ready-to-render options;
 * checkout aggregates them server-side (the client never sends prices).
 */
interface ShippingProvider
{
    public function key(): string;

    public function displayName(): string;

    public function isEnabled(): bool;

    /**
     * @return list<array{provider: string, service: string, description: string, cost: int, etd: string}>
     */
    public function options(ShippingContext $context): array;
}
