<?php

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use App\Services\OutletService;
use App\Supports\JsonResponder;
use App\Supports\RequestHelper;
use App\Middlewares\JwtMiddleware;
use App\Models\Outlet;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

return function (App $app) {
    $container = $app->getContainer();

    $app->group('/oulets', function (RouteCollectorProxy $cust) use ($container) {
        $cust->get('', function (Request $request, Response $response) {
            try {
                return OutletService::listOutletPriority($response);
            } catch (\Throwable $th) {
                //throw $th;
                return JsonResponder::error($response, $th->getMessage(), 500);
            }
        });
        $cust->get('/all', function (Request $request, Response $response) {
            try {
                return OutletService::listOutlets($response);
            } catch (\Throwable $th) {
                //throw $th;
                return JsonResponder::error($response, $th->getMessage(), 500);
            }
        });
        $cust->get('/{id}', function (Request $request, Response $response, array $args) {
            $id = (int)$args['id'];
            return OutletService::getOutletById($response, $id);
            try {
                return OutletService::getOutletById($response, $id);
            } catch (\Throwable $th) {
                //throw $th;
                return JsonResponder::error($response, $th->getMessage(), 500);
            }
        });
        
        $cust->post('/new', function (Request $request, Response $response) use ($container) {
            $svc = $container->get(OutletService::class);
            $data = RequestHelper::getJsonBody($request);
            $file = RequestHelper::getUploadedFiles($request)['file'] ?? null;
            try {
                return $svc->createProduct($response, $data, $file);
            } catch (\Exception $e) {
                return JsonResponder::error($response, [
                    'message' => $e->getMessage(),
                    'type'    => get_class($e),
                    'data'    => $data,
                    'file'    => $e->getFile() . ':' . $e->getLine(),
                ], 500);
            }
        })->add(new JwtMiddleware());
        $cust->get('/{id}', function (Request $request, Response $response, array $args) {
            $id = (int)$args['id'];
            return OutletService::getOutletById($response, $id);
        });

        $cust->post('/update/{id}', function (Request $request, Response $response, array $args) use ($container) {
            $id = (int)$args['id'];
            $svc = $container->get(OutletService::class);
            $data = RequestHelper::getJsonBody($request);
            $file = RequestHelper::getUploadedFiles($request)['file'] ?? null;
            try {
                return $svc->updateOutlet($response, $id, $data, $file);
            } catch (\Exception $e) {
                return JsonResponder::error($response, [
                    'message' => $e->getMessage(),
                    'type'    => get_class($e),
                    'data'    => $data,
                    'file'    => $e->getFile() . ':' . $e->getLine(),
                ], 500);
            }
        })->add(new JwtMiddleware());

        $cust->post('/update/image/{id}', function (Request $request, Response $response, array $args) use ($container) {
            $id = (int)$args['id'];
            $svc = $container->get(OutletService::class);
            $file = RequestHelper::getUploadedFiles($request)['file'] ?? null;
            try {
                return $svc->updateOutletImage($response, $id, $file);
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
