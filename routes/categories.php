<?php

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use App\Services\CategoryService;
use App\Supports\JsonResponder;
use App\Supports\RequestHelper;
use App\Middlewares\JwtMiddleware;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

return function (App $app) {
    $container = $app->getContainer();

    $app->group('/categories', function (RouteCollectorProxy $cust) use ($container) {
        $cust->get('', function (Request $request, Response $response) {
            return CategoryService::listCategories($response);
        });
        $cust->post('/new', function (Request $request, Response $response) use ($container) {
            $svc = $container->get(CategoryService::class);
            $data = RequestHelper::getJsonBody($request);
            try {
                return $svc->createCategory($response, $data);
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
            return CategoryService::getCategory($response, $id);
        });

        $cust->post('/update/{id}', function (Request $request, Response $response, array $args) use ($container) {
            $id = (int)$args['id'];
            $svc = $container->get(CategoryService::class);
            $data = RequestHelper::getJsonBody($request);
            try {
                return $svc->updateCategory($response, $id, $data);
            } catch (\Exception $e) {
                return JsonResponder::error($response, [
                    'message' => $e->getMessage(),
                    'type'    => get_class($e),
                    'data'    => $data,
                    'file'    => $e->getFile() . ':' . $e->getLine(),
                ], 500);
            }
        })->add(new JwtMiddleware());

        $cust->post('/delete/{id}', function (Request $request, Response $response, array $args) use ($container) {
            $id = (int)$args['id'];
            $svc = $container->get(CategoryService::class);
            try {
                return $svc->deleteCategory($response, $id);
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
