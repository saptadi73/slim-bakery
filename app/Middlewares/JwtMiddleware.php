<?php
namespace App\Middlewares;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\supports\JsonResponder;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response as SlimResponse;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final class JwtMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');
        if (!$authHeader || !preg_match('/Bearer\s+(.+)$/i', $authHeader, $m)) {
            return JsonResponder::error(new SlimResponse(401), 'Token not provided');
        }

        $token = trim($m[1]);
        $secret = $_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET') ?: null;
        if (!$secret) {
            return JsonResponder::error(new SlimResponse(500), 'JWT secret not configured');
        }

        try {
            // Sesuaikan algoritma jika perlu
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            $request = $request->withAttribute('jwt', $decoded);
        } catch (\Throwable $e) {
            return JsonResponder::error(new SlimResponse(401), 'Invalid token: ' . $e->getMessage());
        }

        return $handler->handle($request);
    }

    private function jsonError(int $code, string $message): Response
    {
        $resp = new SlimResponse($code);
        $resp->getBody()->write(json_encode(['message' => $message]));
        return $resp->withHeader('Content-Type', 'application/json');
    }
}
