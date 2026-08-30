<?php

namespace App\Services;

use App\Models\TaxClass;
use App\Models\TaxRate;

/**
 * Server-authoritative tax calculation. Integer-only math:
 * rates are stored in basis points (11% = 1100); never floats.
 *
 * Rounding policy: per matching line, half-up (round()), applied per rate.
 * Inclusive prices: tax = gross - round(gross * 10000 / (10000 + rate_bp)).
 */
class TaxCalculator
{
    /**
     * @param  iterable<array{price: int, qty: int, tax_class_id?: ?int}>  $lines
     * @param  array{province_id?: ?int, city_id?: ?int}  $address
     * @return array{amount: int, shipping_tax: int, breakdown: array<int, array{name: string, rate_bp: int, type: string, amount: int}>}
     */
    public function calculate(iterable $lines, int $discount, int $shippingCost, array $address = []): array
    {
        $rates = TaxRate::activeMap();
        $result = ['amount' => 0, 'shipping_tax' => 0, 'breakdown' => []];

        if ($rates === []) {
            return $result;
        }

        $defaultClassId = (int) (TaxClass::query()->where('is_default', true)->value('id') ?? 0);
        $lineCount = count(is_array($lines) ? $lines : iterator_to_array($lines));
        $discountPerLine = $lineCount > 0 ? intdiv($discount, $lineCount) : 0;
        $discountRemainder = $discount - ($discountPerLine * $lineCount);
        $index = 0;

        foreach ($lines as $line) {
            $gross = (int) $line['price'] * (int) $line['qty'];
            $classId = (int) ($line['tax_class_id'] ?? 0) ?: $defaultClassId;

            // Discount reduces the taxable base; remainder lands on the first line.
            $lineDiscount = $discountPerLine + ($index === 0 ? $discountRemainder : 0);
            $base = max(0, $gross - $lineDiscount);

            foreach ($rates as $rate) {
                if ((int) $rate['tax_class_id'] !== $classId || ! empty($rate['applies_to_shipping'])) {
                    continue;
                }

                if (! $this->zoneMatches($rate, $address)) {
                    continue;
                }

                $amount = $this->taxFor($base, (int) $rate['rate_bp'], $rate['type']);

                if ($amount <= 0) {
                    continue;
                }

                $result['amount'] += $amount;
                $result['breakdown'][] = [
                    'name' => $rate['name'],
                    'rate_bp' => $rate['rate_bp'],
                    'type' => $rate['type'],
                    'amount' => $amount,
                ];
            }

            $index++;
        }

        // Shipping tax (zone-matched rates flagged applies_to_shipping).
        if ($shippingCost > 0) {
            foreach ($rates as $rate) {
                if (empty($rate['applies_to_shipping']) || ! $this->zoneMatches($rate, $address)) {
                    continue;
                }

                $amount = $this->taxFor($shippingCost, (int) $rate['rate_bp'], $rate['type']);

                if ($amount > 0) {
                    $result['shipping_tax'] += $amount;
                    $result['breakdown'][] = [
                        'name' => $rate['name'].' (ongkir)',
                        'rate_bp' => $rate['rate_bp'],
                        'type' => $rate['type'],
                        'amount' => $amount,
                    ];
                }
            }
        }

        $result['amount'] += $result['shipping_tax'];

        return $result;
    }

    protected function taxFor(int $base, int $rateBp, string $type): int
    {
        if ($base <= 0 || $rateBp <= 0) {
            return 0;
        }

        return match ($type) {
            TaxRate::TYPE_INCLUSIVE => $base - intdiv($base * 10000, 10000 + $rateBp),
            default => (int) round($base * $rateBp / 10000),
        };
    }

    /**
     * Zone matching: rate with null province matches everywhere; a set
     * province must equal the address; city only checked when the rate pins it.
     */
    protected function zoneMatches(array $rate, array $address): bool
    {
        if ($rate['province_id'] !== null) {
            if (($address['province_id'] ?? null) === null || (int) $address['province_id'] !== $rate['province_id']) {
                return false;
            }
        }

        if ($rate['city_id'] !== null) {
            if (($address['city_id'] ?? null) === null || (int) $address['city_id'] !== $rate['city_id']) {
                return false;
            }
        }

        return true;
    }
}
