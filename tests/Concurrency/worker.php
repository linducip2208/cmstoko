<?php

// Standalone worker for the multi-process concurrency suite.
// Boots the framework against MySQL, performs ONE constrained operation
// through the SAME service layer as checkout (InventoryService, atomic
// coupon increment, Order::create), prints a JSON result line.

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

config(['database.default' => 'mysql', 'database.connections.mysql.database' => ($payload['db'] ?? 'cmstoko_conc_test')]);
DB::purge('mysql');

Artisan::call('migrate', ['--force' => true]);

$payloadPath = $argv[1] ?? '';
$payload = ['db' => 'cmstoko_conc_test'];

if ($payloadPath !== '' && is_file($payloadPath)) {
    $decoded = json_decode((string) file_get_contents($payloadPath), true);

    if (is_array($decoded)) {
        $payload = $decoded;
    }
}

$op = $payload['op'] ?? '';
$email = $payload['email'] ?? 'worker@example.com';

try {
    switch ($op) {
        case 'checkout':
            // Mirror CheckoutPage::placeOrder's server-authoritative core:
            // row locks → stock re-validation → order + items → ledger → coupon.
            $result = DB::transaction(function () use ($payload, $email) {
                $product = Product::whereKey($payload['product_id'])->lockForUpdate()->first();
                $qty = (int) $payload['qty'];

                if (! $product || $product->stock < $qty) {
                    return ['ok' => false, 'error' => 'stok habis'];
                }

                // Coupon atomic increment capped at max_uses (same as checkout).
                $couponCode = null;
                $discount = 0;

                if (! empty($payload['coupon'])) {
                    $coupon = Coupon::where('code', $payload['coupon'])->lockForUpdate()->first();

                    $affected = $coupon
                        ? Coupon::whereKey($coupon->id)
                            ->where(fn ($q) => $q->whereNull('max_uses')->orWhereColumn('used_count', '<', 'max_uses'))
                            ->increment('used_count')
                        : 0;

                    if ($affected === 0) {
                        return ['ok' => false, 'error' => 'kuota kupon habis'];
                    }

                    $couponCode = $coupon->code;
                    $discount = $coupon->type === 'percent'
                        ? (int) round($product->price * $qty * $coupon->value / 100)
                        : (int) $coupon->value;
                }

                $subtotal = $product->price * $qty;
                $shippingCost = 20000;

                $order = Order::create([
                    'customer_name' => 'Buyer '.substr($email, 0, 6),
                    'customer_email' => $email,
                    'customer_phone' => '0812',
                    'city_name' => 'Depok',
                    'province_name' => 'Jabar',
                    'address' => 'Jl. Race',
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'shipping_cost' => $shippingCost,
                    'total' => max(0, $subtotal - $discount + $shippingCost),
                    'weight' => 1000 * $qty,
                    'coupon_code' => $couponCode,
                    'payment_method' => 'manual_transfer',
                    'status' => Order::STATUS_PENDING,
                ]);

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'price' => $product->price,
                    'quantity' => $qty,
                    'subtotal' => $subtotal,
                ]);

                app(InventoryService::class)->deductForOrder([
                    ['product_id' => $product->id, 'variant_id' => null, 'quantity' => $qty, 'price' => $product->price, 'name' => $product->name],
                ], $order);

                $order->histories()->create(['from' => null, 'to' => Order::STATUS_PENDING, 'note' => 'Pesanan dibuat']);

                return ['ok' => true, 'order' => $order->order_number, 'total' => $order->total];
            });

            echo json_encode($result);
            break;

        case 'transition':
            $order = Order::find($payload['order_id']);

            try {
                $order->transitionTo($payload['to'], 'race test');
                echo json_encode(['ok' => true, 'status' => $order->fresh()->status]);
            } catch (InvalidArgumentException $e) {
                echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
            }
            break;

        default:
            echo json_encode(['ok' => false, 'error' => 'unknown op']);
    }
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage().' @ '.$e->getFile().':'.$e->getLine()]);
}
