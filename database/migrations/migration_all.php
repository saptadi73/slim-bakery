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

    echo "Koneksi berhasil ke database.\n\n";

    // Drop all existing tables first
    echo "Menghapus semua tabel yang ada...\n";
    $tables = [
        'receive_items',
        'receives',
        'delivery_order_items',
        'delivery_orders',
        'user_outlet',
        'delivers',
        'providers',
        'order_items',
        'orders',
        'product_movings',
        'inventories',
        'products',
        'categories',
        'outlets',
        'auth_tokens',
        'role_user',
        'users',
        'roles'
    ];

    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS $table CASCADE");
        echo "✓ Tabel $table dihapus.\n";
    }
    echo "Semua tabel lama berhasil dihapus.\n\n";

    // Buat tabel users
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id BIGSERIAL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✓ Tabel users dibuat.\n";

    // Buat tabel roles
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS roles (
            id BIGSERIAL PRIMARY KEY,

    
            name VARCHAR(255) UNIQUE NOT NULL,
            label VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✓ Tabel roles dibuat.\n";

    // Insert roles
    $pdo->exec("
        INSERT INTO roles (name, label) VALUES
        ('admin', 'Administrator'),
        ('pegawai', 'Pegawai'),
        ('user', 'User')
        ON CONFLICT (name) DO NOTHING
    ");
    echo "✓ Role default ditambahkan.\n";

    // Buat tabel role_user
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS role_user (
            role_id BIGINT NOT NULL,
            user_id BIGINT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (role_id, user_id),
            FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "✓ Tabel role_user dibuat.\n";

    // Buat tabel auth_tokens
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS auth_tokens (
            id BIGSERIAL PRIMARY KEY,
            user_id BIGINT NOT NULL,
            type VARCHAR(50) NOT NULL,
            token_hash VARCHAR(128) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            revoked_at TIMESTAMP,
            meta JSONB,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✓ Tabel auth_tokens dibuat.\n";

    // Create index on auth_tokens
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_auth_tokens_user_type ON auth_tokens(user_id, type)");
    echo "✓ Index auth_tokens dibuat.\n";

    // Buat tabel outlets
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS outlets (
            id BIGSERIAL PRIMARY KEY,
            nama VARCHAR(255) NOT NULL,
            kode VARCHAR(255),
            alamat TEXT,
            phone VARCHAR(255),
            prioritas INTEGER DEFAULT 0,
            gambar VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✓ Tabel outlets dibuat.\n";

    // Buat tabel categories
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id BIGSERIAL PRIMARY KEY,
            nama VARCHAR(255) NOT NULL,
            keterangan TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✓ Tabel categories dibuat.\n";

    // Insert categories
    $pdo->exec("
        INSERT INTO categories (nama, keterangan) VALUES
        ('CAKE', 'Kue-kue'),
        ('BOLU', 'Bolu'),
        ('KEMASAN', 'Kue Kemasan'),
        ('ROTI BF & SB - ASIN', 'Roti Bantal Asin'),
        ('ROTI BF & SB - MANIS', 'Roti Bantal Manis'),
        ('ROTI CP - ASIN','Roti Asin'),
        ('ROTI CP - MANIS','Roti Manis'),
        ('ROTI SOBEK','Roti Sobek')
        ON CONFLICT DO NOTHING
    ");
    echo "✓ Data awal categories ditambahkan.\n";

    // Buat tabel products
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS products (
            id BIGSERIAL PRIMARY KEY,
            nama VARCHAR(255) NOT NULL,
            kode VARCHAR(255),
            gambar VARCHAR(255),
            category_id BIGINT,
            harga_jual DECIMAL(10,2) DEFAULT 0,
            harga_beli DECIMAL(10,2) DEFAULT 0,
            stok INTEGER DEFAULT 0,
            status VARCHAR(50) DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
        )
    ");
    echo "✓ Tabel products dibuat.\n";

    // Buat tabel inventories
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS inventories (
            id BIGSERIAL PRIMARY KEY,
            product_id BIGINT NOT NULL,
            outlet_id BIGINT NOT NULL,
            quantity INTEGER DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY (outlet_id) REFERENCES outlets(id) ON DELETE CASCADE,
            UNIQUE (product_id, outlet_id)
        )
    ");
    echo "✓ Tabel inventories dibuat.\n";

    // Buat tabel product_movings
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS product_movings (
            id BIGSERIAL PRIMARY KEY,
            product_id BIGINT NOT NULL,
            type VARCHAR(50) DEFAULT 'income',
            outlet_id BIGINT,
            quantity INTEGER DEFAULT 0,
            tanggal DATE,
            pic VARCHAR(255),
            keterangan TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY (outlet_id) REFERENCES outlets(id) ON DELETE SET NULL
        )
    ");
    echo "✓ Tabel product_movings dibuat.\n";

    // Buat tabel orders
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS orders (
            id BIGSERIAL PRIMARY KEY,
            no_order VARCHAR(255) UNIQUE,
            outlet_id BIGINT NOT NULL,
            user_id BIGINT NOT NULL,
            total DECIMAL(10,2) DEFAULT 0,
            status VARCHAR(50) DEFAULT 'open',
            tanggal TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (outlet_id) REFERENCES outlets(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "✓ Tabel orders dibuat.\n";

    // Jika menggunakan PostgreSQL, buat sequence untuk nomor order agar aman terhadap race condition
    try {
        // create sequence if not exists (Postgres supports IF NOT EXISTS)
        $pdo->exec("CREATE SEQUENCE IF NOT EXISTS order_no_seq START WITH 1 INCREMENT BY 1;");
        echo "✓ Sequence order_no_seq dibuat (jika menggunakan Postgres).\n";
        // Initialize sequence to continue from existing max orders (if any)
        // Extract numeric suffix from no_order (e.g. ORDER-00012 -> 12) and set sequence to that max
        // so nextval will return max+1. If no orders exist, set to 0 so nextval returns 1.
        $pdo->exec(
            "SELECT setval('order_no_seq', COALESCE((SELECT MAX((regexp_replace(no_order, '\\D', '', 'g'))::bigint) FROM orders), 0));"
        );
    } catch (PDOException $e) {
        // ignore if DB doesn't support sequences (e.g., MySQL)
    }

    // Buat tabel order_items
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_items (
            id BIGSERIAL PRIMARY KEY,
            order_id BIGINT NOT NULL,
            product_id BIGINT NOT NULL,
            outlet_id BIGINT NOT NULL,
            quantity INTEGER DEFAULT 0,
            tanggal DATE,
            pic VARCHAR(255),
            status VARCHAR(50) DEFAULT 'open',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY (outlet_id) REFERENCES outlets(id) ON DELETE CASCADE
        )
    ");
    echo "✓ Tabel order_items dibuat.\n";

    // Add constraint to order_items status
    $pdo->exec("
        ALTER TABLE order_items DROP CONSTRAINT IF EXISTS order_items_status_check;
        ALTER TABLE order_items ADD CONSTRAINT order_items_status_check CHECK (status IN ('open', 'processed', 'delivered', 'provided'));
    ");
    echo "✓ Constraint status order_items diperbarui.\n";

    // Buat tabel providers
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS providers (
            id BIGSERIAL PRIMARY KEY,
            order_items_id BIGINT NOT NULL,
            quantity INTEGER DEFAULT 0,
            tanggal DATE,
            pic VARCHAR(255),
            nopro VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_items_id) REFERENCES order_items(id) ON DELETE CASCADE
        )
    ");
    echo "✓ Tabel providers dibuat.\n";

    // Buat tabel delivers
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS delivers (
            id BIGSERIAL PRIMARY KEY,
            provider_id BIGINT NOT NULL,
            quantity INTEGER DEFAULT 0,
            tanggal DATE,
            pic VARCHAR(255),
            receiver VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE CASCADE
        )
    ");
    echo "✓ Tabel delivers dibuat.\n";

    // Buat tabel user_outlet
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_outlet (
            user_id BIGINT NOT NULL,
            outlet_id BIGINT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id, outlet_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (outlet_id) REFERENCES outlets(id) ON DELETE CASCADE
        )
    ");
    echo "✓ Tabel user_outlet dibuat.\n";

    // Buat tabel delivery_orders
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS delivery_orders (
            id BIGSERIAL PRIMARY KEY,
            no_do VARCHAR(255) UNIQUE NOT NULL,
            pic VARCHAR(255) NOT NULL,
            tanggal TIMESTAMP NOT NULL,
            status VARCHAR(50) DEFAULT 'open',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✓ Tabel delivery_orders dibuat.\n";

    // Buat tabel delivery_order_items
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS delivery_order_items (
            id BIGSERIAL PRIMARY KEY,
            delivery_order_id BIGINT NOT NULL,
            provider_id BIGINT NOT NULL,
            quantity INTEGER DEFAULT 0,
            pic VARCHAR(255) NOT NULL,
            tanggal DATE NOT NULL,
            product_id BIGINT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (delivery_order_id) REFERENCES delivery_orders(id) ON DELETE CASCADE,
            FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
        )
    ");
    echo "✓ Tabel delivery_order_items dibuat.\n";

    // Buat tabel receives
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS receives (
            id BIGSERIAL PRIMARY KEY,
            no_rec VARCHAR(255) UNIQUE NOT NULL,
            pic VARCHAR(255) NOT NULL,
            tanggal TIMESTAMP NOT NULL,
            delivery_order_id BIGINT NOT NULL,
            keterangan TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (delivery_order_id) REFERENCES delivery_orders(id) ON DELETE CASCADE
        )
    ");
    echo "✓ Tabel receives dibuat.\n";

    // Buat tabel receive_items
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS receive_items (
            id BIGSERIAL PRIMARY KEY,
            receive_id BIGINT NOT NULL,
            delivery_order_items_id BIGINT NOT NULL,
            quantity INTEGER DEFAULT 0,
            pic VARCHAR(255) NOT NULL,
            tanggal DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (receive_id) REFERENCES receives(id) ON DELETE CASCADE,
            FOREIGN KEY (delivery_order_items_id) REFERENCES delivery_order_items(id) ON DELETE CASCADE
        )
    ");
    echo "✓ Tabel receive_items dibuat.\n";

    echo "\n=== SEMUA MIGRASI BERHASIL ===\n";

    // Cek tabel yang ada
    $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name;");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Tabel yang ada (" . count($tables) . "):\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
