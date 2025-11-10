<?php

require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/');
$dotenv->safeLoad();

try {
    $pdo = new PDO(
        "pgsql:host=" . ($_ENV['DB_HOST'] ?? '127.0.0.1') . ";port=" . (int)($_ENV['DB_PORT'] ?? 5432) . ";dbname=" . ($_ENV['DB_DATABASE'] ?? 'bakery'),
        $_ENV['DB_USERNAME'] ?? 'openpg',
        $_ENV['DB_PASSWORD'] ?? 'openpgpwd'
    );

    echo "Koneksi berhasil ke database remote: " . $_ENV['DB_HOST'] . ":" . ($_ENV['DB_PORT'] ?? 5432) . "/" . $_ENV['DB_DATABASE'] . "\n";

    // Test query sederhana
    $stmt = $pdo->query("SELECT version()");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "PostgreSQL Version: " . $result['version'] . "\n";

    // Cek tabel yang ada
    $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name;");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "\nTabel yang ada (" . count($tables) . "):\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }

} catch (PDOException $e) {
    echo "Error koneksi: " . $e->getMessage() . "\n";
}
