<?php
require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

use Illuminate\Database\Capsule\Manager as Capsule;
use App\Models\User;
use App\Models\Outlet;
use App\Models\Order;

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
    echo "Starting smoke test: create user, outlet, and two orders...\n";

    // Create user
    $user = User::create([
        'name' => 'smoke-tester',
        'email' => 'smoke+' . time() . '@example.test',
        'password' => password_hash('secret', PASSWORD_BCRYPT),
    ]);
    echo "Created user id={$user->id}\n";

    // Create outlet
    $outlet = Outlet::create([
        'nama' => 'Smoke Outlet ' . time(),
        'kode' => 'SMOKE' . rand(1000,9999),
        'alamat' => 'Smoke address',
        'phone' => '000',
        'prioritas' => 0,
    ]);
    echo "Created outlet id={$outlet->id}\n";

    // Create two orders sequentially
    $order1 = Order::create([
        'no_order' => Order::generateNoOrder(),
        'outlet_id' => $outlet->id,
        'user_id' => $user->id,
        'total' => 100,
        'status' => 'open',
        'tanggal' => date('Y-m-d'),
    ]);

    echo "Order 1 -> id={$order1->id}, no_order={$order1->no_order}\n";

    $order2 = Order::create([
        'no_order' => Order::generateNoOrder(),
        'outlet_id' => $outlet->id,
        'user_id' => $user->id,
        'total' => 200,
        'status' => 'open',
        'tanggal' => date('Y-m-d'),
    ]);

    echo "Order 2 -> id={$order2->id}, no_order={$order2->no_order}\n";

    // List recent orders
    $recent = Order::orderBy('id', 'desc')->limit(5)->get();
    echo "\nRecent orders:\n";
    foreach ($recent as $o) {
        echo "- id={$o->id}, no_order={$o->no_order}, outlet_id={$o->outlet_id}, user_id={$o->user_id}\n";
    }

    echo "Smoke test completed.\n";

} catch (Throwable $t) {
    echo 'Error: ' . $t->getMessage() . PHP_EOL;
}
