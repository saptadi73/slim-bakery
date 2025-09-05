<?php
namespace App\Supports;

use Psr\Http\Message\ServerRequestInterface;

class RequestHelper
{
    public static function getJsonBody(ServerRequestInterface $request)
    {
        $contentType = $request->getHeaderLine('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $body = (string)$request->getBody();
            $data = json_decode($body, true);
            return is_array($data) ? $data : [];
        }
        $data = $request->getParsedBody();
        return is_array($data) ? $data : [];
    }

    /**
     * Ambil file upload dari request multipart/form-data
     * @param ServerRequestInterface $request
     * @return array
     */
    public static function getUploadedFiles(ServerRequestInterface $request)
    {
        $files = $request->getUploadedFiles();
        return is_array($files) ? $files : [];
    }
}
