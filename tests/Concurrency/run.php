<?php

// P29: true multi-process concurrency harness (MySQL — sqlite :memory: cannot span processes).
// Spawns parallel `php concurrency-worker.php <payload-file>` processes via proc_open,
// all starting simultaneously, racing on the SAME database. Exactly-one-winner semantics.

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

const DB_NAME = 'cmstoko_conc_test';
const RUNNER = __DIR__.'/worker.php';

config(['database.default' => 'mysql', 'database.connections.mysql.database' => DB_NAME]);
DB::purge('mysql');

$pdo = new PDO('mysql:host=127.0.0.1', (string) config('database.connections.mysql.username'), (string) config('database.connections.mysql.password'));
$pdo->exec('DROP DATABASE IF EXISTS '.DB_NAME);
$pdo->exec('CREATE DATABASE '.DB_NAME.' CHARACTER SET utf8mb4');

Artisan::call('migrate:fresh', ['--force' => true]);
Artisan::call('db:seed', ['--class' => 'Database\Seeders\RbacSeeder', '--force' => true]);

$catId = DB::table('categories')->insertGetId(['name' => 'Conc '.uniqid(), 'slug' => 'conc-'.uniqid(), 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]);

// Spawn worker: payload via temp file (Windows shell-safe).
$spawnPair = function (array $p1, array $p2) {
    $make = function (array $payload): string {
        $path = tempnam(sys_get_temp_dir(), 'conc-');
        file_put_contents($path, json_encode($payload));

        return $path;
    };

    $cmd = function (string $payloadFile): array {
        return [
            ['file', 'php', RUNNER, $payloadFile],
            1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
        ];
    };

    $spec = fn (string $f) => [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];

    $run = function (string $payloadFile) use ($spec) {
        $cmd = 'php '.escapeshellarg(RUNNER).' '.escapeshellarg($payloadFile);
        $proc = proc_open($cmd, $spec($payloadFile), $pipes, dirname(__DIR__, 2));

        return ['proc' => $proc, 'pipes' => $pipes];
    };

    // Start BOTH before waiting on either — true simultaneity.
    $a = $run($make($p1));
    $b = $run($make($p2));

    $collect = function (array $p): array {
        $stdout = stream_get_contents($p['pipes'][1]);
        fclose($p['pipes'][1]);
        fclose($p['pipes'][2]);
        proc_close($p['proc']);

        return json_decode((string) $stdout, true) ?? ['ok' => false, 'error' => substr((string) $stdout, 0, 300)];
    };

    return [$collect($a), $collect($b)];
};

echo "=== TEST 1: two buyers race for the LAST unit (stock=1) ===\n";

$product = Product::create([
    'category_id' => $catId,
    'name' => 'Conc Product',
    'slug' => 'conc-'.uniqid(),
    'price' => 100000,
    'stock' => 1,
    'weight' => 100,
    'is_active' => true,
]);

[$res1, $res2] = $spawnPair(
    ['db' => DB_NAME, 'op' => 'checkout', 'product_id' => $product->id, 'qty' => 1, 'email' => 'buyer1@example.com'],
    ['db' => DB_NAME, 'op' => 'checkout', 'product_id' => $product->id, 'qty' => 1, 'email' => 'buyer2@example.com'],
);

echo 'winner1: '.json_encode($res1)."\n";
echo 'winner2: '.json_encode($res2)."\n";

$orders = DB::table('orders')->where('customer_email', 'like', 'buyer%@example.com')->count();
$stock = Product::find($product->id)->stock;
echo "orders: {$orders}, final stock: {$stock}\n";

$pass1 = (($res1['ok'] ?? false) + ($res2['ok'] ?? false)) === 1
    && $orders === 1
    && $stock === 0;

echo $pass1 ? "PASS: exactly one buyer won\n" : "FAIL\n";

echo "\n=== TEST 2: coupon with max_uses=1 spent by 2 racing buyers ===\n";

Coupon::create([
    'code' => 'RACE'.strtoupper(uniqid()),
    'type' => 'fixed',
    'value' => 10000,
    'min_purchase' => 0,
    'max_uses' => 1,
    'used_count' => 0,
    'starts_at' => now()->subDay(),
    'expires_at' => now()->addDay(),
    'is_active' => true,
]);

$code = Coupon::latest('id')->value('code');

$product2 = Product::create([
    'category_id' => $catId,
    'name' => 'Conc Product 2',
    'slug' => 'conc-'.uniqid(),
    'price' => 100000,
    'stock' => 10,
    'weight' => 100,
    'is_active' => true,
]);

[$res1, $res2] = $spawnPair(
    ['db' => DB_NAME, 'op' => 'checkout', 'product_id' => $product2->id, 'qty' => 1, 'email' => 'c1@example.com', 'coupon' => $code],
    ['db' => DB_NAME, 'op' => 'checkout', 'product_id' => $product2->id, 'qty' => 1, 'email' => 'c2@example.com', 'coupon' => $code],
);

echo 'winner1: '.json_encode($res1)."\n";
echo 'winner2: '.json_encode($res2)."\n";

$coupon = Coupon::where('code', $code)->first();
$discounted = DB::table('orders')->where('coupon_code', $code)->where('discount', '>', 0)->count();
echo "used_count: {$coupon->used_count}, discounted orders: {$discounted}\n";

$pass2 = (int) $coupon->used_count === 1 && $discounted === 1;

echo $pass2 ? "PASS: coupon consumed exactly once\n" : "FAIL\n";

echo "\n=== TEST 3: paid vs cancelled race (single deterministic transition) ===\n";

$order = Order::create([
    'customer_name' => 'Race',
    'customer_email' => 'race@example.com',
    'customer_phone' => '0812',
    'city_name' => 'Depok',
    'province_name' => 'Jabar',
    'address' => 'Jl',
    'subtotal' => 100000,
    'discount' => 0,
    'shipping_cost' => 0,
    'total' => 100000,
    'weight' => 100,
    'payment_method' => 'manual_transfer',
]);

[$res1, $res2] = $spawnPair(
    ['db' => DB_NAME, 'op' => 'transition', 'order_id' => $order->id, 'to' => 'paid'],
    ['db' => DB_NAME, 'op' => 'transition', 'order_id' => $order->id, 'to' => 'cancelled'],
);

echo 'r1: '.json_encode($res1)."\n";
echo 'r2: '.json_encode($res2)."\n";

$status = $order->fresh()->status;
$rows = DB::table('order_status_history')->where('order_id', $order->id)->orderBy('id')->get(['from', 'to']);

// THE invariant under CAS: history is a single linear chain —
// each row's `from` must equal the previous row's `to` (first: pending).
$chainOk = true;
$expected = 'pending';

foreach ($rows as $row) {
    if ($row->from !== $expected) {
        $chainOk = false;
        break;
    }
    $expected = $row->to;
}

echo 'final status: '.$status.', chain linear: '.var_export($chainOk, true)."\n";

$pass3 = in_array($status, ['paid', 'cancelled'], true) && $chainOk;

echo $pass3 ? "PASS: single deterministic transition\n" : "FAIL\n";

echo "\n=========================\n";
$all = $pass1 && $pass2 && $pass3;
echo $all ? "ALL CONCURRENCY TESTS PASS\n" : "SOME TESTS FAILED\n";
exit($all ? 0 : 1);
