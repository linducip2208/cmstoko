<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Services\PaymentService;

class MidtransGateway implements PaymentGateway
{
    public function __construct(
        protected PaymentService $payment,
    ) {}

    public function id(): string
    {
        return 'midtrans';
    }

    public function label(): string
    {
        return 'Pembayaran Online (Midtrans)';
    }

    public function isConfigured(): bool
    {
        return $this->payment->configured();
    }

    public function initiate(Order $order): array
    {
        $token = $this->payment->snapToken($order->load('items'));

        return [
            'type' => 'snap',
            'token' => $token,
            'redirect_url' => $token ? null : null,
            'instructions' => null,
        ];
    }

    public function handleWebhook(array $payload): ?Order
    {
        return $this->payment->handleNotification($payload);
    }

    public function publicConfig(): array
    {
        return [
            'client_key' => config('shop.midtrans.client_key'),
            'snap_js_url' => $this->payment->snapJsUrl(),
            'is_production' => $this->payment->isProduction(),
        ];
    }
}
