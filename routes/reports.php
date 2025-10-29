<?php
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use App\Services\ReportService;
use App\Supports\JsonResponder;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

return function (App $app) {
    $container = $app->getContainer();
    $app->group('/reports', function (RouteCollectorProxy $r) use ($container) {
        $r->get('/orders', function (Request $request, Response $response) use ($container) {
            $svc = $container->get(ReportService::class);
            try {
                return $svc->getOrderReport($response);
            } catch (\Exception $e) {
                return JsonResponder::error($response, [
                    'message' => $e->getMessage(),
                    'type'    => get_class($e),
                    'file'    => $e->getFile() . ':' . $e->getLine(),
                ], 500);
            }
        });
    });
};
