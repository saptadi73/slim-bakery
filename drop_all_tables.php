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

$schema = Capsule::schema();

echo "Menghapus semua tabel di database remote...\n";

// Disable foreign key checks for PostgreSQL
Capsule::connection()->getPdo()->exec('SET CONSTRAINTS ALL DEFERRED');

// Drop tables in reverse order to avoid foreign key constraints
$tables = [
    'receive_items',
    'receives',
    'delivery_order_items',
    'delivery_orders',
    'user_outlet',
    'delivers',
    'providers',
    'order_items',
    'orders',
    'product_movings',
    'inventories',
    'products',
    'categories',
    'outlets',
    'auth_tokens',
    'role_user',
    'users',
    'roles'
];

foreach ($tables as $table) {
    if ($schema->hasTable($table)) {
        $schema->drop($table);
        echo "Tabel $table dihapus.\n";
    } else {
        echo "Tabel $table tidak ada.\n";
    }
}

// Re-enable foreign key checks
Capsule::connection()->getPdo()->exec('SET CONSTRAINTS ALL IMMEDIATE');

echo "Semua tabel berhasil dihapus.\n";
