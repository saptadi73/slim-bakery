<?php
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

use Illuminate\Database\Capsule\Manager as Capsule;
use App\Models\OrderItem;
use App\Models\Provider;
use App\Models\Deliver;

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

echo "=== CRITICAL PATH TESTING FOR PROVIDER MODEL ===\n\n";

// Test 1: Check if status 'provided' is accepted
echo "Test 1: Testing status 'provided' constraint\n";
try {
    $orderItem = OrderItem::first();
    if ($orderItem) {
        $originalStatus = $orderItem->status;
        $orderItem->status = 'provided';
        $orderItem->save();
        echo "✓ Status 'provided' accepted successfully\n";

        // Restore original status
        $orderItem->status = $originalStatus;
        $orderItem->save();
        echo "✓ Status restored to original\n";
    } else {
        echo "⚠ No order items found to test\n";
    }
} catch (Exception $e) {
    echo "✗ Error testing status 'provided': " . $e->getMessage() . "\n";
}

// Test 2: Test Provider-OrderItem relationship
echo "\nTest 2: Testing Provider-OrderItem relationship\n";
try {
    $orderItem = OrderItem::first();
    if ($orderItem) {
        $provider = Provider::create([
            'order_items_id' => $orderItem->id,
            'quantity' => 5,
            'tanggal' => now(),
            'pic' => 'Test PIC'
        ]);

        // Test relationship from Provider to OrderItem
        $relatedOrderItem = $provider->orders;
        if ($relatedOrderItem && $relatedOrderItem->id == $orderItem->id) {
            echo "✓ Provider->OrderItem relationship works\n";
        } else {
            echo "✗ Provider->OrderItem relationship failed\n";
        }

        // Test relationship from OrderItem to Providers
        $providers = $orderItem->providers;
        if ($providers && $providers->contains('id', $provider->id)) {
            echo "✓ OrderItem->Providers relationship works\n";
        } else {
            echo "✗ OrderItem->Providers relationship failed\n";
        }

        // Clean up
        $provider->delete();
        echo "✓ Test provider cleaned up\n";
    } else {
        echo "⚠ No order items found to test relationships\n";
    }
} catch (Exception $e) {
    echo "✗ Error testing relationships: " . $e->getMessage() . "\n";
}

// Test 3: Test Provider-Deliver relationship
echo "\nTest 3: Testing Provider-Deliver relationship\n";
try {
    $provider = Provider::first();
    if ($provider) {
        $deliver = Deliver::create([
            'provider_id' => $provider->id,
            'order_id' => $provider->orders->order_id ?? 1,
            'quantity' => 3,
            'status' => 'pending',
            'tanggal' => now(),
            'pic' => 'Test PIC',
            'receiver' => 'Test Receiver'
        ]);

        // Test relationship from Deliver to Provider
        $relatedProvider = $deliver->provider;
        if ($relatedProvider && $relatedProvider->id == $provider->id) {
            echo "✓ Deliver->Provider relationship works\n";
        } else {
            echo "✗ Deliver->Provider relationship failed\n";
        }

        // Test relationship from Provider to Delivers
        $delivers = $provider->delivers;
        if ($delivers && $delivers->contains('id', $deliver->id)) {
            echo "✓ Provider->Delivers relationship works\n";
        } else {
            echo "✗ Provider->Delivers relationship failed\n";
        }

        // Clean up
        $deliver->delete();
        echo "✓ Test deliver cleaned up\n";
    } else {
        echo "⚠ No providers found to test Deliver relationship\n";
    }
} catch (Exception $e) {
    echo "✗ Error testing Deliver relationships: " . $e->getMessage() . "\n";
}

// Test 4: Test foreign key constraints
echo "\nTest 4: Testing foreign key constraints\n";
try {
    // Try to create provider with non-existent order_items_id
    $invalidProvider = new Provider([
        'order_items_id' => 999999, // Non-existent ID
        'quantity' => 1,
        'tanggal' => now(),
        'pic' => 'Test'
    ]);

    try {
        $invalidProvider->save();
        echo "✗ Foreign key constraint not enforced (this should fail)\n";
        $invalidProvider->delete(); // Clean up if it somehow succeeded
    } catch (Exception $e) {
        if (str_contains($e->getMessage(), 'foreign key') || str_contains($e->getMessage(), 'constraint')) {
            echo "✓ Foreign key constraint properly enforced\n";
        } else {
            echo "✗ Unexpected error: " . $e->getMessage() . "\n";
        }
    }
} catch (Exception $e) {
    echo "✗ Error testing foreign key constraints: " . $e->getMessage() . "\n";
}

echo "\n=== CRITICAL PATH TESTING COMPLETED ===\n";
