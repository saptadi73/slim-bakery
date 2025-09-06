<?php
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use App\Services\OrderService;
use App\Supports\JsonResponder;
use App\Supports\RequestHelper;
use App\Middlewares\JwtMiddleware;
use App\Models\Order;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

return function (App $app) {
    $container = $app->getContainer();
    $app->group('/orders', function (RouteCollectorProxy $ord) use ($container) {
        $ord->post('/new', function (Request $request, Response $response) use ($container) {
            $svc = $container->get(OrderService::class);
            $data = RequestHelper::getJsonBody($request);
            try {
                return $svc->createOrder($response, $data);
            } catch (\Exception $e) {
                return JsonResponder::error($response, [
                    'message' => $e->getMessage(),
                    'type'    => get_class($e),
                    'data'    => $data,
                    'file'    => $e->getFile() . ':' . $e->getLine(),
                ], 500);
            }
        })->add(new JwtMiddleware());

        $ord->get('/{id}', function (Request $request, Response $response, array $args) {
            $id = (int)$args['id'];
            try {
                return OrderService::getOrder($response, $id);
            } catch (\Throwable $th) {
                return JsonResponder::error($response, $th->getMessage(), 500);
            }
        });

        $ord->get('', function (Request $request, Response $response) {
            try {
                return OrderService::listOrders($response);
            } catch (\Throwable $th) {
                return JsonResponder::error($response, $th->getMessage(), 500);
            }
        });

        $ord->post('/{id}/update', function (Request $request, Response $response, array $args) {
            $id = (int)$args['id'];
            $data = RequestHelper::getJsonBody($request);
            try {
                return OrderService::updateOrder($response, $id, $data);
            } catch (\Throwable $e) {
                return JsonResponder::error($response, [
                    'message' => $e->getMessage(),
                    'type'    => get_class($e),
                    'data'    => $data,
                    'file'    => $e->getFile() . ':' . $e->getLine(),
                ], 500);
            }
        })->add(new JwtMiddleware());
    });
}
?>