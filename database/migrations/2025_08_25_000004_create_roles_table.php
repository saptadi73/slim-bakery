<?php

require __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

$capsule = new Capsule;
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => '127.0.0.1',
    'database'  => 'erpmini',
    'username'  => 'root',
    'password'  => '',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

Capsule::schema()->dropIfExists('role_user');

Capsule::schema()->create('roles', function (Blueprint $table) {
    $table->id(); // menghasilkan unsignedBigInteger
    $table->string('name')->unique();
    $table->string('label')->nullable();
    $table->timestamps();
});

Capsule::schema()->create('role_user', function (Blueprint $table) {
    $table->unsignedBigInteger('user_id');
    $table->unsignedBigInteger('role_id');

    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');

    $table->primary(['user_id', 'role_id']);
});

echo "Tabel roles dan role_user berhasil dibuat!\n";
