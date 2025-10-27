<?php

require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable('./');
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

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Provider;
use App\Services\OrderService;
use Slim\Psr7\Response;
use Carbon\Carbon;

// Create a test order
$order = Order::create([
    'no_order' => 'TEST-ORDER-' . time(),
    'outlet_name' => 'Test Outlet',
    'pic_name' => 'Test PIC',
    'tanggal' => Carbon::now(),
    'status_order' => 'new',
    'keterangan' => 'Test order for provider_id'
]);

// Create order items
$orderItem1 = OrderItem::create([
    'order_id' => $order->id,
    'product_id' => 1,
    'outlet_id' => 1,
    'quantity' => 10,
    'pic' => 'Test PIC',
    'tanggal' => Carbon::now(),
    'status' => 'open'
]);

$orderItem2 = OrderItem::create([
    'order_id' => $order->id,
    'product_id' => 2,
    'outlet_id' => 1,
    'quantity' => 5,
    'pic' => 'Test PIC',
    'tanggal' => Carbon::now(),
    'status' => 'open'
]);

// Create provider for first item
$provider = Provider::create([
    'order_items_id' => $orderItem1->id,
    'quantity' => 5,
    'tanggal' => Carbon::now(),
    'pic' => 'Test PIC'
]);

echo 'Test data created. Order ID: ' . $order->id . PHP_EOL;
echo 'OrderItem1 ID: ' . $orderItem1->id . ' (has provider ID: ' . $provider->id . ')' . PHP_EOL;
echo 'OrderItem2 ID: ' . $orderItem2->id . ' (no provider)' . PHP_EOL;

// Test the getOrderWithProductId method
$response = new Response();
$result = OrderService::getOrderWithProductId($response, $order->id);

$body = $result->getBody();
$content = $body->getContents();
$data = json_decode($content, true);

echo PHP_EOL . 'Testing getOrderWithProductId response:' . PHP_EOL;
echo 'Raw content: ' . $content . PHP_EOL;
if (isset($data['data']['orderItems'])) {
    foreach ($data['data']['orderItems'] as $item) {
        echo 'OrderItem ID: ' . $item['id'] . ', provider_id: ' . ($item['provider_id'] ?? 'null') . PHP_EOL;
    }
} else {
    echo 'No orderItems found in response' . PHP_EOL;
    echo 'Response status: ' . $result->getStatusCode() . PHP_EOL;
    echo 'Response data: ' . PHP_EOL;
    print_r($data);
}
