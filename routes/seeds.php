<?php
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use App\Services\SeedService;
use App\Support\JsonResponder;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

return function (App $app) {
    // Kumpulan endpoint seed/isi tabel checklist
    $app->group('/seeds', function (RouteCollectorProxy $check) {
        $check->get('/products', function (Request $request, Response $response) {
            $payload = SeedService::isiProduct();
            return JsonResponder::success($response, $payload, 'Isi tabel products');
        });
        $check->get('/outlets', function (Request $request, Response $response) {
            $payload = SeedService::isiOutlet();
            return JsonResponder::success($response, $payload, 'Isi tabel roles');
        });
    });
};
