<?php
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/');
$dotenv->safeLoad();

use Illuminate\Database\Capsule\Manager as Capsule;
use App\Models\Receive;
use App\Models\ReceiveItem;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;

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

// Test relationships
$deliveryOrder = DeliveryOrder::first();
if ($deliveryOrder) {
    echo 'DeliveryOrder has receives: ' . $deliveryOrder->receives()->count() . PHP_EOL;
} else {
    echo 'No DeliveryOrder found' . PHP_EOL;
}

$deliveryOrderItem = DeliveryOrderItem::first();
if ($deliveryOrderItem) {
    echo 'DeliveryOrderItem has receiveItems: ' . $deliveryOrderItem->receiveItems()->count() . PHP_EOL;
} else {
    echo 'No DeliveryOrderItem found' . PHP_EOL;
}

$receive = Receive::first();
if ($receive) {
    echo 'Receive has receiveItems: ' . $receive->receiveItems()->count() . PHP_EOL;
    echo 'Receive belongs to DeliveryOrder: ' . ($receive->deliveryOrder ? 'Yes' : 'No') . PHP_EOL;
} else {
    echo 'No Receive found' . PHP_EOL;
}

$receiveItem = ReceiveItem::first();
if ($receiveItem) {
    echo 'ReceiveItem belongs to Receive: ' . ($receiveItem->receive ? 'Yes' : 'No') . PHP_EOL;
    echo 'ReceiveItem belongs to DeliveryOrderItem: ' . ($receiveItem->deliveryOrderItem ? 'Yes' : 'No') . PHP_EOL;
} else {
    echo 'No ReceiveItem found' . PHP_EOL;
}
