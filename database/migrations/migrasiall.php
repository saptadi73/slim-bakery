<?php

require __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
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

$schema = Capsule::schema();

/** users table (without email_verified_at + softDeletes) */
Capsule::schema()->dropIfExists('user_outlet');
Capsule::schema()->dropIfExists('role_user');
Capsule::schema()->dropIfExists('users');
if (!$schema->hasTable('users')) {
    $schema->create('users', function ($t) {
        $t->bigIncrements('id');
        $t->string('name');
        $t->string('email')->unique();
        $t->string('password');
        $t->timestamps(); // Tanpa email_verified_at dan softDeletes
    });
    echo "Tabel users dibuat.\n";
} else {
    if (!$schema->hasColumn('users', 'password')) {
        $schema->table('users', fn($t) => $t->string('password'));
        echo "Kolom password ditambah.\n";
    }
}

/** roles + pivot */


Capsule::schema()->dropIfExists('roles');
if (!$schema->hasTable('roles')) {
    $schema->create('roles', function ($t) {
        $t->bigIncrements('id');
        $t->string('name')->unique();
        $t->string('label')->nullable();
        $t->timestamps();
    });
    echo "Tabel roles dibuat.\n";
}

if (!$schema->hasTable('role_user')) {
    $schema->create('role_user', function ($t) {
        $t->unsignedBigInteger('role_id');
        $t->unsignedBigInteger('user_id');
        $t->primary(['role_id', 'user_id']);
        $t->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        $t->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        $t->timestamps();
    });
    echo "Tabel role_user dibuat.\n";
}

/** auth_tokens: refresh/email_verify/password_reset */
Capsule::schema()->dropIfExists('auth_tokens');
if (!$schema->hasTable('auth_tokens')) {
    $schema->create('auth_tokens', function ($t) {
        $t->bigIncrements('id');
        $t->unsignedBigInteger('user_id');
        $t->enum('type', ['refresh', 'email_verify', 'password_reset']);
        $t->string('token_hash', 128); // simpan hash, bukan token asli
        $t->timestamp('expires_at');
        $t->timestamp('revoked_at')->nullable();
        $t->json('meta')->nullable();  // user agent, ip, dll
        $t->timestamps();
        $t->index(['user_id', 'type']);
    });
    echo "Tabel auth_tokens dibuat.\n";
}

// Seed role dasar
$admin = \App\Models\Role::firstOrCreate(['name' => 'admin'], ['label' => 'Administrator']);
$pegawai = \App\Models\Role::firstOrCreate(['name' => 'pegawai'], ['label' => 'Pegawai']);
$userRole = \App\Models\Role::firstOrCreate(['name' => 'user'], ['label' => 'User']);

// Set ID default untuk role
$admin->id = 1; // ID untuk admin
$pegawai->id = 2; // ID untuk teknisi
$userRole->id = 3; // ID untuk user

// Simpan role dengan ID yang sudah ditentukan
$admin->save();
$pegawai->save();
$userRole->save();

// jika ada user id=1, jadikan admin
$first = \App\Models\User::find(1);
if ($first) {
    $first->roles()->syncWithoutDetaching([$admin->id]); // Mengaitkan user dengan role admin
}

echo "Migrasi selesai dan role default ditambahkan.\n";
