<?php
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

// Jalankan file ini dengan: php database/migrations/2025_08_24_000000_create_users_table.php

Capsule::schema()->create('users', function (Blueprint $table) {
    $table->increments('id');
    $table->string('name');
    $table->string('email')->unique();
    $table->string('password');
    $table->timestamps();
});

echo "Tabel users berhasil dibuat!\n";
