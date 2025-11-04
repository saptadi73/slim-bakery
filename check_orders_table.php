<?php
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

use Illuminate\Database\Capsule\Manager as Capsule;

$c = new Capsule();
$c->addConnection([
    'driver' => $_ENV['DB_DRIVER'] ?? 'pgsql',
    'host' => $_ENV['DB_HOST'] ?? '103.160.213.59',
    'database' => $_ENV['DB_DATABASE'] ?? 'bakery',
    'username' => $_ENV['DB_USERNAME'] ?? 'pgwarga',
    'password' => $_ENV['DB_PASSWORD'] ?? 'pgw4rg4PWD',
    'charset' => $_ENV['DB_CHARSET'] ?? 'utf8',
    'prefix' => '',
    'port' => (int)($_ENV['DB_PORT'] ?? 5432),
]);
$c->setAsGlobal();
$c->bootEloquent();

try {
    // Check if orders table exists
    $tables = Capsule::select('SELECT table_name FROM information_schema.tables WHERE table_schema = \'public\' AND table_name = \'orders\';');
    if (empty($tables)) {
        echo "Table 'orders' does not exist.\n";
        exit;
    }

    // Get columns of orders table
    $columns = Capsule::select('SELECT column_name, data_type, is_nullable, column_default FROM information_schema.columns WHERE table_schema = \'public\' AND table_name = \'orders\' ORDER BY ordinal_position;');
    echo "Columns in 'orders' table:\n";
    foreach ($columns as $column) {
        echo "- {$column->column_name} ({$column->data_type}) " . ($column->is_nullable === 'YES' ? 'NULL' : 'NOT NULL') . " DEFAULT: " . ($column->column_default ?? 'NULL') . "\n";
    }

    // Get max id
    $maxId = Capsule::select('SELECT MAX(id) as max_id FROM orders;');
    echo "\nMax ID in orders: " . ($maxId[0]->max_id ?? 0) . "\n";

    // Get sequence current value
    $seq = Capsule::select("SELECT last_value FROM orders_id_seq;");
    echo "Current sequence value: " . ($seq[0]->last_value ?? 'N/A') . "\n";

    // List all orders
    $orders = Capsule::select('SELECT id, no_order FROM orders ORDER BY id;');
    echo "\nOrders:\n";
    foreach ($orders as $order) {
        echo "- ID: {$order->id}, No Order: {$order->no_order}\n";
    }

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
