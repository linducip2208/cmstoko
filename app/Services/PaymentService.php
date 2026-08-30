<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentService
{
    public const GATEWAY = 'midtrans';

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

            if ($response->failed()) {
                Log::error('Midtrans snap error', [
                    'order' => $order->order_number,
                    'status' => $response->status(),
                    'body' => Str::limit($response->body(), 500),
                ]);

                return null;
            }

            return $response->json('token');
        } catch (\Throwable $e) {
            Log::error('Midtrans snap exception', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);

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
     * Verified, idempotent webhook processing.
     *
     * Guarantees:
     *  - signature verified via hash_equals (amount + order + status code)
     *  - amount must match order total (anti-tamper)
     *  - duplicate transaction_id is recorded once; no duplicate side effects
     *  - no paid→pending regressions; cancelled orders stay cancelled
     */
    public function handleNotification(array $payload): ?Order
    {
        $orderNumber = $payload['order_id'] ?? null;
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $signature = (string) ($payload['signature_key'] ?? '');
        $serverKey = (string) config('shop.midtrans.server_key');
        $transactionStatus = (string) ($payload['transaction_status'] ?? '');
        $transactionId = $payload['transaction_id'] ?? null;
        $fraudStatus = (string) ($payload['fraud_status'] ?? 'accept');

        $expected = hash('sha512', $orderNumber.$statusCode.$grossAmount.$serverKey);

        if (! hash_equals($expected, $signature)) {
            Log::warning('Midtrans signature mismatch', ['order_id' => $orderNumber]);

            return null;
        }

        $order = Order::where('order_number', $orderNumber)->first();

        if (! $order) {
            Log::warning('Midtrans webhook for unknown order', ['order_id' => $orderNumber]);

            return null;
        }

        if ((int) ((float) $grossAmount * 100) !== $order->total * 100) {
            Log::warning('Midtrans amount mismatch', [
                'order_id' => $orderNumber,
                'expected' => $order->total,
                'received' => $grossAmount,
            ]);

            return null;
        }

        // Idempotency: one ledger row per gateway transaction id.
        $existing = PaymentTransaction::where('gateway', self::GATEWAY)
            ->where('transaction_id', $transactionId)
            ->first();

        if ($existing && $existing->processed_at !== null) {
            Log::info('Midtrans webhook replay ignored', [
                'order_id' => $orderNumber,
                'transaction_id' => $transactionId,
            ]);

            return $order;
        }

        return DB::transaction(function () use ($payload, $order, $transactionStatus, $transactionId, $fraudStatus, $statusCode) {
            $transaction = PaymentTransaction::firstOrCreate(
                ['gateway' => self::GATEWAY, 'transaction_id' => $transactionId],
                [
                    'order_id' => $order->id,
                    'status' => $transactionStatus,
                    'gross_amount' => (int) ((float) $payload['gross_amount'] ?? 0),
                ],
            );

            // Lock the order row to serialize concurrent webhooks.
            $order = Order::whereKey($order->id)->lockForUpdate()->first();

            $order->forceFill([
                'payment_type' => $payload['payment_type'] ?? $order->payment_type,
                'transaction_id' => $transactionId ?? $order->transaction_id,
            ]);

            $challenge = $transactionStatus === 'capture' && $fraudStatus === 'challenge';

            try {
                if ($challenge) {
                    if ($order->status === Order::STATUS_PENDING) {
                        $order->transitionTo(Order::STATUS_PENDING, 'Midtrans challenge', null, true);
                        $transaction->update(['status' => PaymentTransaction::STATUS_CHALLENGE]);
                    }
                } elseif (in_array($transactionStatus, ['capture', 'settlement'], true)) {
                    $this->markPaid($order, $transaction);
                } elseif ($transactionStatus === 'pending') {
                    // Pending never regresses a paid/processing/shipped/completed order.
                    $transaction->update(['status' => PaymentTransaction::STATUS_PENDING]);
                } elseif (in_array($transactionStatus, ['deny', 'expire', 'cancel'], true)) {
                    if ($order->isPending() || $order->status === Order::STATUS_PAID) {
                        $order->transitionTo(Order::STATUS_CANCELLED, "Midtrans {$transactionStatus}");
                        $transaction->update(['status' => $transactionStatus]);
                    }
                }

                $order->save();
            } catch (InvalidArgumentException $e) {
                Log::warning('Midtrans transition rejected', [
                    'order_id' => $order->order_number,
                    'status' => $order->status,
                    'to' => $transactionStatus,
                    'reason' => $e->getMessage(),
                ]);
            }

            $transaction->forceFill([
                'payment_type' => $payload['payment_type'] ?? null,
                'fraud_status' => $fraudStatus,
                'payload' => $payload,
                'signature' => $statusCode,
                'processed_at' => now(),
            ])->save();

            return $order->fresh();
        });
    }

    protected function markPaid(Order $order, PaymentTransaction $transaction): void
    {
        if ($order->status === Order::STATUS_CANCELLED) {
            Log::warning('Midtrans settlement for cancelled order ignored', [
                'order_id' => $order->order_number,
            ]);

            return;
        }

        if ($order->isPaid()) {
            return;
        }

        $order->transitionTo(Order::STATUS_PAID, 'Pembayaran diterima via Midtrans');
        $transaction->update(['status' => PaymentTransaction::STATUS_SETTLED]);
        $order->forceFill([
            'payment_method' => $order->payment_type ?? self::GATEWAY,
        ]);
    }
}
