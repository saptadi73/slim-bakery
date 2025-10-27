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

/** delivery_orders table */
if (!$schema->hasTable('delivery_orders')) {
    $schema->create('delivery_orders', function ($t) {
        $t->bigIncrements('id');
        $t->string('no_do')->unique();
        $t->string('pic');
        $t->timestamp('tanggal');
        $t->timestamps();
    });
    echo "Tabel delivery_orders dibuat.\n";
}

/** delivery_order_items table */
if (!$schema->hasTable('delivery_order_items')) {
    $schema->create('delivery_order_items', function ($t) {
        $t->bigIncrements('id');
        $t->unsignedBigInteger('delivery_order_id');
        $t->unsignedBigInteger('provider_id');
        $t->integer('quantity')->default(0);
        $t->string('pic');
        $t->date('tanggal');
        $t->timestamps();

        $t->foreign('delivery_order_id')->references('id')->on('delivery_orders')->onDelete('cascade');
        $t->foreign('provider_id')->references('id')->on('providers')->onDelete('cascade');
    });
    echo "Tabel delivery_order_items dibuat.\n";
}

// Re-enable foreign key checks
Capsule::connection()->getPdo()->exec('SET CONSTRAINTS ALL IMMEDIATE');

echo "Migrasi delivery orders selesai.\n";
