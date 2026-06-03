<?php
namespace App\Services;

use App\Models\Product;
use Psr\Http\Message\ResponseInterface as Response;
use App\Supports\JsonResponder;
use App\Utils\Upload;
use Psr\Http\Message\UploadedFileInterface;

class ProductService
{
    private static function normalizeHarga($value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return is_numeric($value) ? (float) $value : 0;
    }

    private static function valuesMatch(string $field, $expected, $actual): bool
    {
        if ($expected === null || $actual === null) {
            return $expected === $actual;
        }

        if (in_array($field, ['harga', 'harga_jual', 'harga_beli'], true)) {
            return abs((float) $expected - (float) $actual) < 0.00001;
        }

        if ($field === 'category_id') {
            return (int) $expected === (int) $actual;
        }

        return (string) $expected === (string) $actual;
    }

    private static function persistAndVerify(Product $product, array $expectedChanges): ?string
    {
        if (!$product->save()) {
            return 'Perubahan produk gagal disimpan';
        }

        $product->refresh();

        foreach ($expectedChanges as $field => $expectedValue) {
            if (!self::valuesMatch($field, $expectedValue, $product->{$field})) {
                return "Perubahan field {$field} belum tersimpan di database";
            }
        }

        return null;
    }

    public static function listProducts(Response $response)
    {
        try {
            $products = Product::with('category')->orderBy('id', 'asc')->get();
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

    public static function createProduct(Response $response, $data, ?UploadedFileInterface $file = null)
    {

        if (empty($data['nama']) || empty($data['kode'])) {
            return JsonResponder::error($response, 'Data tidak lengkap', 400);
        }

        if (isset($data['harga']) && (!is_numeric($data['harga']) || (float) $data['harga'] < 0)) {
            return JsonResponder::error($response, 'Field harga harus berupa angka dan tidak boleh negatif', 422);
        }

        try {
            $harga = self::normalizeHarga($data['harga'] ?? ($data['harga_jual'] ?? 0));

            $product = Product::create([
                'nama' => $data['nama'],
                'kode' => $data['kode'],
                'gambar' => $data['gambar'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'harga' => $harga,
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
    public static function updateProduct(Response $response, $id, array $data, ?UploadedFileInterface $file = null)
    {
        $product = Product::find($id);
        if (!$product) {
            return JsonResponder::error($response, 'Produk tidak ditemukan', 404);
        }

        $hasHarga = array_key_exists('harga', $data);
        $hasHargaJual = array_key_exists('harga_jual', $data);

        if ($hasHarga && (!is_numeric($data['harga']) || (float) $data['harga'] < 0)) {
            return JsonResponder::error($response, 'Field harga harus berupa angka dan tidak boleh negatif', 422);
        }
        if ($hasHargaJual && (!is_numeric($data['harga_jual']) || (float) $data['harga_jual'] < 0)) {
            return JsonResponder::error($response, 'Field harga_jual harus berupa angka dan tidak boleh negatif', 422);
        }

        // Update data dasar
        $product->nama = $data['nama'] ?? $product->nama;
        $product->kode = $data['kode'] ?? $product->kode;
        $product->category_id = $data['category_id'] ?? $product->category_id;

        // Tetap terima harga_jual untuk kompatibilitas payload frontend lama.
        if ($hasHarga) {
            $product->harga = self::normalizeHarga($data['harga']);
        } elseif ($hasHargaJual) {
            $product->harga = self::normalizeHarga($data['harga_jual']);
        }

        // Jika ada file baru, update gambar; jika tidak, biarkan gambar tetap
        if ($file && $file->getError() === UPLOAD_ERR_OK) {
            // Hapus file lama jika ada
            if ($product->gambar) {
                Upload::deleteImage($product->gambar);
            }
            // Upload file baru
            $filename = Upload::storeImage($file, 'products');
            $product->gambar = $filename;
        }
        // Jika file tidak ada atau null, field gambar tidak diubah

        if (!$product->isDirty()) {
            return JsonResponder::error($response, 'Tidak ada perubahan data produk untuk disimpan', 400, $product);
        }

        $expectedChanges = $product->getDirty();
        $saveError = self::persistAndVerify($product, $expectedChanges);
        if ($saveError !== null) {
            return JsonResponder::error($response, $saveError, 500, [
                'expected' => $expectedChanges,
                'actual' => $product->only(array_keys($expectedChanges)),
            ]);
        }

        return JsonResponder::success($response, $product, 'Produk berhasil diupdate dan tersimpan');
    }

    public static function deleteProduct(Response $response, $id)
    {
        try {
            $product = Product::find($id);
            if (!$product) {
                return JsonResponder::error($response, 'Produk tidak ditemukan', 404);
            }

            // Hapus gambar jika ada
            if ($product->gambar) {
                Upload::deleteImage($product->gambar);
            }

            $product->delete();
            return JsonResponder::success($response, [], 'Produk berhasil dihapus');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }

    public static function getProductSummary(Response $response)
    {
        try {
            $products = Product::with('category')->get();

            $summary = $products->map(function ($product) {
                // Ambil total stok lintas outlet
                $stock = (int) \App\Models\Inventory::where('product_id', $product->id)->sum('quantity');

                // Ambil total order dari semua status item agar ringkasan nilai order akurat
                $totalOrders = (int) \App\Models\OrderItem::where('product_id', $product->id)->sum('quantity');
                $harga = (float) ($product->harga ?? 0);
                $totalHarga = $harga * $totalOrders;

                return [
                    'id' => $product->id,
                    'nama' => $product->nama,
                    'kode' => $product->kode,
                    'harga' => $harga,
                    'stock' => $stock,
                    'total_orders' => $totalOrders,
                    'total_harga' => $totalHarga,
                    'category' => $product->category ? $product->category->nama : null,
                ];
            });

            return JsonResponder::success($response, $summary, 'Ringkasan produk berhasil diambil');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }

    public static function getProductSummaryCategoryRoti(Response $response)
    {
        try {
            $products = Product::with('category')->where('category_id','>=', 1)->get();

            $summary = $products->map(function ($product) {
                // Ambil total stok lintas outlet
                $stock = (int) \App\Models\Inventory::where('product_id', $product->id)->sum('quantity');

                // Ambil total order dari semua status item agar ringkasan nilai order akurat
                $totalOrders = (int) \App\Models\OrderItem::where('product_id', $product->id)->sum('quantity');
                $harga = (float) ($product->harga ?? 0);
                $totalHarga = $harga * $totalOrders;

                return [
                    'id' => $product->id,
                    'nama' => $product->nama,
                    'kode' => $product->kode,
                    'harga' => $harga,
                    'stock' => $stock,
                    'total_orders' => $totalOrders,
                    'total_harga' => $totalHarga,
                    'category' => $product->category ? $product->category->nama : null,
                ];
            });

            return JsonResponder::success($response, $summary, 'Ringkasan produk kategori roti berhasil diambil');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }


}
