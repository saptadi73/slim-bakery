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

        $ord->get('/list/{outlet_id}', function (Request $request, Response $response, array $args) use ($container) {
            $outlet_id = (int)$args['outlet_id'];
            $svc = $container->get(OrderService::class);
            if ($outlet_id <= 0) {
                return JsonResponder::error($response, 'ID outlet tidak valid', 400);
            }
            try {
                return $svc->listOrdersByOutlet($response, $outlet_id);
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

        $ord->get('/leftjoin/{id}', function (Request $request, Response $response, array $args) use ($container){
            $id = (int)$args['id'];
            $svc = $container->get(OrderService::class);

            try {
                $leftJoinOrders = $svc->leftJoinProductOrders($response,$id);
                return $leftJoinOrders;
            } catch (\Throwable $th) {
                return JsonResponder::error($response, [
                    'message' => $th->getMessage(),
                    'type'    => get_class($th),
                    'file'    => $th->getFile() . ':' . $th->getLine(),
                ], 500);
            }
        });

        $ord->put('/{id}', function (Request $request, Response $response, array $args) use ($container) {
            $id = (int)$args['id'];
            $svc = $container->get(OrderService::class);
            $data = RequestHelper::getJsonBody($request);
            if ($id <= 0) {
                return JsonResponder::error($response, 'ID tidak valid', 400);
            }

            try {
                return $svc->updateOrder($response, $id, $data);
            } catch (\Exception $e) {
                return JsonResponder::error($response, [
                    'message' => $e->getMessage(),
                    'type'    => get_class($e),
                    'file'    => $e->getFile() . ':' . $e->getLine(),
                ], 500);
            }
        })->add(new JwtMiddleware());

        $ord->delete('/{id}', function (Request $request, Response $response, array $args) use ($container) {
            $id = (int)$args['id'];
            $svc = $container->get(OrderService::class);
            if ($id <= 0) {
                return JsonResponder::error($response, 'ID tidak valid', 400);
            }

            try {
                return $svc->deleteOrder($response, $id);
            } catch (\Exception $e) {
                return JsonResponder::error($response, [
                    'message' => $e->getMessage(),
                    'type'    => get_class($e),
                    'file'    => $e->getFile() . ':' . $e->getLine(),
                ], 500);
            }
        })->add(new JwtMiddleware());

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