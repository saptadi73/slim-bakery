<?php
namespace App\Supports;

class DebugLogger
{
    public static function write(string $filename, array $context): void
    {
        $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'logs';
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return;
        }

        $entry = [
            'logged_at' => date('c'),
            ...$context,
        ];

        @file_put_contents(
            $dir . DIRECTORY_SEPARATOR . $filename,
            json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }
}
