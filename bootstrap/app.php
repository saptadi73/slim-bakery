<?php
use Slim\Factory\AppFactory;
use Illuminate\Database\Capsule\Manager as Capsule;
use App\Middlewares\CorsMiddleware;
use App\Models\Outlet;
use Slim\Psr7\Factory\ResponseFactory;
use Psr\Http\Message\ResponseFactoryInterface;
use App\Services\ProductService;
use App\Services\OutletService;
use App\Services\OrderService;
use App\Services\CategoryService;
use Pimple\Container as PimpleContainer;
use Pimple\Psr11\Container as Psr11Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require __DIR__ . '/../vendor/autoload.php';

// .env
if (file_exists(__DIR__ . '/../.env')) {
    Dotenv\Dotenv::createImmutable(__DIR__ . '/../')->load();
}

// --- Container (Pimple + PSR-11 adapter)
$pimple = new PimpleContainer();
$pimple[ResponseFactoryInterface::class] = fn() => new ResponseFactory();
// (optional legacy id used by some examples)
$pimple['responseFactory'] = fn($c) => $c[ResponseFactoryInterface::class];

// register services here
$pimple[ProductService::class] = fn($c) => new ProductService();
$pimple[OutletService::class] = fn($c) => new OutletService();
$pimple[OrderService::class] = fn($c) => new OrderService();
$pimple[CategoryService::class] = fn($c) => new CategoryService();
// If your CorsMiddleware needs deps, wire them here too, e.g.:
// $pimple[CorsMiddleware::class] = fn($c) => new CorsMiddleware(/* deps */);

$container = new Psr11Container($pimple);

// Use EITHER setContainer()+create() OR createFromContainer()
// AppFactory::setContainer($container);
// $app = AppFactory::create();

$app = AppFactory::createFromContainer($container);

// --- Core middlewares
$app->addRoutingMiddleware();
$app->addBodyParsingMiddleware();

$displayError = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
$app->addErrorMiddleware($displayError, true, true);

// --- Preflight CORS (accept 3 args!)
$app->options('/{routes:.+}', function (Request $req, Response $res, array $args) {
    // If you want, set headers here:
    // return $res->withHeader('Access-Control-Allow-Origin', '*')
    //           ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
    //           ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
    return $res;
});

// --- CORS middleware
// If you registered it in Pimple: $app->add(CorsMiddleware::class);
// Otherwise instantiate directly (make sure ctor matches):
$app->add(new CorsMiddleware());

// --- Eloquent
$capsule = new Capsule;
$capsule->addConnection([
    'driver'    => $_ENV['DB_DRIVER'] ?? 'mysql',
    'host'      => $_ENV['DB_HOST'] ?? '127.0.0.1',
    'database'  => $_ENV['DB_DATABASE'] ?? 'bakery',
    'username'  => $_ENV['DB_USERNAME'] ?? 'root',
    'password'  => $_ENV['DB_PASSWORD'] ?? '',
    'charset'   => $_ENV['DB_CHARSET'] ?? 'utf8',
    'prefix'    => $_ENV['DB_PREFIX'] ?? '',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

date_default_timezone_set($_ENV['APP_TZ'] ?? 'Asia/Jakarta');

// Routes
(require __DIR__ . '/../routes/index.php')($app);

return $app;
