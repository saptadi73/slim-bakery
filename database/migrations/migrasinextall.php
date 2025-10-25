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

// Drop tables
Capsule::schema()->dropIfExists('user_outlet');
Capsule::schema()->dropIfExists('delivers');
Capsule::schema()->dropIfExists('providers');
Capsule::schema()->dropIfExists('order_items');
Capsule::schema()->dropIfExists('orders');
Capsule::schema()->dropIfExists('product_movings');
Capsule::schema()->dropIfExists('inventories');
Capsule::schema()->dropIfExists('products');
Capsule::schema()->dropIfExists('categories');
Capsule::schema()->dropIfExists('outlets');

// Re-enable foreign key checks after migration (if needed)
Capsule::connection()->getPdo()->exec('SET CONSTRAINTS ALL IMMEDIATE');



/** outlets table */
if (!$schema->hasTable('outlets')) {
    $schema->create('outlets', function ($t) {
        $t->bigIncrements('id');
        $t->string('nama');
        $t->string('kode')->nullable();
        $t->string('alamat')->nullable();
        $t->string('phone')->nullable();
        $t->integer('prioritas')->default(0);
        $t->string('gambar')->nullable();
        $t->timestamps();
    });
    echo "Tabel outlets dibuat.\n";
}

if (!$schema->hasTable('categories')) {
    $schema->create('categories', function ($t) {
        $t->bigIncrements('id');
        $t->string('nama');
        $t->string('keterangan')->nullable();
        $t->timestamps();
    });
    echo "Tabel categories dibuat.\n";
}


/** products table */
if (!$schema->hasTable('products')) {
    $schema->create('products', function ($t) {
        $t->bigIncrements('id');
        $t->string('nama');
        $t->string('kode')->nullable();
        $t->string('gambar')->nullable();
        $t->unsignedBigInteger('category_id');
        $t->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        $t->timestamps();
    });
    echo "Tabel products dibuat.\n";
}
/** inventories table */
if (!$schema->hasTable('inventories')) {
    $schema->create('inventories', function ($t) {
        $t->bigIncrements('id');
        $t->unsignedBigInteger('product_id');
        $t->integer('quantity')->default(0);
        $t->date('tanggal')->nullable();
        $t->timestamps();

        $t->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        
        $t->unique(['product_id']);
    });
    echo "Tabel inventories dibuat.\n";
}
/** product_movings table */
if (!$schema->hasTable('product_movings')) {
    $schema->create('product_movings', function ($t) {
        $t->bigIncrements('id');
        $t->unsignedBigInteger('product_id');
        $t->enum('type', ['in', 'out', 'transfer'])->default('in'); // in, out, transfer
        $t->string('terminal')->nullable(); // terminal info
        $t->integer('quantity')->default(0);
        $t->date('tanggal')->nullable();
        $t->string('pic')->nullable();
        $t->string('keterangan')->nullable();
        $t->timestamps();

        $t->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
    });
    echo "Tabel product_movings dibuat.\n";
}

/** orders table */
if (!$schema->hasTable('orders')) {
    $schema->create('orders', function ($t) {
        $t->bigIncrements('id');
        $t->string('no_order')->unique();
        $t->string('outlet_name')->nullable();
        $t->string('pic_name')->nullable();
        $t->timestamp('tanggal')->nullable();
        $t->timestamps();
    });
    echo "Tabel orders dibuat.\n";
}

/** order_items table */
if (!$schema->hasTable('order_items')) {
    $schema->create('order_items', function ($t) {
        $t->bigIncrements('id');
        $t->unsignedBigInteger('order_id');
        $t->unsignedBigInteger('product_id');
        $t->unsignedBigInteger('outlet_id');
        $t->integer('quantity')->default(0);
        $t->date('tanggal')->nullable();
        $t->string('pic')->nullable();
        $t->enum('status', ['open', 'processed', 'delivered'])->default('open');
        $t->timestamps();

        $t->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        $t->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        $t->foreign('outlet_id')->references('id')->on('outlets')->onDelete('cascade');
    });
    echo "Tabel order_items dibuat.\n";
}
/** providers table */
if (!$schema->hasTable('providers')) {
    $schema->create('providers', function ($t) {
        $t->bigIncrements('id');
        $t->unsignedBigInteger('order_id');
        $t->integer('quantity')->default(0);
        $t->date('tanggal')->nullable();
        $t->string('pic')->nullable();
        $t->string('nopro')->uqique();
        $t->timestamps();

        $t->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
    });
    echo "Tabel providers dibuat.\n";
}

/** delivers table */
if (!$schema->hasTable('delivers')) {
    $schema->create('delivers', function ($t) {
        $t->bigIncrements('id');
        $t->unsignedBigInteger('order_id');
        $t->unsignedBigInteger('provider_id');
        $t->integer('quantity')->default(0);
        $t->date('tanggal')->nullable();
        $t->string('pic')->nullable();
        $t->string('receiver')->nullable();
        $t->timestamps();

        $t->foreign('provider_id')->references('id')->on('providers')->onDelete('cascade')->onUpdate('cascade');
    });
    echo "Tabel delivers dibuat.\n";
}   
echo "Migrasi selesai.\n";

/** Pivot table user_outlet */
if (!$schema->hasTable('user_outlet')) {
    $schema->create('user_outlet', function ($t) {
        $t->unsignedBigInteger('user_id');
        $t->unsignedBigInteger('outlet_id');
        $t->primary(['user_id', 'outlet_id']);
        $t->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        $t->foreign('outlet_id')->references('id')->on('outlets')->onDelete('cascade');
        $t->timestamps();
    });
    echo "Tabel user_outlet dibuat.\n";
}

// Seed default outlet
// $defaultOutlet = \App\Models\Outlet::firstOrCreate(
//     ['kode' => 'DEFAULT'],
//     [
//         'nama' => 'Default Outlet',
//         'alamat' => 'Default Address',
//         'phone' => '0000000000',
//         'prioritas' => 0,
//         'gambar' => 'outlet_default.jpg'
//     ]
// );

// echo "Default outlet seeded.\n";
