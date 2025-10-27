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
    // Check if providers table exists
    if ($schema->hasTable('providers')) {
        // Rename order_id to order_items_id if exists
        if ($schema->hasColumn('providers', 'order_id')) {
            // For PostgreSQL, renaming column and updating foreign key
            Capsule::connection()->statement('ALTER TABLE providers RENAME COLUMN order_id TO order_items_id');
            // Update foreign key if needed (assuming it was wrong)
            Capsule::connection()->statement('ALTER TABLE providers DROP CONSTRAINT IF EXISTS providers_order_id_foreign');
            $schema->table('providers', function ($table) {
                $table->foreign('order_items_id')->references('id')->on('order_items')->onDelete('cascade');
            });
            echo "Kolom 'order_id' berhasil diubah menjadi 'order_items_id' dan foreign key diperbarui.\n";
        } elseif (!$schema->hasColumn('providers', 'order_items_id')) {
            // If neither exists, add order_items_id
            $schema->table('providers', function ($table) {
                $table->unsignedBigInteger('order_items_id');
                $table->foreign('order_items_id')->references('id')->on('order_items')->onDelete('cascade');
            });
            echo "Kolom 'order_items_id' berhasil ditambahkan ke tabel providers.\n";
        } else {
            echo "Kolom 'order_items_id' sudah ada di tabel providers.\n";
        }
    } else {
        echo "Tabel providers tidak ditemukan. Migrasi ini hanya untuk memperbaiki tabel yang sudah ada.\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Re-enable foreign key checks
Capsule::connection()->getPdo()->exec('SET CONSTRAINTS ALL IMMEDIATE');

echo "Migrasi perbaikan providers selesai.\n";
