<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Order;

class ManualTransferGateway implements PaymentGateway
{
    public function id(): string
    {
        return 'manual_transfer';
    }

    public function label(): string
    {
        return 'Transfer Bank Manual';
    }

    public function isConfigured(): bool
    {
        return (bool) config('shop.bank_accounts');
    }

    public function initiate(Order $order): array
    {
        return [
            'type' => 'instructions',
            'token' => null,
            'redirect_url' => null,
            'instructions' => $this->instructions($order),
        ];
    }

    public function handleWebhook(array $payload): ?Order
    {
        // Manual transfer has no webhook. Admin confirms payment manually.
        return null;
    }

    public function publicConfig(): array
    {
        return [
            'bank_accounts' => config('shop.bank_accounts'),
        ];
    }

    protected function instructions(Order $order): string
    {
        $accounts = collect(config('shop.bank_accounts', []))
            ->map(fn (array $account) => "{$account['bank']} — {$account['number']} ({$account['holder']})")
            ->implode("\n");

        return 'Lakukan transfer sebesar '.rupiah($order->total)." ke salah satu rekening berikut:\n".$accounts;
    }
}
