# API Docs Index

Last update: 2026-05-06

Dokumen API untuk project ini:

1. Referensi lengkap endpoint
- File: docs/API_FRONTEND_REFERENCE.md
- Isi: daftar endpoint, auth, payload, response, dan catatan integrasi frontend.

2. OpenAPI 3.0
- File: docs/openapi.yaml
- Isi: spesifikasi endpoint dalam format OpenAPI + contoh response pada endpoint utama (login, create product, reports).

3. Postman Collection
- File: docs/SlimBakery.postman_collection.json
- Isi: koleksi request siap import ke Postman.
- Catatan: request Login memiliki test script untuk otomatis menyimpan JWT ke collection variable token.

## Catatan Migrasi Database

1. Server baru (fresh setup)
- Gunakan: `php database/migrations/migration_all.php`
- Catatan: `migration_all.php` membuat tabel categories dan seed categories sebelum membuat/seed products agar relasi `category_id` valid.

2. Database existing (sudah ada data)
- Gunakan incremental migration: `php database/migrations/add_harga_to_products.php`

3. Migrasi lama
- `database/migrations/migrasinextall.php` sudah ditandai deprecated dan tidak direkomendasikan untuk setup server baru.

## Cara Pakai Cepat

### Import OpenAPI ke Swagger Editor
1. Buka https://editor.swagger.io
2. File -> Import File -> pilih docs/openapi.yaml

### Import Postman Collection
1. Buka Postman
2. Import -> File -> pilih docs/SlimBakery.postman_collection.json
3. Set environment variable:
   - base_url = http://localhost/slim-eloquent-bakery/public
   - token = JWT dari endpoint login

## Catatan Ringkasan Produk

- Endpoint `/products/summary` dan `/products/summary/roti` mengembalikan field `total_harga`.
- Rumus backend:
   - `stock` = total quantity inventory dari semua outlet per product.
   - `total_orders` = total quantity order item dari semua status per product.
   - `total_harga` = `harga * total_orders`.
