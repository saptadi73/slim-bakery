<?php
namespace App\Services;

use App\Models\Product;
use Psr\Http\Message\ResponseInterface as Response;
use App\Supports\JsonResponder;
use App\Utils\Upload;
use Psr\Http\Message\UploadedFileInterface;

class ProductService
{
    public static function listProducts(Response $response)
    {
        try {
            $products = Product::all();
            return JsonResponder::success($response, $products, 'Daftar produk berhasil diambil');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }

    public static function getProduct(Response $response, $id)
    {
        try {
            $product = Product::find($id);
            if (!$product) {
                return JsonResponder::error($response, 'Produk tidak ditemukan', 404);
            }
            return JsonResponder::success($response, $product, 'Detail produk berhasil diambil');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }

    public static function createProduct(Response $response, $data, UploadedFileInterface $file)
    {

        if (empty($data['nama']) || empty($data['kode'])) {
            return JsonResponder::error($response, 'Data tidak lengkap', 400);
        }

        try {
            $product = Product::create([
                'nama' => $data['nama'],
                'kode' => $data['kode'],
                'gambar' => $data['gambar'] ?? null,
            ]);

            if ($file && $file->getError() === UPLOAD_ERR_OK) {
                $filename = Upload::storeImage($file, 'products');
                $product->gambar = $filename;
                $product->save();
            } else {
                $product->gambar = null;
                $product->save();
                $msg_file = $file ? 'File upload error code: ' . $file->getError() : 'No file uploaded';
            }
            return JsonResponder::success($response, $product, 'Produk berhasil dibuat' . ($msg_file ?? ''));
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
       
    }

    /**
     * Update gambar produk, hapus file lama jika ada, upload file baru
     * @param Response $response
     * @param int $id
     * @param UploadedFileInterface $file
     * @return Response
     */
    public static function updateProductImage(Response $response, $id, UploadedFileInterface $file)
    {
        $product = Product::find($id);
        if (!$product) {
            return JsonResponder::error($response, 'Produk tidak ditemukan', 404);
        }
        if (!$file || $file->getError() !== UPLOAD_ERR_OK) {
            return JsonResponder::error($response, 'File tidak valid', 400);
        }

        // Hapus file lama jika ada
        if ($product->gambar) {
            Upload::deleteImage($product->gambar);
        }
        // Upload file baru
        $filename = Upload::storeImage($file, 'products');
        $product->gambar = $filename;
        $product->save();

        return JsonResponder::success($response, $product, 'Gambar produk berhasil diupdate');
    }

     /**
     * Update data produk, dan jika ada file baru, hapus file lama lalu upload file baru
     * @param Response $response
     * @param int $id
     * @param array $data
     * @param UploadedFileInterface|null $file
     * @return Response
     */
    public static function updateProduct(Response $response, $id, array $data, UploadedFileInterface $file = null)
    {
        $product = Product::find($id);
        if (!$product) {
            return JsonResponder::error($response, 'Produk tidak ditemukan', 404);
        }

        // Update data dasar
        $product->nama = $data['nama'] ?? $product->nama;
        $product->kode = $data['kode'] ?? $product->kode;

        // Jika ada file baru
        if ($file && $file->getError() === UPLOAD_ERR_OK) {
            // Hapus file lama jika ada
            if ($product->gambar) {
                Upload::deleteImage($product->gambar);
            }
            // Upload file baru
            $filename = Upload::storeImage($file, 'products');
            $product->gambar = $filename;
        }

        $product->save();
        return JsonResponder::success($response, $product, 'Produk berhasil diupdate');
    }


}
