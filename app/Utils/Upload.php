<?php
namespace App\Utils;

use Psr\Http\Message\UploadedFileInterface;

final class Upload
{
    /**
     * Simpan gambar ke folder publik (default: public/uploads/{subdir}).
     * @return string Path relatif (mis. "/uploads/customers/abc123_20250830_101530.jpg")
     * @throws \RuntimeException bila validasi gagal.
     */
    public static function storeImage(UploadedFileInterface $file, string $subdir = 'customers'): string
    {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Upload error code: '.$file->getError());
        }

        // 5 MB
        $maxBytes = 1 * 1024 * 1024;
        if (($file->getSize() ?? 0) > $maxBytes) {
            throw new \RuntimeException('File too large (max 1 MB)');
        }

        // Deteksi MIME (server-side) via finfo
        $tmpPath = $file->getStream()->getMetadata('uri');
        if (!is_string($tmpPath) || !is_file($tmpPath)) {
            throw new \RuntimeException('Cannot read uploaded file stream');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = strtolower($finfo->file($tmpPath) ?: $file->getClientMediaType());

        // Whitelist
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
        ];
        if (!isset($allowed[$mime])) {
            throw new \RuntimeException('Invalid mime type: '.$mime);
        }
        $ext = $allowed[$mime];

        // Base path publik (bisa dioverride via ENV)
        $publicRoot = rtrim($_ENV['PUBLIC_PATH'] ?? (dirname(__DIR__, 2).'/public'), '/\\');
        $uploadRoot = $publicRoot.'/uploads/'.trim($subdir, '/\\');

        if (!is_dir($uploadRoot) && !mkdir($uploadRoot, 0755, true) && !is_dir($uploadRoot)) {
            throw new \RuntimeException('Cannot create upload directory');
        }

        // Nama aman & unik
        $basename = bin2hex(random_bytes(8)).'_'.date('Ymd_His');
        $destAbs  = $uploadRoot.'/'.$basename.'.'.$ext;

        // Pindahkan file
        $file->moveTo($destAbs);

        // Pastikan benar-benar tersimpan
        if (!is_file($destAbs)) {
            throw new \RuntimeException('Failed to move uploaded file');
        }

        // Kembalikan path relatif untuk disimpan di DB / dikirim ke klien
        $relative = '/uploads/'.trim($subdir, '/\\').'/'.$basename.'.'.$ext;
        return $relative;
    }
    
    public static function deleteImage(string $relativePath): bool
    {
        $publicRoot = rtrim($_ENV['PUBLIC_PATH'] ?? (dirname(__DIR__, 2).'/public'), '/\\');
        $fullPath = $publicRoot . '/' . ltrim($relativePath, '/\\');
        if (is_file($fullPath)) {
            return unlink($fullPath);
        }
        return false;
    }
}
