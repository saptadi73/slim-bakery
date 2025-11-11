<?php

require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->safeLoad();

try {
    $pdo = new PDO(
        "pgsql:host=" . ($_ENV['DB_HOST'] ?? '127.0.0.1') . ";port=" . (int)($_ENV['DB_PORT'] ?? 5432) . ";dbname=" . ($_ENV['DB_DATABASE'] ?? 'bakery'),
        $_ENV['DB_USERNAME'] ?? 'openpg',
        $_ENV['DB_PASSWORD'] ?? 'openpgpwd'
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Koneksi berhasil ke database.\n\n";

    $tables = ['users', 'roles', 'role_user', 'user_outlet', 'outlets', 'categories', 'products', 'inventories', 'product_movings', 'orders', 'order_items', 'providers', 'delivers', 'delivery_orders', 'delivery_order_items', 'receives', 'receive_items'];

    foreach ($tables as $table) {
        echo "=== Tabel: $table ===\n";
        $stmt = $pdo->query("SELECT column_name, data_type, is_nullable, column_default FROM information_schema.columns WHERE table_name = '$table' AND table_schema = 'public' ORDER BY ordinal_position;");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "- {$col['column_name']} ({$col['data_type']})" . ($col['is_nullable'] == 'NO' ? ' NOT NULL' : '') . "\n";
        }
        echo "\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
