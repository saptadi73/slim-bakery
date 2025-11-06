<?php

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use App\Services\ProductService;
use App\Supports\JsonResponder;
use App\Supports\RequestHelper;
use App\Middlewares\JwtMiddleware;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

return function (App $app) {
    $container = $app->getContainer();

    $app->group('/products', function (RouteCollectorProxy $cust) use ($container) {
        $cust->get('', function (Request $request, Response $response) {
            return ProductService::listProducts($response);
        });
        $cust->post('/new', function (Request $request, Response $response) use ($container) {
            $svc = $container->get(ProductService::class);
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

        $cust->get('/summary', function (Request $request, Response $response) {
            return ProductService::getProductSummary($response);
        });

        $cust->get('/summary/roti', function (Request $request, Response $response) {
            return ProductService::getProductSummaryCategoryRoti($response);
        });

        $cust->get('/{id}', function (Request $request, Response $response, array $args) {
            $id = (int)$args['id'];
            return ProductService::getProduct($response, $id);
        });

        $cust->post('/update/{id}', function (Request $request, Response $response, array $args) use ($container) {
            $id = (int)$args['id'];
            $svc = $container->get(ProductService::class);
            $data = RequestHelper::getJsonBody($request);
            $file = RequestHelper::getUploadedFiles($request)['file'] ?? null;
            try {
                return $svc->updateProduct($response, $id, $data, $file);
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
            $svc = $container->get(ProductService::class);
            $file = RequestHelper::getUploadedFiles($request)['file'] ?? null;
            try {
                return $svc->updateProductImage($response, $id, $file);
            } catch (\Exception $e) {
                return JsonResponder::error($response, [
                    'message' => $e->getMessage(),
                    'type'    => get_class($e),
                    'data'    => $args,
                    'file'    => $e->getFile() . ':' . $e->getLine(),
                ], 500);
            }
        })->add(new JwtMiddleware());

        $cust->post('/delete/{id}', function (Request $request, Response $response, array $args) {
            $id = (int)$args['id'];
            return ProductService::deleteProduct($response, $id);
        })->add(new JwtMiddleware());

        $cust->post('/moving', function (Request $request, Response $response) use ($container) {
            $svc = $container->get(\App\Services\StockService::class);
            $data = RequestHelper::getJsonBody($request);
            try {
                return $svc->createProductMoving($response, $data);
            } catch (\Exception $e) {
                return JsonResponder::error($response, [
                    'message' => $e->getMessage(),
                    'type'    => get_class($e),
                    'data'    => $data,
                    'file'    => $e->getFile() . ':' . $e->getLine(),
                ], 500);
            }
        })->add(new JwtMiddleware());

        $cust->get('/inventory/{id}', function (Request $request, Response $response, array $args) use($container) {
            $id = (int)$args['id'];
            $svc = $container->get(\App\Services\StockService::class);
            return $svc->getInventoryByProductId($response, $id);
        });

        $cust->post('/moving/income', function (Request $request, Response $response) use ($container) {
            $svc = $container->get(\App\Services\StockService::class);
            $data = RequestHelper::getJsonBody($request);
            try {
                return $svc->createIncomeProductMove($response, $data);
            } catch (\Exception $e) {
                return JsonResponder::error($response, [
                    'message' => $e->getMessage(),
                    'type'    => get_class($e),
                    'data'    => $data,
                    'file'    => $e->getFile() . ':' . $e->getLine(),
                ], 500);
            }
        })->add(new JwtMiddleware());

        $cust->post('moving/multi/income', function (Request $request, Response $response) use ($container) {
            $svc = $container->get(\App\Services\StockService::class);
            $data = RequestHelper::getJsonBody($request);
            try {
                return $svc->createMultiIncomeProductMove($response, $data);
            } catch (\Exception $e) {
                return JsonResponder::error($response, [
                    'message' => $e->getMessage(),
                    'type'    => get_class($e),
                    'data'    => $data,
                    'file'    => $e->getFile() . ':' . $e->getLine(),
                ], 500);
            }
        })->add(new JwtMiddleware());

        $cust->post('/moving/multi', function (Request $request, Response $response) use ($container) {
            $svc = $container->get(\App\Services\StockService::class);
            $data = RequestHelper::getJsonBody($request);
            try {
                return $svc->multiCreateProductMoving($response, $data);
            } catch (\Exception $e) {
                return JsonResponder::error($response, [
                    'message' => $e->getMessage(),
                    'type'    => get_class($e),
                    'data'    => $data,
                    'file'    => $e->getFile() . ':' . $e->getLine(),
                ], 500);
            }
        })->add(new JwtMiddleware());
    });
};
