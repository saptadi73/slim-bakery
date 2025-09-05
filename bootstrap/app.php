<?php
use DI\Container;
use Slim\Factory\AppFactory;
use Illuminate\Database\Capsule\Manager as Capsule;
use App\Middleware\CorsMiddleware;
use Psr\Container\ContainerInterface;
use Pimple\Container as PimpleContainer;
use Pimple\Psr11\Container as Psr11Container;

require __DIR__ . '/../vendor/autoload.php';

// .env
if (file_exists(__DIR__ . '/../.env')) {
    Dotenv\Dotenv::createImmutable(__DIR__ . '/../')->load();
}

// Create container first
$container = new Container();

// Register Eloquent (Capsule) as a service
$container->set('db', function (ContainerInterface $c) {
    $capsule = new Capsule();
    $capsule->addConnection([
        'driver'    => $_ENV['DB_DRIVER'] ?? 'mysql',
        'host'      => $_ENV['DB_HOST'] ?? '127.0.0.1',
        'database'  => $_ENV['DB_DATABASE'] ?? 'bakery',
        'username'  => $_ENV['DB_USERNAME'] ?? 'root',
        'password'  => $_ENV['DB_PASSWORD'] ?? '',
        'charset'   => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
        'collation' => $_ENV['DB_COLLATION'] ?? 'utf8mb4_unicode_ci',
        'prefix'    => '',
        'port'      => (int) ($_ENV['DB_PORT'] ?? 3306),
    ]);

    // Make global & boot Eloquent
    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    return $capsule; // you can also return $capsule->getDatabaseManager()
});

// Attach container to Slim BEFORE create()
AppFactory::setContainer($container);
$app = AppFactory::create();

// IMPORTANT: resolve 'db' now so Eloquent actually boots before routes
$container->get('db');

// Middlewares
$app->add(new CorsMiddleware());

date_default_timezone_set($_ENV['APP_TZ'] ?? 'Asia/Jakarta');

(require __DIR__ . '/../routes/index.php')($app);

return $app;
