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
    // Check if order_items table exists
    $tables = Capsule::select('SELECT table_name FROM information_schema.tables WHERE table_schema = \'public\' AND table_name = \'order_items\';');
    if (empty($tables)) {
        echo "Table 'order_items' does not exist.\n";
        exit;
    }

    // Get columns of order_items table
    $columns = Capsule::select('SELECT column_name, data_type, is_nullable, column_default FROM information_schema.columns WHERE table_schema = \'public\' AND table_name = \'order_items\' ORDER BY ordinal_position;');
    echo "Columns in 'order_items' table:\n";
    foreach ($columns as $column) {
        echo "- {$column->column_name} ({$column->data_type}) " . ($column->is_nullable === 'YES' ? 'NULL' : 'NOT NULL') . " DEFAULT: " . ($column->column_default ?? 'NULL') . "\n";
    }

    // Get max id
    $maxId = Capsule::select('SELECT MAX(id) as max_id FROM order_items;');
    echo "\nMax ID in order_items: " . ($maxId[0]->max_id ?? 0) . "\n";

    // Get sequence current value
    $seq = Capsule::select("SELECT last_value FROM order_items_id_seq;");
    echo "Current sequence value: " . ($seq[0]->last_value ?? 'N/A') . "\n";

    // List all order_items
    $orderItems = Capsule::select('SELECT id, order_id, product_id FROM order_items ORDER BY id LIMIT 10;');
    echo "\nFirst 10 order_items:\n";
    foreach ($orderItems as $item) {
        echo "- ID: {$item->id}, Order ID: {$item->order_id}, Product ID: {$item->product_id}\n";
    }

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
