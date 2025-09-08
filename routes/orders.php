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

        $ord->get('/list', function (Request $request, Response $response) use ($container) {
            $svc = $container->get(OrderService::class);
            try {
                return $svc->listOrders($response);
            } catch (\Exception $e) {
                return JsonResponder::error($response, [
                    'message' => $e->getMessage(),
                    'type'    => get_class($e),
                    'file'    => $e->getFile() . ':' . $e->getLine(),
                ], 500);
            }
        });

        $ord->get('/products', function (Request $request, Response $response) use ($container) {
            $svc = $container->get(OrderService::class);
            try {
                return $svc->SumOrdersGroupsAllByProduct($response);
            } catch (\Exception $e) {
                return JsonResponder::error($response, [
                    'message' => $e->getMessage(),
                    'type'    => get_class($e),
                    'file'    => $e->getFile() . ':' . $e->getLine(),
                ], 500);
            }
        });

        $ord->get('/outlets/{id}', function (Request $request, Response $response, array $args) use ($container) {
            $id = (int)$args['id'];
            $svc = $container->get(OrderService::class);
            try {
                return $svc->SumOrdersGroupsByOutlet($response,$id);
            } catch (\Exception $e) {
                return JsonResponder::error($response, [
                    'message' => $e->getMessage(),
                    'type'    => get_class($e),
                    'file'    => $e->getFile() . ':' . $e->getLine(),
                ], 500);
            }
        });

        $ord->get('/products/{id}', function (Request $request, Response $response, array $args) use ($container) {
            $id = (int)$args['id'];
            $svc = $container->get(OrderService::class);
            try {
                return $svc->SumOrdersGroupsByProduct($response,$id);
            } catch (\Exception $e) {
                return JsonResponder::error($response, [
                    'message' => $e->getMessage(),
                    'type'    => get_class($e),
                    'file'    => $e->getFile() . ':' . $e->getLine(),
                ], 500);
            }
        });

        $ord->get('/groups', function (Request $request, Response $response) use ($container) {
            $svc = $container->get(OrderService::class);
            try {
                return $svc->OrdersOutletGroup($response);
            } catch (\Exception $e) {
                return JsonResponder::error($response, [
                    'message' => $e->getMessage(),
                    'type'    => get_class($e),
                    'file'    => $e->getFile() . ':' . $e->getLine(),
                ], 500);
            }
        });

        $ord->get('/{id}', function (Request $request, Response $response, array $args) use ($container) {
            $id = (int)$args['id'];
            $svc = $container->get(OrderService::class);
            if ($id <= 0) {
                return JsonResponder::error($response, 'ID tidak valid', 400);
            }

            try {
                $order = $svc->getOrder($response, $id);
                if (!$order) {
                    return JsonResponder::error($response, 'Order tidak ditemukan', 404);
                }
                return $order;
            } catch (\Exception $e) {
                return JsonResponder::error($response, [
                    'message' => $e->getMessage(),
                    'type'    => get_class($e),
                    'file'    => $e->getFile() . ':' . $e->getLine(),
                ], 500);
            }
        });

    });
}
?>