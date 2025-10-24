<?php
namespace App\Services;

use App\Models\Category;
use Psr\Http\Message\ResponseInterface as Response;
use App\Supports\JsonResponder;

class CategoryService
{
    public static function listCategories(Response $response)
    {
        try {
            $categories = Category::all();
            return JsonResponder::success($response, $categories, 'Daftar kategori berhasil diambil');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }

    public static function getCategory(Response $response, $id)
    {
        try {
            $category = Category::find($id);
            if (!$category) {
                return JsonResponder::error($response, 'Kategori tidak ditemukan', 404);
            }
            return JsonResponder::success($response, $category, 'Detail kategori berhasil diambil');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }

    public static function createCategory(Response $response, $data)
    {
        if (empty($data['nama'])) {
            return JsonResponder::error($response, 'Nama kategori wajib diisi', 400);
        }

        try {
            $category = Category::create([
                'nama' => $data['nama'],
                'keterangan' => $data['keterangan'] ?? null,
            ]);
            return JsonResponder::success($response, $category, 'Kategori berhasil dibuat');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }

    public static function updateCategory(Response $response, $id, $data)
    {
        $category = Category::find($id);
        if (!$category) {
            return JsonResponder::error($response, 'Kategori tidak ditemukan', 404);
        }

        try {
            $category->nama = $data['nama'] ?? $category->nama;
            $category->keterangan = $data['keterangan'] ?? $category->keterangan;
            $category->save();
            return JsonResponder::success($response, $category, 'Kategori berhasil diupdate');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }

    public static function deleteCategory(Response $response, $id)
    {
        $category = Category::find($id);
        if (!$category) {
            return JsonResponder::error($response, 'Kategori tidak ditemukan', 404);
        }

        try {
            $category->delete();
            return JsonResponder::success($response, [], 'Kategori berhasil dihapus');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }
}
