<?php
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use App\Services\ReceiveService;
use App\Supports\JsonResponder;
use App\Supports\RequestHelper;
use App\Middlewares\JwtMiddleware;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

return function (App $app) {
    $container = $app->getContainer();
    $app->group('/receives', function (RouteCollectorProxy $rec) use ($container) {
        $rec->post('/new', function (Request $request, Response $response) use ($container) {
            $svc = $container->get(ReceiveService::class);
            $data = RequestHelper::getJsonBody($request);
            try {
                return $svc->createReceive($response, $data);
            } catch (\Exception $e) {
                return JsonResponder::error($response, [
                    'message' => $e->getMessage(),
                    'type'    => get_class($e),
                    'data'    => $data,
                    'file'    => $e->getFile() . ':' . $e->getLine(),
                ], 500);
            }
        })->add(new JwtMiddleware());

        $rec->get('/list', function (Request $request, Response $response) use ($container) {
            $svc = $container->get(ReceiveService::class);
            try {
                return $svc->listReceives($response);
            } catch (\Exception $e) {
                return JsonResponder::error($response, [
                    'message' => $e->getMessage(),
                    'type'    => get_class($e),
                    'file'    => $e->getFile() . ':' . $e->getLine(),
                ], 500);
            }
        });

        $rec->get('/{id}', function (Request $request, Response $response, array $args) use ($container) {
            $id = (int)$args['id'];
            $svc = $container->get(ReceiveService::class);
            if ($id <= 0) {
                return JsonResponder::error($response, 'ID tidak valid', 400);
            }

            try {
                return $svc->getReceive($response, $id);
            } catch (\Exception $e) {
                return JsonResponder::error($response, [
                    'message' => $e->getMessage(),
                    'type'    => get_class($e),
                    'file'    => $e->getFile() . ':' . $e->getLine(),
                ], 500);
            }
        });

        $rec->put('/{id}', function (Request $request, Response $response, array $args) use ($container) {
            $id = (int)$args['id'];
            $svc = $container->get(ReceiveService::class);
            $data = RequestHelper::getJsonBody($request);
            if ($id <= 0) {
                return JsonResponder::error($response, 'ID tidak valid', 400);
            }

            try {
                return $svc->updateReceive($response, $id, $data);
            } catch (\Exception $e) {
                return JsonResponder::error($response, [
                    'message' => $e->getMessage(),
                    'type'    => get_class($e),
                    'data'    => $data,
                    'file'    => $e->getFile() . ':' . $e->getLine(),
                ], 500);
            }
        })->add(new JwtMiddleware());

        $rec->delete('/{id}', function (Request $request, Response $response, array $args) use ($container) {
            $id = (int)$args['id'];
            $svc = $container->get(ReceiveService::class);
            if ($id <= 0) {
                return JsonResponder::error($response, 'ID tidak valid', 400);
            }

            try {
                return $svc->deleteReceive($response, $id);
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
