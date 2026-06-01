<?php

require __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->safeLoad();

try {
    $pdo = new PDO(
        "pgsql:host=" . ($_ENV['DB_HOST'] ?? '127.0.0.1') . ";port=" . (int)($_ENV['DB_PORT'] ?? 5432) . ";dbname=" . ($_ENV['DB_DATABASE'] ?? 'bakery'),
        $_ENV['DB_USERNAME'] ?? 'openpg',
        $_ENV['DB_PASSWORD'] ?? 'openpgpwd'
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Koneksi database berhasil.\n";

    // Pastikan outlet default selalu ada
    $stmtOutlet = $pdo->query("SELECT id FROM outlets WHERE kode = 'DEFAULT' LIMIT 1");
    $defaultOutlet = $stmtOutlet->fetch(PDO::FETCH_ASSOC);

    if (!$defaultOutlet) {
        $pdo->exec("
            INSERT INTO outlets (nama, kode, alamat, phone, prioritas)
            VALUES ('Outlet Default', 'DEFAULT', '-', '-', 0)
        ");
        echo "✓ Outlet default berhasil ditambahkan.\n";
    } else {
        echo "✓ Outlet default sudah ada (id: {$defaultOutlet['id']}).\n";
    }

    // Jika products masih kosong, buat 1 produk contoh agar alur awal tidak buntu
    $stmtProductCount = $pdo->query("SELECT COUNT(*) FROM products");
    $productCount = (int)$stmtProductCount->fetchColumn();

    if ($productCount === 0) {
        $stmtCategory = $pdo->query("SELECT id FROM categories ORDER BY id ASC LIMIT 1");
        $firstCategory = $stmtCategory->fetch(PDO::FETCH_ASSOC);
        $categoryId = $firstCategory ? (int)$firstCategory['id'] : null;

        $insertSql = "
            INSERT INTO products (nama, kode, gambar, category_id, harga, harga_jual, harga_beli, stok, status)
            VALUES (:nama, :kode, NULL, :category_id, :harga, :harga_jual, :harga_beli, :stok, :status)
        ";
        $stmtInsert = $pdo->prepare($insertSql);
        $stmtInsert->execute([
            ':nama' => 'Produk Awal',
            ':kode' => 'PRD-DEFAULT',
            ':category_id' => $categoryId,
            ':harga' => 0,
            ':harga_jual' => 0,
            ':harga_beli' => 0,
            ':stok' => 0,
            ':status' => 'active',
        ]);

        echo "✓ Produk awal berhasil ditambahkan.\n";
    } else {
        echo "✓ Tabel products sudah berisi data ({$productCount} baris), skip insert produk awal.\n";
    }

    echo "Selesai seed data awal outlet & product.\n";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
