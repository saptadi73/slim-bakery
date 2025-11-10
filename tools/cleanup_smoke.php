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
    $pdo = $c->getConnection()->getPdo();
    $pdo->beginTransaction();

    // Find users created by smoke test
    $users = Capsule::select("SELECT id, email, name FROM users WHERE name = 'smoke-tester'");
    $userIds = array_map(fn($u) => $u->id, $users);

    // Find outlets created by smoke test
    $outlets = Capsule::select("SELECT id, nama FROM outlets WHERE nama LIKE 'Smoke Outlet %'");
    $outletIds = array_map(fn($o) => $o->id, $outlets);

    echo "Found " . count($userIds) . " smoke user(s) and " . count($outletIds) . " smoke outlet(s)\n";

    // Delete orders referencing these users or outlets
    if (!empty($userIds) || !empty($outletIds)) {
        $conds = [];
        if (!empty($userIds)) {
            $ids = implode(',', array_map('intval', $userIds));
            $conds[] = "user_id IN ($ids)";
        }
        if (!empty($outletIds)) {
            $ids2 = implode(',', array_map('intval', $outletIds));
            $conds[] = "outlet_id IN ($ids2)";
        }
        $where = implode(' OR ', $conds);

        // Delete order_items first if any (defensive)
        $deletedOrderItems = Capsule::delete("DELETE FROM order_items WHERE order_id IN (SELECT id FROM orders WHERE $where)");
        echo "Deleted $deletedOrderItems order_items rows\n";

        $deletedProviders = Capsule::delete("DELETE FROM providers WHERE order_items_id IN (SELECT id FROM order_items WHERE order_id IN (SELECT id FROM orders WHERE $where))");
        echo "Deleted $deletedProviders providers rows (if any)\n";

        $deletedOrders = Capsule::delete("DELETE FROM orders WHERE $where");
        echo "Deleted $deletedOrders orders\n";
    }

    // Delete outlets
    if (!empty($outletIds)) {
        $ids = implode(',', array_map('intval', $outletIds));
        $deletedOutlets = Capsule::delete("DELETE FROM outlets WHERE id IN ($ids)");
        echo "Deleted $deletedOutlets outlets\n";
    }

    // Delete users
    if (!empty($userIds)) {
        $ids = implode(',', array_map('intval', $userIds));
        $deletedUsers = Capsule::delete("DELETE FROM users WHERE id IN ($ids)");
        echo "Deleted $deletedUsers users\n";
    }

    $pdo->commit();
    echo "Cleanup transaction committed.\n";
} catch (Throwable $t) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo "Cleanup failed: " . $t->getMessage() . PHP_EOL;
    exit(1);
}
