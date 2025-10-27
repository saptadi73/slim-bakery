<?php

require __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
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

// Disable foreign key checks for PostgreSQL
Capsule::connection()->getPdo()->exec('SET CONSTRAINTS ALL DEFERRED');

try {
    // Drop the existing check constraint
    Capsule::connection()->statement('ALTER TABLE order_items DROP CONSTRAINT IF EXISTS order_items_status_check');

    // Add the new check constraint with 'provided' status
    Capsule::connection()->statement("ALTER TABLE order_items ADD CONSTRAINT order_items_status_check CHECK (status IN ('open', 'processed', 'delivered', 'provided'))");

    echo "Constraint order_items_status_check berhasil diperbarui untuk mendukung status 'provided'.\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Re-enable foreign key checks
Capsule::connection()->getPdo()->exec('SET CONSTRAINTS ALL IMMEDIATE');

echo "Migrasi perbaikan constraint status order_items selesai.\n";
