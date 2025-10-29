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

/** receives table */
if (!$schema->hasTable('receives')) {
    $schema->create('receives', function ($t) {
        $t->bigIncrements('id');
        $t->string('no_rec')->unique();
        $t->string('pic');
        $t->timestamp('tanggal');
        $t->unsignedBigInteger('delivery_order_id');
        $t->text('keterangan')->nullable();
        $t->timestamps();

        $t->foreign('delivery_order_id')->references('id')->on('delivery_orders')->onDelete('cascade');
    });
    echo "Tabel receives dibuat.\n";
}

/** receive_items table */
if (!$schema->hasTable('receive_items')) {
    $schema->create('receive_items', function ($t) {
        $t->bigIncrements('id');
        $t->unsignedBigInteger('receive_id');
        $t->unsignedBigInteger('delivery_order_items_id');
        $t->integer('quantity')->default(0);
        $t->string('pic');
        $t->date('tanggal');
        $t->timestamps();

        $t->foreign('receive_id')->references('id')->on('receives')->onDelete('cascade');
        $t->foreign('delivery_order_items_id')->references('id')->on('delivery_order_items')->onDelete('cascade');
    });
    echo "Tabel receive_items dibuat.\n";
}

// Re-enable foreign key checks
Capsule::connection()->getPdo()->exec('SET CONSTRAINTS ALL IMMEDIATE');

echo "Migrasi receives selesai.\n";
