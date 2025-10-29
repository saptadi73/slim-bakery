<?php
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use App\Services\DeliveryOrderService;
use App\Supports\JsonResponder;
use App\Supports\RequestHelper;
use App\Middlewares\JwtMiddleware;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

return function (App $app) {
    $container = $app->getContainer();
    $app->group('/delivery-orders', function (RouteCollectorProxy $do) use ($container) {
        $do->post('/new', function (Request $request, Response $response) use ($container) {
            $svc = $container->get(DeliveryOrderService::class);
            $data = RequestHelper::getJsonBody($request);
            try {
                return $svc->createDeliveryOrder($response, $data);
            } catch (\Exception $e) {
                return JsonResponder::error($response, [
                    'message' => $e->getMessage(),
                    'type'    => get_class($e),
                    'data'    => $data,
                    'file'    => $e->getFile() . ':' . $e->getLine(),
                ], 500);
            }
        })->add(new JwtMiddleware());

        $do->get('/list', function (Request $request, Response $response) use ($container) {
            $svc = $container->get(DeliveryOrderService::class);
            try {
                return $svc->listDeliveryOrders($response);
            } catch (\Exception $e) {
                return JsonResponder::error($response, [
                    'message' => $e->getMessage(),
                    'type'    => get_class($e),
                    'file'    => $e->getFile() . ':' . $e->getLine(),
                ], 500);
            }
        });

        $do->get('/{id}', function (Request $request, Response $response, array $args) use ($container) {
            $id = (int)$args['id'];
            $svc = $container->get(DeliveryOrderService::class);
            if ($id <= 0) {
                return JsonResponder::error($response, 'ID tidak valid', 400);
            }

            try {
                return $svc->getDeliveryOrder($response, $id);
            } catch (\Exception $e) {
                return JsonResponder::error($response, [
                    'message' => $e->getMessage(),
                    'type'    => get_class($e),
                    'file'    => $e->getFile() . ':' . $e->getLine(),
                ], 500);
            }
        });

        $do->put('/{id}', function (Request $request, Response $response, array $args) use ($container) {
            $id = (int)$args['id'];
            $svc = $container->get(DeliveryOrderService::class);
            $data = RequestHelper::getJsonBody($request);
            if ($id <= 0) {
                return JsonResponder::error($response, 'ID tidak valid', 400);
            }

            try {
                return $svc->updateDeliveryOrder($response, $id, $data);
            } catch (\Exception $e) {
                return JsonResponder::error($response, [
                    'message' => $e->getMessage(),
                    'type'    => get_class($e),
                    'data'    => $data,
                    'file'    => $e->getFile() . ':' . $e->getLine(),
                ], 500);
            }
        })->add(new JwtMiddleware());

        $do->delete('/{id}', function (Request $request, Response $response, array $args) use ($container) {
            $id = (int)$args['id'];
            $svc = $container->get(DeliveryOrderService::class);
            if ($id <= 0) {
                return JsonResponder::error($response, 'ID tidak valid', 400);
            }

            try {
                return $svc->deleteDeliveryOrder($response, $id);
            } catch (\Exception $e) {
                return JsonResponder::error($response, [
                    'message' => $e->getMessage(),
                    'type'    => get_class($e),
                    'file'    => $e->getFile() . ':' . $e->getLine(),
                ], 500);
            }
        })->add(new JwtMiddleware());

        $do->post('/{id}/close', function (Request $request, Response $response, array $args) use ($container) {
            $id = (int)$args['id'];
            $svc = $container->get(DeliveryOrderService::class);
            if ($id <= 0) {
                return JsonResponder::error($response, 'ID tidak valid', 400);
            }

            try {
                return $svc->closeDeliveryOrder($response, $id);
            } catch (\Exception $e) {
                return JsonResponder::error($response, [
                    'message' => $e->getMessage(),
                    'type'    => get_class($e),
                    'file'    => $e->getFile() . ':' . $e->getLine(),
                ], 500);
            }
        })->add(new JwtMiddleware());
    });
};
