<?php

require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/');
$dotenv->safeLoad();

use Illuminate\Database\Capsule\Manager as Capsule;

$c = new Capsule();
$c->addConnection([
    'driver' => $_ENV['DB_DRIVER'] ?? 'pgsql',
    'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
    'database' => $_ENV['DB_DATABASE'] ?? 'bakery',
    'username' => $_ENV['DB_USERNAME'] ?? 'openpg',
    'password' => $_ENV['DB_PASSWORD'] ?? 'openpgpwd',
    'charset' => $_ENV['DB_CHARSET'] ?? 'utf8',
    'prefix' => '',
    'port' => (int)($_ENV['DB_PORT'] ?? 5432),
]);
$c->setAsGlobal();
$c->bootEloquent();

echo "Testing DeliveryOrderService...\n";

// Test createDeliveryOrder
echo "Testing createDeliveryOrder...\n";
$service = new \App\Services\DeliveryOrderService();
$data = [
    'pic' => 'Test PIC',
    'items' => [
        [
            'provider_id' => 1, // Assuming provider exists
            'quantity' => 10,
            'pic' => 'Item PIC',
            'tanggal' => '2025-10-27'
        ]
    ]
];

try {
    $response = $service->createDeliveryOrder(new \Slim\Psr7\Response(), $data);
    echo "✓ createDeliveryOrder succeeded.\n";
} catch (\Exception $e) {
    echo "✗ createDeliveryOrder failed: " . $e->getMessage() . "\n";
}

// Test listDeliveryOrders
echo "Testing listDeliveryOrders...\n";
try {
    $response = $service->listDeliveryOrders(new \Slim\Psr7\Response());
    echo "✓ listDeliveryOrders succeeded.\n";
} catch (\Exception $e) {
    echo "✗ listDeliveryOrders failed: " . $e->getMessage() . "\n";
}

echo "DeliveryOrderService testing completed.\n";
