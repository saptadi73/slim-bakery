<?php

require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/');
$dotenv->safeLoad();

use Illuminate\Database\Capsule\Manager as Capsule;

echo "=== MENJALANKAN MIGRASI ===\n";

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

echo "Koneksi ke: " . $_ENV['DB_HOST'] . ":" . ($_ENV['DB_PORT'] ?? 5432) . "/" . $_ENV['DB_DATABASE'] . "\n\n";

// Migrasi users dan roles
if (!$schema->hasTable('users')) {
    $schema->create('users', function ($t) {
        $t->bigIncrements('id');
        $t->string('name');
        $t->string('email')->unique();
        $t->string('password');
        $t->timestamps();
    });
    echo "✓ Tabel users dibuat.\n";
}

if (!$schema->hasTable('roles')) {
    $schema->create('roles', function ($t) {
        $t->bigIncrements('id');
        $t->string('name')->unique();
        $t->string('label')->nullable();
        $t->timestamps();
    });
    echo "✓ Tabel roles dibuat.\n";
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
    echo "✓ Tabel role_user dibuat.\n";
}

// Seed roles
\App\Models\Role::firstOrCreate(['name' => 'admin'], ['label' => 'Administrator']);
\App\Models\Role::firstOrCreate(['name' => 'pegawai'], ['label' => 'Pegawai']);
\App\Models\Role::firstOrCreate(['name' => 'user'], ['label' => 'User']);
echo "✓ Role default ditambahkan.\n\n";

echo "=== CEK TABEL YANG ADA ===\n";

try {
    $pdo = Capsule::connection()->getPdo();
    $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name;");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Tabel yang ada (" . count($tables) . "):\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }

    if (in_array('users', $tables) && in_array('roles', $tables)) {
        echo "\n✓ Migrasi berhasil! Tabel users dan roles ada di remote.\n";
    } else {
        echo "\n✗ Migrasi gagal! Tabel tidak ditemukan.\n";
    }

} catch (Exception $e) {
    echo "Error checking tables: " . $e->getMessage() . "\n";
}
