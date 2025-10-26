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

// Drop and recreate product_movings table with updated structure
Capsule::schema()->dropIfExists('product_movings');

if (!$schema->hasTable('product_movings')) {
    $schema->create('product_movings', function ($t) {
        $t->bigIncrements('id');
        $t->unsignedBigInteger('product_id');
        $t->enum('type', ['income', 'outcome', 'transfer'])->default('income'); // income, outcome, transfer
        $t->unsignedBigInteger('outlet_id')->nullable(); // outlet_id instead of terminal
        $t->integer('quantity')->default(0);
        $t->date('tanggal')->nullable();
        $t->string('pic')->nullable();
        $t->string('keterangan')->nullable();
        $t->timestamps();

        $t->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        $t->foreign('outlet_id')->references('id')->on('outlets')->onDelete('set null');
    });
    echo "Tabel product_movings diperbarui dengan outlet_id.\n";
}

// Re-enable foreign key checks
Capsule::connection()->getPdo()->exec('SET CONSTRAINTS ALL IMMEDIATE');

echo "Migrasi product_movings selesai.\n";
