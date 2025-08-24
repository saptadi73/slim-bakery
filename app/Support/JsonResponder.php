<?php
namespace App\Support;

use Psr\Http\Message\ResponseInterface;

class JsonResponder
{
    public static function success(ResponseInterface $response, $data = [], $message = 'OK', $code = 200)
    {
        $payload = [
            'status' => true,
            'message' => $message,
            'data' => $data
        ];
        $response->getBody()->write(json_encode($payload));
        return $response->withStatus($code)->withHeader('Content-Type', 'application/json');
    }

    public static function error(ResponseInterface $response, $message = 'Error', $code = 400, $data = [])
    {
        $payload = [
            'status' => false,
            'message' => $message,
            'data' => $data
        ];
        $response->getBody()->write(json_encode($payload));
        return $response->withStatus($code)->withHeader('Content-Type', 'application/json');
    }
}
