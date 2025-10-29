<?php
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
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

try {
    // Check if receives table exists
    $tables = Capsule::select('SELECT table_name FROM information_schema.tables WHERE table_schema = \'public\' AND table_name = \'receives\';');
    if (empty($tables)) {
        echo "Table 'receives' does not exist.\n";
        exit;
    }

    // Get columns of receives table
    $columns = Capsule::select('SELECT column_name, data_type, is_nullable, column_default FROM information_schema.columns WHERE table_schema = \'public\' AND table_name = \'receives\' ORDER BY ordinal_position;');
    echo "Columns in 'receives' table:\n";
    foreach ($columns as $column) {
        echo "- {$column->column_name} ({$column->data_type}) " . ($column->is_nullable === 'YES' ? 'NULL' : 'NOT NULL') . " DEFAULT: " . ($column->column_default ?? 'NULL') . "\n";
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
