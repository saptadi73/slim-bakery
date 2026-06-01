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
    'port' => (int) ($_ENV['DB_PORT'] ?? 5432),
]);
$c->setAsGlobal();
$c->bootEloquent();

$schema = Capsule::schema();

Capsule::connection()->getPdo()->exec('SET CONSTRAINTS ALL DEFERRED');

if ($schema->hasTable('products')) {
    if (!$schema->hasColumn('products', 'harga')) {
        $schema->table('products', function ($table) {
            $table->decimal('harga', 12, 2)->default(0);
        });
        echo "Kolom harga berhasil ditambahkan ke tabel products.\n";

        if ($schema->hasColumn('products', 'harga_jual')) {
            Capsule::statement('UPDATE products SET harga = COALESCE(harga_jual, 0)');
            echo "Nilai harga diinisialisasi dari kolom harga_jual.\n";
        }
    } else {
        echo "Kolom harga sudah ada di tabel products.\n";
    }
} else {
    echo "Tabel products tidak ditemukan.\n";
}

Capsule::connection()->getPdo()->exec('SET CONSTRAINTS ALL IMMEDIATE');

echo "Migrasi penambahan harga ke products selesai.\n";
