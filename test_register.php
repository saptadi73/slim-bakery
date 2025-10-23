<?php

require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/');
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

// Simulate the register request
$data = [
    'name' => 'Test User',
    'email' => 'test' . time() . '@example.com',
    'password' => 'password123'
];

$user = \App\Services\AuthService::register($data['name'], $data['email'], $data['password'], null, null);

if ($user) {
    echo "User registered successfully!\n";
    echo "User ID: " . $user->id . "\n";
    echo "Roles: " . $user->roles->pluck('name')->join(', ') . "\n";
    echo "Outlets: " . $user->outlets->pluck('nama')->join(', ') . "\n";
} else {
    echo "Registration failed.\n";
}
