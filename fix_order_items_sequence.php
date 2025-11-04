<?php
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

use Illuminate\Database\Capsule\Manager as Capsule;

$c = new Capsule();
$c->addConnection([
    'driver' => $_ENV['DB_DRIVER'] ?? 'pgsql',
    'host' => $_ENV['DB_HOST'] ?? '103.160.213.59',
    'database' => $_ENV['DB_DATABASE'] ?? 'bakery',
    'username' => $_ENV['DB_USERNAME'] ?? 'pgwarga',
    'password' => $_ENV['DB_PASSWORD'] ?? 'pgw4rg4PWD',
    'charset' => $_ENV['DB_CHARSET'] ?? 'utf8',
    'prefix' => '',
    'port' => (int)($_ENV['DB_PORT'] ?? 5432),
]);
$c->setAsGlobal();
$c->bootEloquent();

try {
    // Get max id
    $maxId = Capsule::select('SELECT MAX(id) as max_id FROM order_items;');
    $maxIdValue = $maxId[0]->max_id ?? 0;

    echo "Max ID in order_items: $maxIdValue\n";

    // Reset sequence to max_id + 1
    $newSeqValue = $maxIdValue + 1;
    Capsule::statement("SELECT setval('order_items_id_seq', $newSeqValue);");

    echo "Sequence reset to: $newSeqValue\n";

    // Verify
    $seq = Capsule::select("SELECT last_value FROM order_items_id_seq;");
    echo "Current sequence value: " . $seq[0]->last_value . "\n";

    echo "Sequence fixed successfully.\n";

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
