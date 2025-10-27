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

try {
    // Check if the enum type exists
    $typeExists = Capsule::select("SELECT 1 FROM pg_type WHERE typname = 'orders_status_order_enum';");
    if (empty($typeExists)) {
        echo "Enum type 'orders_status_order_enum' does not exist.\n";
    } else {
        $result = Capsule::select("SELECT enumtypid, enumlabel FROM pg_enum WHERE enumtypid = (SELECT oid FROM pg_type WHERE typname = 'orders_status_order_enum');");
        echo "Enum values for orders_status_order_enum:\n";
        foreach ($result as $row) {
            echo "- " . $row->enumlabel . "\n";
        }
        if (empty($result)) {
            echo "No enum values found. The enum might be empty.\n";
        }
    }

    // Also check the table structure
    $columns = Capsule::select("SELECT column_name, data_type, udt_name FROM information_schema.columns WHERE table_name = 'orders' AND column_name = 'status_order';");
    if (!empty($columns)) {
        $col = $columns[0];
        echo "\nTable 'orders' column 'status_order':\n";
        echo "- Data type: " . $col->data_type . "\n";
        echo "- UDT name: " . $col->udt_name . "\n";
    } else {
        echo "\nColumn 'status_order' not found in table 'orders'.\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
