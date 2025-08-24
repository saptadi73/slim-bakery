<?php
use Slim\App;
use App\Service\AuthService;
use App\Service\UserService;
use App\Support\JsonResponder;
use App\Support\RequestHelper;
use Firebase\JWT\JWT;
use App\Middleware\JwtMiddleware;

return function (App $app) {
    $app->get('/', function ($request, $response, $args) {
        $response->getBody()->write("Hello Slim 4 + Eloquent ORM!");
        return $response;
    });

    $app->post('/register', function ($request, $response) {
        $data = RequestHelper::getJsonBody($request);
        if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
            return JsonResponder::error($response, 'Invalid input', 400);
        }
        $user = AuthService::register($data['name'], $data['email'], $data['password']);
        return JsonResponder::success($response, $user, 'User registered');
    });

    $app->post('/login', function ($request, $response) {
        $data = RequestHelper::getJsonBody($request);
        if (empty($data['email']) || empty($data['password'])) {
            return JsonResponder::error($response, 'Invalid input', 400);
        }
        $result = AuthService::login($data['email'], $data['password']);
        if ($result['success']) {
            return JsonResponder::success($response, ['token' => $result['token']], 'Login success');
        }
        return JsonResponder::error($response, $result['message'], 401);
    });

    $app->get('/profile', function ($request, $response) {
        $jwt = $request->getAttribute('jwt');
        return JsonResponder::success($response, $jwt, 'Profile data');
    })->add(new JwtMiddleware());

    $app->post('/logout', function ($request, $response) {
        $jwt = $request->getAttribute('jwt');
        if (!$jwt) {
            return JsonResponder::error($response, 'Invalid or missing token', 401);
        }
        return JsonResponder::success($response, [], 'Logout success');
    })->add(new JwtMiddleware());
};
