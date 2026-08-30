<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentService
{
    public function configured(): bool
    {
        return (bool) config('shop.midtrans.server_key');
    }

    public function snapToken(Order $order): ?string
    {
        if (! $this->configured()) {
            return null;
        }

        $baseUrl = $this->isProduction()
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

        $payload = [
            'transaction_details' => [
                'order_id' => $order->order_number,
                'gross_amount' => $order->total,
            ],
            'item_details' => $order->items->map(fn ($item) => [
                'id' => (string) ($item->product_id ?? 'item'),
                'price' => $item->price,
                'quantity' => $item->quantity,
                'name' => Str::limit($item->product_name, 40),
            ])->values()->all(),
            'customer_details' => [
                'first_name' => $order->customer_name,
                'email' => $order->customer_email,
                'phone' => $order->customer_phone,
            ],
        ];

        if ($order->discount > 0) {
            $payload['item_details'][] = [
                'id' => 'discount',
                'price' => -abs($order->discount),
                'quantity' => 1,
                'name' => 'Diskon'.($order->coupon_code ? " ({$order->coupon_code})" : ''),
            ];
        }

        $payload['item_details'][] = [
            'id' => 'shipping',
            'price' => $order->shipping_cost,
            'quantity' => 1,
            'name' => 'Ongkos Kirim',
        ];

        try {
            $response = Http::withBasicAuth(config('shop.midtrans.server_key').':', '')
                ->acceptJson()
                ->timeout(30)
                ->post($baseUrl, $payload);

            return $response->json('token');
        } catch (\Throwable $e) {
            Log::error('Midtrans snap error: '.$e->getMessage());

            return null;
        }
    }

    public function snapJsUrl(): string
    {
        return $this->isProduction()
            ? 'https://app.midtrans.com/snap/snap.js'
            : 'https://app.sandbox.midtrans.com/snap/snap.js';
    }

    public function isProduction(): bool
    {
        return (bool) config('shop.midtrans.is_production');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handleNotification(array $payload): ?Order
    {
        $orderNumber = $payload['order_id'] ?? null;
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $signature = (string) ($payload['signature_key'] ?? '');
        $serverKey = (string) config('shop.midtrans.server_key');

        $expected = hash('sha512', $orderNumber.$statusCode.$grossAmount.$serverKey);

        if (! hash_equals($expected, $signature)) {
            Log::warning('Midtrans signature mismatch', ['order_id' => $orderNumber]);

            return null;
        }

        $order = Order::where('order_number', $orderNumber)->first();

        if (! $order) {
            return null;
        }

        $transactionStatus = $payload['transaction_status'] ?? '';
        $fraudStatus = $payload['fraud_status'] ?? 'accept';

        $order->payment_type = $payload['payment_type'] ?? null;
        $order->transaction_id = $payload['transaction_id'] ?? null;

        match (true) {
            in_array($transactionStatus, ['capture', 'settlement'], true) => $this->markPaid($order),
            $transactionStatus === 'pending' => $order->forceFill(['status' => Order::STATUS_PENDING])->save(),
            in_array($transactionStatus, ['deny', 'expire'], true) => $order->forceFill(['status' => Order::STATUS_CANCELLED])->save(),
            $transactionStatus === 'cancel' => $order->forceFill(['status' => Order::STATUS_CANCELLED])->save(),
            default => null,
        };

        if ($transactionStatus === 'capture' && $fraudStatus === 'challenge') {
            $order->forceFill(['status' => Order::STATUS_PENDING])->save();
        }

        return $order->fresh();
    }

    protected function markPaid(Order $order): void
    {
        $order->forceFill([
            'status' => Order::STATUS_PAID,
            'paid_at' => now(),
            'payment_method' => $order->payment_type ?? 'midtrans',
        ])->save();
    }
}
