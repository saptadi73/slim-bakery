<?php
require __DIR__ . '/../vendor/autoload.php';
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

try {
    // Check whether order_no_seq exists
    $exists = Capsule::select("SELECT relname FROM pg_class WHERE relkind = 'S' AND relname = 'order_no_seq';");
    if (empty($exists)) {
        echo "Sequence 'order_no_seq' does not exist.\n";
        exit(0);
    }

    // Fetch last_value and is_called
    $row = Capsule::select("SELECT last_value, is_called FROM order_no_seq");
    if (!empty($row)) {
        $r = $row[0];
        echo "order_no_seq -> last_value: {$r->last_value}, is_called: {$r->is_called}\n";
    } else {
        echo "Could not read order_no_seq value.\n";
    }

    // Also show current nextval (without advancing sequence we can use setval/current value info) - we will call nextval to show next number then optionally reset
    $next = Capsule::select("SELECT nextval('order_no_seq') as nextval");
    if (!empty($next)) {
        echo "nextval(order_no_seq) returned: {$next[0]->nextval}\n";
    }

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
