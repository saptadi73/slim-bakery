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

if ($schema->hasTable('receives')) {
    if (!$schema->hasColumn('receives', 'keterangan')) {
        $schema->table('receives', function ($table) {
            $table->text('keterangan')->nullable();
        });
        echo "Kolom keterangan berhasil ditambahkan ke tabel receives.\n";
    } else {
        echo "Kolom keterangan sudah ada di tabel receives.\n";
    }
} else {
    echo "Tabel receives tidak ditemukan.\n";
}

// Re-enable foreign key checks
Capsule::connection()->getPdo()->exec('SET CONSTRAINTS ALL IMMEDIATE');

echo "Migrasi tambahan receives selesai.\n";
