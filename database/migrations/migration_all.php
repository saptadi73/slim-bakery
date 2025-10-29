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

/** users table (without email_verified_at + softDeletes) */
Capsule::schema()->dropIfExists('user_outlet');
Capsule::schema()->dropIfExists('role_user');
Capsule::schema()->dropIfExists('users');
if (!$schema->hasTable('users')) {
    $schema->create('users', function ($t) {
        $t->bigIncrements('id');
        $t->string('name');
        $t->string('email')->unique();
        $t->string('password');
        $t->timestamps(); // Tanpa email_verified_at dan softDeletes
    });
    echo "Tabel users dibuat.\n";
} else {
    if (!$schema->hasColumn('users', 'password')) {
        $schema->table('users', fn($t) => $t->string('password'));
        echo "Kolom password ditambah.\n";
    }
}

/** roles + pivot */
Capsule::schema()->dropIfExists('roles');
if (!$schema->hasTable('roles')) {
    $schema->create('roles', function ($t) {
        $t->bigIncrements('id');
        $t->string('name')->unique();
        $t->string('label')->nullable();
        $t->timestamps();
    });
    echo "Tabel roles dibuat.\n";
}

if (!$schema->hasTable('role_user')) {
    $schema->create('role_user', function ($t) {
        $t->unsignedBigInteger('role_id');
        $t->unsignedBigInteger('user_id');
        $t->primary(['role_id', 'user_id']);
        $t->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        $t->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        $t->timestamps();
    });
    echo "Tabel role_user dibuat.\n";
}

/** auth_tokens: refresh/email_verify/password_reset */
Capsule::schema()->dropIfExists('auth_tokens');
if (!$schema->hasTable('auth_tokens')) {
    $schema->create('auth_tokens', function ($t) {
        $t->bigIncrements('id');
        $t->unsignedBigInteger('user_id');
        $t->enum('type', ['refresh', 'email_verify', 'password_reset']);
        $t->string('token_hash', 128); // simpan hash, bukan token asli
        $t->timestamp('expires_at');
        $t->timestamp('revoked_at')->nullable();
        $t->json('meta')->nullable();  // user agent, ip, dll
        $t->timestamps();
        $t->index(['user_id', 'type']);
    });
    echo "Tabel auth_tokens dibuat.\n";
}

// Seed role dasar
$admin = \App\Models\Role::firstOrCreate(['name' => 'admin'], ['label' => 'Administrator']);
$pegawai = \App\Models\Role::firstOrCreate(['name' => 'pegawai'], ['label' => 'Pegawai']);
$userRole = \App\Models\Role::firstOrCreate(['name' => 'user'], ['label' => 'User']);

// Set ID default untuk role
$admin->id = 1; // ID untuk admin
$pegawai->id = 2; // ID untuk teknisi
$userRole->id = 3; // ID untuk user

// Simpan role dengan ID yang sudah ditentukan
$admin->save();
$pegawai->save();
$userRole->save();

// jika ada user id=1, jadikan admin
$first = \App\Models\User::find(1);
if ($first) {
    $first->roles()->syncWithoutDetaching([$admin->id]); // Mengaitkan user dengan role admin
}

echo "Migrasi selesai dan role default ditambahkan.\n";

// Disable foreign key checks for PostgreSQL
Capsule::connection()->getPdo()->exec('SET CONSTRAINTS ALL DEFERRED');

// Drop tables in reverse order to avoid foreign key constraints
Capsule::schema()->dropIfExists('receive_items');
Capsule::schema()->dropIfExists('receives');
Capsule::schema()->dropIfExists('delivery_order_items');
Capsule::schema()->dropIfExists('delivery_orders');
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

    // Seed initial categories
    \App\Models\Category::create(['id' => 1, 'nama' => 'CAKE']);
    \App\Models\Category::create(['id' => 2, 'nama' => 'ROTI']);
    \App\Models\Category::create(['id' => 3, 'nama' => 'BOLU']);
    \App\Models\Category::create(['id' => 4, 'nama' => 'KEMASAN']);
    echo "Data awal categories ditambahkan.\n";
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
        $t->string('pic')->nullable();
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
        $t->enum('status_order', ['new', 'pending', 'approved', 'rejected', 'completed', 'delivered'])->default('new');
        $t->text('keterangan')->nullable();
        $t->timestamps();
    });
    echo "Tabel orders dibuat.\n";
}

// Alter enum to add 'delivered' if not exists
try {
    Capsule::connection()->statement("ALTER TYPE orders_status_order_enum ADD VALUE IF NOT EXISTS 'delivered';");
    echo "Enum status_order diperbarui dengan 'delivered'.\n";
} catch (\Exception $e) {
    echo "Enum sudah diperbarui atau error: " . $e->getMessage() . "\n";
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
        $t->enum('status', ['open', 'processed', 'delivered', 'provided'])->default('open');
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
        $t->unsignedBigInteger('order_items_id');
        $t->integer('quantity')->default(0);
        $t->date('tanggal')->nullable();
        $t->string('pic')->nullable();
        $t->timestamps();

        $t->foreign('order_items_id')->references('id')->on('order_items')->onDelete('cascade');
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

// Disable foreign key checks for PostgreSQL
Capsule::connection()->getPdo()->exec('SET CONSTRAINTS ALL DEFERRED');

/** delivery_orders table */
if (!$schema->hasTable('delivery_orders')) {
    $schema->create('delivery_orders', function ($t) {
        $t->bigIncrements('id');
        $t->string('no_do')->unique();
        $t->string('pic');
        $t->timestamp('tanggal');
        $t->enum('status', ['open', 'closed'])->default('open');
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
        $t->unsignedBigInteger('product_id');
        $t->integer('quantity')->default(0);
        $t->string('pic');
        $t->date('tanggal');
        $t->timestamps();

        $t->foreign('delivery_order_id')->references('id')->on('delivery_orders')->onDelete('cascade');
        $t->foreign('provider_id')->references('id')->on('providers')->onDelete('cascade');
        $t->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
    });
    echo "Tabel delivery_order_items dibuat.\n";
}

// Re-enable foreign key checks
Capsule::connection()->getPdo()->exec('SET CONSTRAINTS ALL IMMEDIATE');

echo "Migrasi delivery orders selesai.\n";

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
} else {
    // Jika tabel sudah ada, tambahkan kolom keterangan jika belum ada
    if (!$schema->hasColumn('receives', 'keterangan')) {
        $schema->table('receives', function ($table) {
            $table->text('keterangan')->nullable();
        });
        echo "Kolom keterangan berhasil ditambahkan ke tabel receives.\n";
    }
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

echo "Semua migrasi berhasil disatukan dalam satu file.\n";
?>
