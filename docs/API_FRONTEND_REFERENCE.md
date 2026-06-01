# API Frontend Reference

Dokumen ini merangkum endpoint aplikasi Slim PHP 4 + Eloquent ORM yang aktif di kode saat ini.
Last update: 2026-05-06

## 1) Konvensi Umum

### Base URL
- Local umum: http://localhost/slim-eloquent-bakery/public
- Semua path di bawah ini relatif terhadap base URL.

### Format Response Umum
Semua endpoint JSON menggunakan envelope:

Success
{
  "status": true,
  "message": "Pesan sukses",
  "data": {}
}

Error
{
  "status": false,
  "message": "Pesan error",
  "data": {}
}

### Header Umum
- Content-Type: application/json untuk payload JSON
- Authorization: Bearer <token> untuk endpoint dengan proteksi JWT

### Upload File
Endpoint upload gambar membaca file dari field multipart bernama file.

### Catatan Migrasi Database
- Fresh server: gunakan `php database/migrations/migration_all.php`.
- Existing database: gunakan `php database/migrations/add_harga_to_products.php`.
- `database/migrations/migrasinextall.php` adalah migrasi lama (deprecated), jangan dipakai untuk setup server baru.
- `migration_all.php` membuat tabel categories terlebih dahulu, lalu seed categories, kemudian membuat products, dan menambahkan seed snapshot outlets/products.

## 2) Ringkasan Endpoint

### Auth dan User
- GET /
- POST /register
- POST /login
- POST /update/role (JWT)
- GET /profile (JWT)
- POST /logout (JWT)
- POST /user/update/{id} (JWT)
- POST /user/delete/{id} (JWT)
- POST /change-password (JWT)
- GET /users (JWT)
- GET /roles

### Categories
- GET /categories
- POST /categories/new (JWT)
- GET /categories/{id}
- POST /categories/update/{id} (JWT)
- POST /categories/delete/{id} (JWT)

### Outlets
- GET /outlets
- GET /outlets/all
- GET /outlets/{id}
- POST /outlets/new (JWT, optional file)
- POST /outlets/update/{id} (JWT, optional file)
- POST /outlets/update/image/{id} (JWT, file)
- DELETE /outlets/{id} (JWT)

### Products dan Stock
- GET /products
- POST /products/new (JWT, optional file)
- GET /products/summary
- GET /products/summary/roti
- GET /products/{id}
- POST /products/update/{id} (JWT, optional file)
- POST /products/update/image/{id} (JWT, file)
- POST /products/delete/{id} (JWT)
- POST /products/moving (JWT)
- GET /products/inventory/{id}
- POST /products/moving/income (JWT)
- POST /productsmoving/multi/income (JWT)  <- route didefinisikan tanpa slash awal
- POST /products/moving/multi (JWT)

### Orders dan Provider
- POST /orders/new (JWT)
- GET /orders/list
- GET /orders/list-all
- GET /orders/list/{outlet_id}
- GET /orders/products
- GET /orders/outlets/{id}
- GET /orders/products/{id}
- GET /orders/groups
- GET /orders/leftjoin/{id}
- PUT /orders/{id} (JWT)
- DELETE /orders/{id} (JWT)
- GET /orders/{id}
- GET /orders/{id}/with-product-id
- POST /orders/providers (JWT)
- POST /orders/providers/single (JWT)
- POST /orders/providers/multi (JWT)
- PUT /orders/providers/{id} (JWT)

### Delivery Orders
- POST /delivery-orders/new (JWT)
- GET /delivery-orders/list
- GET /delivery-orders/list/{outlet_id}
- GET /delivery-orders/{id}
- PUT /delivery-orders/{id} (JWT)
- DELETE /delivery-orders/{id} (JWT)
- POST /delivery-orders/{id}/close (JWT)

### Receives
- POST /receives/new (JWT)
- GET /receives/list
- GET /receives/{id}
- GET /receives/delivery-order/{delivery_order_id}
- PUT /receives/{id} (JWT)
- DELETE /receives/{id} (JWT)

### Reports
- GET /reports/orders
- GET /reports/orders/{order_id}

### Seeds
- GET /seeds/products
- GET /seeds/outlets
- GET /seeds/orders

## 3) Detail Endpoint

## 3.1 Auth dan User

### GET /
- Auth: Tidak
- Request body: Tidak ada
- Response data: String plain text, bukan JSON

### POST /register
- Auth: Tidak
- Request body:
{
  "name": "Nama User",
  "email": "user@mail.com",
  "password": "rahasia",
  "role_id": 1,
  "outlet_id": 1
}
- Wajib: name, email, password
- Optional: role_id, outlet_id
- Success: data user yang dibuat
- Error umum:
  - 400 Invalid input
  - 404 Default role not found / Role not found

### POST /login
- Auth: Tidak
- Request body:
{
  "email": "user@mail.com",
  "password": "rahasia"
}
- Success data:
{
  "token": "jwt",
  "user": { ... },
  "role_id": 1,
  "role": "admin",
  "outlet_id": 1,
  "outlet_name": "Outlet A"
}
- Error umum:
  - 400 Invalid input
  - 401 Invalid credentials

### POST /update/role
- Auth: JWT
- Request body:
{
  "user_id": 1,
  "role_id": 2
}
- Wajib: user_id, role_id
- Success: data user setelah sinkronisasi role

### GET /profile
- Auth: JWT
- Request body: Tidak ada
- Success data:
{
  "user": { ... },
  "role": { ... }
}

### POST /logout
- Auth: JWT
- Request body: Tidak ada
- Success: data kosong

### POST /user/update/{id}
- Auth: JWT
- Path param: id user
- Request body: field user yang ingin diubah, contoh
{
  "name": "Nama Baru",
  "email": "baru@mail.com",
  "role_id": 2
}
- Success: user terupdate
- Error: 404 user not found

### POST /user/delete/{id}
- Auth: JWT
- Path param: id user
- Request body: tidak ada
- Success: data kosong

### POST /change-password
- Auth: JWT
- Request body:
{
  "old_password": "lama",
  "new_password": "baru"
}
- Wajib: old_password, new_password
- Success: data kosong
- Error: old password salah, user tidak ditemukan

### GET /users
- Auth: JWT
- Response data: array user sederhana {id, name, email}

### GET /roles
- Auth: Tidak
- Response data: array role

## 3.2 Categories

### GET /categories
- Auth: Tidak
- Response data: array kategori

### POST /categories/new
- Auth: JWT
- Request body:
{
  "nama": "ROTI",
  "keterangan": "Kategori roti"
}
- Wajib: nama
- Success: category baru

### GET /categories/{id}
- Auth: Tidak
- Path param: id kategori
- Success: detail category
- Error: 404

### POST /categories/update/{id}
- Auth: JWT
- Path param: id kategori
- Request body: nama, keterangan (optional)
- Success: category terupdate

### POST /categories/delete/{id}
- Auth: JWT
- Path param: id kategori
- Success: data kosong

## 3.3 Outlets

### GET /outlets
- Auth: Tidak
- Response data: array outlet diurutkan prioritas DESC

### GET /outlets/all
- Auth: Tidak
- Response data: array outlet

### GET /outlets/{id}
- Auth: Tidak
- Path param: id outlet
- Response data: detail outlet (bisa null jika id tidak ada)

### POST /outlets/new
- Auth: JWT
- Content-Type: application/json atau multipart/form-data
- Request body JSON:
{
  "nama": "Outlet A",
  "alamat": "Alamat",
  "phone": "08123",
  "kode": "OUTA",
  "prioritas": 10
}
- File optional: file
- Wajib: nama
- Success: outlet baru

### POST /outlets/update/{id}
- Auth: JWT
- Path param: id outlet
- Request body: field outlet yang ingin diubah
- File optional: file
- Success: outlet terupdate

### POST /outlets/update/image/{id}
- Auth: JWT
- Path param: id outlet
- Multipart wajib file
- Success: outlet dengan gambar terbaru

### DELETE /outlets/{id}
- Auth: JWT
- Path param: id outlet
- Success: data null

## 3.4 Products dan Stock

### GET /products
- Auth: Tidak
- Response data: array product dengan relasi category
- Field product mencakup harga

### POST /products/new
- Auth: JWT
- Content-Type: application/json atau multipart/form-data
- Request body:
{
  "nama": "Roti Coklat",
  "kode": "RTC",
  "category_id": 1,
  "harga": 15000,
  "gambar": null
}
- File optional: file
- Wajib: nama, kode
- Validasi: harga harus numeric dan tidak negatif
- Fallback: jika harga tidak dikirim, backend isi dari harga_jual jika ada, jika tidak 0

### GET /products/summary
- Auth: Tidak
- Response data item:
{
  "id": 1,
  "nama": "Roti",
  "kode": "RT",
  "harga": 15000,
  "stock": 20,
  "total_orders": 10,
  "total_harga": 150000,
  "category": "ROTI"
}
- Rumus backend:
  - `stock` = total kuantitas inventory per product (akumulasi semua outlet)
  - `total_orders` = total quantity dari semua order item per product (tanpa filter status)
  - `total_harga` = `harga * total_orders`

### GET /products/summary/roti
- Auth: Tidak
- Sama seperti summary, dengan filter category_id >= 1

### GET /products/{id}
- Auth: Tidak
- Path param: id produk
- Success: detail produk
- Error: 404

### POST /products/update/{id}
- Auth: JWT
- Path param: id produk
- Request body: nama, kode, category_id, harga, gambar (opsional)
- File optional: file
- Validasi: harga numeric dan tidak negatif

### POST /products/update/image/{id}
- Auth: JWT
- Path param: id produk
- Multipart wajib file

### POST /products/delete/{id}
- Auth: JWT
- Path param: id produk
- Success: data kosong

### POST /products/moving
- Auth: JWT
- Request body:
{
  "product_id": 1,
  "outlet_id": 1,
  "type": "income",
  "quantity": 5,
  "tanggal": "2026-05-06",
  "pic": "admin",
  "keterangan": "adjustment"
}
- Wajib: product_id, outlet_id, type, quantity
- type hanya income atau outcome
- quantity harus angka positif
- Success data:
{
  "product_moving": { ... },
  "inventory": { ... }
}

### GET /products/inventory/{id}
- Auth: Tidak
- Path param: id produk
- Response: inventory produk (atau default quantity 0 jika belum ada)

### POST /products/moving/income
- Auth: JWT
- Wajib: product_id, outlet_id, quantity, type
- Behavior: selalu membuat move type income dan menambah inventory

### POST /productsmoving/multi/income
- Auth: JWT
- Catatan: route terdefinisi tanpa slash awal pada kode
- Request body:
{
  "products": [
    {
      "product_id": 1,
      "outlet_id": 1,
      "quantity": 10,
      "type": "income",
      "tanggal": "2026-05-06",
      "pic": "admin",
      "keterangan": "bulk"
    }
  ]
}
- Wajib: products array tidak kosong
- Success data:
{
  "product_movings": [ ... ],
  "inventories": [ ... ]
}

### POST /products/moving/multi
- Auth: JWT
- Request body sama pola dengan moving tunggal
- Memanggil createProductMoving per request

## 3.5 Orders dan Provider

### POST /orders/new
- Auth: JWT
- Request body:
{
  "outlet_id": 1,
  "user_id": 2,
  "total": 100000,
  "tanggal": "2026-05-06T10:00:00+07:00",
  "items": [
    {
      "product_id": 1,
      "outlet_id": 1,
      "quantity": 5,
      "pic": "admin",
      "tanggal": "2026-05-06",
      "status": "open"
    }
  ]
}
- Wajib: outlet_id, items[]
- Wajib per item: product_id, outlet_id, quantity
- Success: order + relasi orderItems

### GET /orders/list
- Auth: Tidak
- Response: array order dengan relasi orderItems.product dan orderItems.outlet

### GET /orders/list-all
- Auth: Tidak
- Response: array order + relasi outlet dan user
- Backend melengkapi outlet_name dan pic_name jika kosong

### GET /orders/list/{outlet_id}
- Auth: Tidak
- Path param: outlet_id
- Response: order berdasarkan outlet

### GET /orders/products
- Auth: Tidak
- Response agregasi quantity order open per product

### GET /orders/outlets/{id}
- Auth: Tidak
- Response agregasi quantity produk untuk outlet tertentu

### GET /orders/products/{id}
- Auth: Tidak
- Response detail order item open per produk dengan outlet info

### GET /orders/groups
- Auth: Tidak
- Response agregasi quantity order per outlet

### GET /orders/leftjoin/{id}
- Auth: Tidak
- Response left join products-order_items-categories berdasar outlet

### PUT /orders/{id}
- Auth: JWT
- Path param: id order
- Request body dapat memuat field order + items
- Items update pattern:
  - Jika item punya id yang sudah ada -> update
  - Jika item tanpa id -> create
  - Item lama yang tidak dikirim lagi -> delete

### DELETE /orders/{id}
- Auth: JWT
- Path param: id order
- Success: data null

### GET /orders/{id}
- Auth: Tidak
- Response: detail order + orderItems + quantity_provider per item

### GET /orders/{id}/with-product-id
- Auth: Tidak
- Mirip detail order, item explicit memuat product_id

### POST /orders/providers
- Auth: JWT
- Request body:
{
  "providers": [
    {
      "order_items_id": 10,
      "quantity": 5,
      "tanggal": "2026-05-06",
      "pic": "admin"
    }
  ]
}
- Wajib: providers[] tidak kosong
- Wajib per item: order_items_id, quantity
- Validasi: quantity provider tidak boleh melebihi quantity order item
- Success data:
{
  "providers": [ ... ],
  "updated_order_items": [ ... ]
}

### POST /orders/providers/single
- Auth: JWT
- Request body:
{
  "order_items_id": 10,
  "quantity": 5,
  "tanggal": "2026-05-06",
  "pic": "admin"
}
- Wajib: order_items_id
- quantity default 0 jika tidak dikirim
- Success data:
{
  "provider": { ... },
  "updated_order_item": { ... }
}

### POST /orders/providers/multi
- Auth: JWT
- Mirip providers, tetapi response data hanya array provider dibuat

### PUT /orders/providers/{id}
- Auth: JWT
- Path param: id provider
- Request body (minimal satu): quantity, tanggal, pic
- Validasi: quantity provider tidak boleh melebihi quantity order item

## 3.6 Delivery Orders

### POST /delivery-orders/new
- Auth: JWT
- Request body:
{
  "pic": "admin",
  "tanggal": "2026-05-06",
  "order_id": 100,
  "items": [
    {
      "provider_id": 1,
      "product_id": 1,
      "outlet_id": 1,
      "quantity": 5,
      "pic": "admin",
      "tanggal": "2026-05-06"
    }
  ]
}
- Wajib: items[]
- Wajib per item: provider_id, product_id, outlet_id, quantity
- Efek samping:
  - Membuat product moving outcome per item
  - Update status order terkait menjadi delivered

### GET /delivery-orders/list
- Auth: Tidak
- Response: delivery order + item + receives
- Tambahan field runtime: outlet_name di item, receives_id di level delivery order

### GET /delivery-orders/list/{outlet_id}
- Auth: Tidak
- Filter by outlet

### GET /delivery-orders/{id}
- Auth: Tidak
- Response detail dengan relasi provider, order, product, category, receives

### PUT /delivery-orders/{id}
- Auth: JWT
- Request body field update: no_do, pic, tanggal, status, items
- Item update pattern sama seperti orders

### DELETE /delivery-orders/{id}
- Auth: JWT
- Success: data null

### POST /delivery-orders/{id}/close
- Auth: JWT
- Menutup DO status closed
- Order terkait diubah ke completed

## 3.7 Receives

### POST /receives/new
- Auth: JWT
- Request body:
{
  "delivery_order_id": 1,
  "pic": "receiver",
  "tanggal": "2026-05-06",
  "keterangan": "diterima baik",
  "items": [
    {
      "delivery_order_items_id": 11,
      "quantity": 5,
      "pic": "receiver",
      "tanggal": "2026-05-06"
    }
  ]
}
- Wajib: delivery_order_id, items[]
- Wajib per item: delivery_order_items_id, quantity
- Validasi: delivery_order_items_id harus milik delivery_order_id terkait

### GET /receives/list
- Auth: Tidak
- Response: list receive + receiveItems + deliveryOrder
- Backend menambahkan product_id, product_name, category_id, category_name di item

### GET /receives/{id}
- Auth: Tidak
- Response: detail receive + item + detail produk

### GET /receives/delivery-order/{delivery_order_id}
- Auth: Tidak
- Response: receive pertama berdasarkan delivery order

### PUT /receives/{id}
- Auth: JWT
- Request body field update: no_rec, pic, tanggal, delivery_order_id, keterangan, items
- Item update pattern sama seperti orders/delivery orders

### DELETE /receives/{id}
- Auth: JWT
- Success: data null

## 3.8 Reports

### GET /reports/orders
- Auth: Tidak
- Response data:
{
  "summary": {
    "total_orders": 0,
    "orders_delivered": 0,
    "orders_closed": 0,
    "orders_with_discrepancies": 0,
    "total_order_items": 0,
    "items_provided": 0,
    "items_delivered": 0,
    "items_received": 0,
    "items_with_discrepancies": 0,
    "total_ordered_value": 0
  },
  "details": [
    {
      "order_id": 1,
      "no_order": "ORDER-00001",
      "outlet_name": "Outlet A",
      "pic_name": "User A",
      "tanggal": "...",
      "status": "open",
      "keterangan": null,
      "total_ordered_quantity": 0,
      "total_delivered_quantity": 0,
      "total_received_quantity": 0,
      "total_ordered_value": 0,
      "has_discrepancies": true,
      "items": [
        {
          "product_id": 1,
          "product_name": "Roti",
          "harga": 15000,
          "quantity_ordered": 2,
          "subtotal_ordered": 30000,
          "status": "open",
          "provided": false,
          "delivered": false,
          "received": false,
          "discrepancy": true,
          "providers": []
        }
      ]
    }
  ]
}

### GET /reports/orders/{order_id}
- Auth: Tidak
- Response detail order trace per segmen ordered-provided-delivered-received
- Item sekarang memuat harga dan subtotal pada segmen ordered

## 3.9 Seeds

### GET /seeds/products
- Auth: Tidak
- Menambahkan data produk masal
- Response data: hasil Product::all()

### GET /seeds/outlets
- Auth: Tidak
- Menambahkan data outlet masal
- Response data: hasil Outlet::all()

### GET /seeds/orders
- Auth: Tidak
- Menambahkan data order masal (insert langsung)
- Response data: string status seed

## 4) Daftar Endpoint dengan JWT Wajib

- POST /update/role
- GET /profile
- POST /logout
- POST /user/update/{id}
- POST /user/delete/{id}
- POST /change-password
- GET /users
- POST /categories/new
- POST /categories/update/{id}
- POST /categories/delete/{id}
- POST /outlets/new
- POST /outlets/update/{id}
- POST /outlets/update/image/{id}
- DELETE /outlets/{id}
- POST /products/new
- POST /products/update/{id}
- POST /products/update/image/{id}
- POST /products/delete/{id}
- POST /products/moving
- POST /products/moving/income
- POST /productsmoving/multi/income
- POST /products/moving/multi
- POST /orders/new
- PUT /orders/{id}
- DELETE /orders/{id}
- POST /orders/providers
- POST /orders/providers/single
- POST /orders/providers/multi
- PUT /orders/providers/{id}
- POST /delivery-orders/new
- PUT /delivery-orders/{id}
- DELETE /delivery-orders/{id}
- POST /delivery-orders/{id}/close
- POST /receives/new
- PUT /receives/{id}
- DELETE /receives/{id}

## 5) Catatan Integrasi Frontend

- Untuk endpoint upload gambar, gunakan multipart/form-data dan file harus bernama file.
- Ada ketidakkonsistenan nama field produk di beberapa relasi lama, misalnya name versus nama pada sebagian query receive.
- Endpoint productsmoving/multi/income kemungkinan typo route karena didefinisikan tanpa slash awal. Bila terjadi 404 pada frontend, cek ulang route definition di backend.
- Timezone backend diset ke Asia/Jakarta.
