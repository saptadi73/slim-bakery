<?php

require __DIR__ . '/../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = (int)(getenv('DB_PORT') ?: 5432);
$db = getenv('DB_DATABASE') ?: 'bakery';
$user = getenv('DB_USERNAME') ?: 'openpg';
$pass = getenv('DB_PASSWORD') ?: 'openpgpwd';

$pdo = new PDO("pgsql:host={$host};port={$port};dbname={$db}", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function sqlValue(PDO $pdo, $value): string
{
    if ($value === null) {
        return 'NULL';
    }

    if (is_bool($value)) {
        return $value ? 'TRUE' : 'FALSE';
    }

    if (is_int($value) || is_float($value)) {
        return (string)$value;
    }

    return $pdo->quote((string)$value);
}

function buildInsert(PDO $pdo, string $table, array $rows): string
{
    if (count($rows) === 0) {
        return "-- {$table}: tidak ada data";
    }

    $columns = array_keys($rows[0]);
    $columnSql = implode(', ', $columns);

    $valuesSql = [];
    foreach ($rows as $row) {
        $cells = [];
        foreach ($columns as $column) {
            $cells[] = sqlValue($pdo, $row[$column] ?? null);
        }
        $valuesSql[] = '(' . implode(', ', $cells) . ')';
    }

    return "INSERT INTO {$table} ({$columnSql}) VALUES\n" . implode(",\n", $valuesSql) . "\nON CONFLICT DO NOTHING;";
}

$outlets = $pdo->query('SELECT id, nama, kode, alamat, phone, prioritas, gambar FROM outlets ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);
$products = $pdo->query('SELECT id, nama, kode, gambar, category_id, harga, harga_jual, harga_beli, stok, status FROM products ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);

$out = [];
$out[] = '-- Seed snapshot generated from local database';
$out[] = '-- outlets rows: ' . count($outlets);
$out[] = '-- products rows: ' . count($products);
$out[] = '';
$out[] = buildInsert($pdo, 'outlets', $outlets);
$out[] = '';
$out[] = buildInsert($pdo, 'products', $products);
$out[] = '';
$out[] = "SELECT setval('outlets_id_seq', COALESCE((SELECT MAX(id) FROM outlets), 1), true);";
$out[] = "SELECT setval('products_id_seq', COALESCE((SELECT MAX(id) FROM products), 1), true);";

file_put_contents(__DIR__ . '/seed_snapshot.sql', implode(PHP_EOL, $out));

echo 'OK: tools/seed_snapshot.sql generated' . PHP_EOL;
