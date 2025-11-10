<?php

require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/');
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

echo "Koneksi ke database: " . $_ENV['DB_HOST'] . ":" . ($_ENV['DB_PORT'] ?? 5432) . "/" . $_ENV['DB_DATABASE'] . "\n";

$tables = Capsule::connection()->getPdo()->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name;")->fetchAll(PDO::FETCH_COLUMN);

echo "Tabel yang ada:\n";
foreach ($tables as $table) {
    echo "- $table\n";
}

if (in_array('users', $tables)) {
    echo "\nTabel users ada. Migrasi berhasil di remote.\n";
} else {
    echo "\nTabel users tidak ada. Migrasi belum berhasil.\n";
}
