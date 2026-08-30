<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Services\Payments\ManualTransferGateway;
use App\Services\Payments\MidtransGateway;

class PaymentManager
{
    /** @var array<string, PaymentGateway> */
    protected array $drivers = [];

    public function __construct()
    {
        $this->drivers['midtrans'] = app(MidtransGateway::class);
        $this->drivers['manual_transfer'] = app(ManualTransferGateway::class);
    }

    public function driver(string $id): ?PaymentGateway
    {
        return $this->drivers[$id] ?? null;
    }

    /**
     * @return array<string, PaymentGateway>
     */
    public function available(): array
    {
        return collect($this->drivers)
            ->filter(fn (PaymentGateway $driver) => $driver->isConfigured())
            ->all();
    }

    public function defaultDriver(): PaymentGateway
    {
        $available = $this->available();

        return $available['midtrans'] ?? $available['manual_transfer'] ?? $this->drivers['manual_transfer'];
    }
}
