<?php
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtMiddleware
{
    public function __invoke(Request $request, Response $response, $next)
    {
        $authHeader = $request->getHeaderLine('Authorization');
        if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $response->getBody()->write(json_encode(['error' => 'Token not provided']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        $token = $matches[1];
        $key = getenv('JWT_SECRET') ?: 'your_jwt_secret'; // Ambil dari env atau fallback
        try {
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            $request = $request->withAttribute('jwt', $decoded);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Invalid token']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        return $next($request, $response);
    }
}
