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

echo "Testing DeliveryOrder and DeliveryOrderItem models...\n";

// Test table existence
$schema = Capsule::schema();
if ($schema->hasTable('delivery_orders')) {
    echo "✓ delivery_orders table exists.\n";
} else {
    echo "✗ delivery_orders table does not exist.\n";
}

if ($schema->hasTable('delivery_order_items')) {
    echo "✓ delivery_order_items table exists.\n";
} else {
    echo "✗ delivery_order_items table does not exist.\n";
}

// Test column structure for delivery_orders
$columns = $schema->getColumnListing('delivery_orders');
$expectedColumns = ['id', 'no_do', 'pic', 'tanggal', 'created_at', 'updated_at'];
foreach ($expectedColumns as $col) {
    if (in_array($col, $columns)) {
        echo "✓ delivery_orders has column '$col'.\n";
    } else {
        echo "✗ delivery_orders missing column '$col'.\n";
    }
}

// Test column structure for delivery_order_items
$columns = $schema->getColumnListing('delivery_order_items');
$expectedColumns = ['id', 'delivery_order_id', 'provider_id', 'quantity', 'pic', 'tanggal', 'created_at', 'updated_at'];
foreach ($expectedColumns as $col) {
    if (in_array($col, $columns)) {
        echo "✓ delivery_order_items has column '$col'.\n";
    } else {
        echo "✗ delivery_order_items missing column '$col'.\n";
    }
}

// Test model creation and relationships
try {
    $deliveryOrder = new \App\Models\DeliveryOrder();
    $deliveryOrder->no_do = 'DO-' . str_pad((string)rand(10000, 99999), 5, '0', STR_PAD_LEFT);
    $deliveryOrder->pic = 'Test PIC';
    $deliveryOrder->tanggal = \Carbon\Carbon::now();
    $deliveryOrder->save();
    echo "✓ DeliveryOrder model created successfully.\n";

    $deliveryOrderItem = new \App\Models\DeliveryOrderItem();
    $deliveryOrderItem->delivery_order_id = $deliveryOrder->id;
    $deliveryOrderItem->provider_id = 1; // Assuming provider with id 1 exists
    $deliveryOrderItem->quantity = 10;
    $deliveryOrderItem->pic = 'Test PIC Item';
    $deliveryOrderItem->tanggal = \Carbon\Carbon::now()->toDateString();
    $deliveryOrderItem->save();
    echo "✓ DeliveryOrderItem model created successfully.\n";

    // Test relationships
    $loadedOrder = \App\Models\DeliveryOrder::with('deliveryOrderItems')->find($deliveryOrder->id);
    if ($loadedOrder && $loadedOrder->deliveryOrderItems->count() > 0) {
        echo "✓ DeliveryOrder hasMany deliveryOrderItems relationship works.\n";
    } else {
        echo "✗ DeliveryOrder hasMany deliveryOrderItems relationship failed.\n";
    }

    $loadedItem = \App\Models\DeliveryOrderItem::with('deliveryOrder', 'provider')->find($deliveryOrderItem->id);
    if ($loadedItem && $loadedItem->deliveryOrder) {
        echo "✓ DeliveryOrderItem belongsTo deliveryOrder relationship works.\n";
    } else {
        echo "✗ DeliveryOrderItem belongsTo deliveryOrder relationship failed.\n";
    }

    if ($loadedItem && $loadedItem->provider) {
        echo "✓ DeliveryOrderItem belongsTo provider relationship works.\n";
    } else {
        echo "✗ DeliveryOrderItem belongsTo provider relationship failed.\n";
    }

    // Test casts
    if (is_a($loadedOrder->tanggal, 'Carbon\Carbon')) {
        echo "✓ DeliveryOrder tanggal cast to datetime works.\n";
    } else {
        echo "✗ DeliveryOrder tanggal cast to datetime failed.\n";
    }

    if (is_string($loadedItem->tanggal)) {
        echo "✓ DeliveryOrderItem tanggal cast to date works.\n";
    } else {
        echo "✗ DeliveryOrderItem tanggal cast to date failed.\n";
    }

    // Clean up
    $deliveryOrderItem->delete();
    $deliveryOrder->delete();
    echo "✓ Test data cleaned up.\n";

} catch (\Exception $e) {
    echo "✗ Error during model testing: " . $e->getMessage() . "\n";
}

echo "Model testing completed.\n";
