<?php

use App\Models\User;
use App\Services\Checkout;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

require __DIR__ . '/../../vendor/autoload.php';
putenv('PGCHANNELBINDING=disable');
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config([
    'app.env' => 'testing',
    'database.default' => 'pgsql',
    'database.connections.pgsql.host' => '127.0.0.1',
    'database.connections.pgsql.port' => 55432,
    'database.connections.pgsql.database' => 'veltrix_test',
    'database.connections.pgsql.username' => 'postgres',
    'database.connections.pgsql.password' => '',
    'database.connections.pgsql.url' => null,
    'database.connections.pgsql.sslmode' => 'disable',
    'cache.default' => 'array',
]);
DB::purge('pgsql');
[$script, $cashierId, $menuId, $key, $startAt] = $argv;
while (microtime(true) < (float) $startAt) {
    usleep(10000);
}
try {
    $order = app(Checkout::class)->complete(User::findOrFail($cashierId), [
        'idempotency_key' => $key,
        'items' => [
            [
                'branch_product_id' => (int) $menuId,
                'quantity' => 1,
                'expected_price' => 22000,
            ],
        ],
        'method' => 'cash',
        'tendered' => 50000,
    ]);
    echo json_encode(['result' => 'completed', 'order_id' => $order->id]);
} catch (ValidationException $exception) {
    echo json_encode(['result' => 'rejected', 'errors' => $exception->errors()]);
}
