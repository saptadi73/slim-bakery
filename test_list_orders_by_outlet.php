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

echo "Testing listOrdersByOutlet method...\n";

// Test with outlet_id = 1
$orderService = new \App\Services\OrderService();
$response = new \Slim\Psr7\Response();

try {
    $result = $orderService->listOrdersByOutlet($response, 1);
    $body = $result->getBody()->getContents();
    echo "Raw response body: '" . $body . "'\n";
    $data = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "JSON decode error: " . json_last_error_msg() . "\n";
        echo "Response status: " . $result->getStatusCode() . "\n";
        echo "Response headers: \n";
        foreach ($result->getHeaders() as $name => $values) {
            echo "  $name: " . implode(', ', $values) . "\n";
        }
    } else {
        echo "✓ Endpoint /orders/list/1 returned successfully.\n";
    }

    if (isset($data['data']) && is_array($data['data'])) {
        echo "✓ Response contains 'data' array.\n";

        foreach ($data['data'] as $order) {
            if (isset($order['orderItems']) && is_array($order['orderItems'])) {
                echo "✓ Order has 'orderItems' array.\n";

                foreach ($order['orderItems'] as $item) {
                    if (isset($item['quantity_order']) && isset($item['quantity_provider'])) {
                        echo "✓ Order item has 'quantity_order' and 'quantity_provider' fields.\n";
                        echo "  - quantity_order: {$item['quantity_order']}\n";
                        echo "  - quantity_provider: {$item['quantity_provider']}\n";

                        if ($item['quantity_provider'] > 0) {
                            echo "  - Status: Provided\n";
                        } else {
                            echo "  - Status: Not provided\n";
                        }
                    } else {
                        echo "✗ Order item missing 'quantity_order' or 'quantity_provider' fields.\n";
                    }
                }
            } else {
                echo "✗ Order missing 'orderItems' array.\n";
            }
        }
    } else {
        echo "Response structure:\n";
        var_dump($data);
        echo "✗ Response does not contain 'data' array or is not an array.\n";
    }

} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "Testing completed.\n";
