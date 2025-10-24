<?php
namespace App\Services;
use App\Models\Outlet;
use App\Supports\JsonResponder;
use Psr\Http\Message\ResponseInterface as Response;
use App\Utils\Upload;
use Psr\Http\Message\UploadedFileInterface;

class OutletService
{
    public static function listOutlets(Response $response)
    {
        try {
            $outlets = Outlet::all();
            return JsonResponder::success($response, $outlets, 'Daftar outlet berhasil diambil');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }

    public static function getOutlet(Response $response, $id)
    {
        try {
            $outlet = Outlet::find($id);
            if (!$outlet) {
                return JsonResponder::error($response, 'Outlet tidak ditemukan', 404);
            }
            return JsonResponder::success($response, $outlet, 'Detail outlet berhasil diambil');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }

    public static function createOutlet(Response $response, $data, UploadedFileInterface $file = null)
    {

        if (empty($data['nama'])) {
            return JsonResponder::error($response, 'Nama outlet harus diisi', 400);
        }

        try {
            $outlet = Outlet::create([
                'nama' => $data['nama'],
                'alamat' => $data['alamat'] ?? null,
                'phone' => $data['phone'] ?? null,
                'kode' => $data['kode'] ?? null,
                'prioritas' => $data['prioritas'] ?? null,
            ]);

            if ($file && $file->getError() === UPLOAD_ERR_OK) {
                $relativePath = Upload::storeImage($file, 'outlets');
                $outlet->gambar = $relativePath;
                $outlet->save();
            } else {
                $outlet->gambar = null;
                $outlet->save();
                $msg_file = $file ? 'File upload error code: ' . $file->getError() : 'No file uploaded';
            }
            return JsonResponder::success($response, $outlet, 'Outlet berhasil dibuat'. ($msg_file ?? ''));
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }

    public static function updateOutlet(Response $response, $id, $data, ?UploadedFileInterface $file = null)
    {
        try {
            $outlet = Outlet::find($id);
            if (!$outlet) {
                return JsonResponder::error($response, 'Outlet tidak ditemukan', 404);
            }

            if (isset($data['nama'])) {
                $outlet->nama = $data['nama'];
            }
            if (isset($data['alamat'])) {
                $outlet->alamat = $data['alamat'];
            }
            if (isset($data['phone'])) {
                $outlet->phone = $data['phone'];
            }
            if (isset($data['kode'])) {
                $outlet->kode = $data['kode'];
            }
            if (isset($data['prioritas'])) {
                $outlet->prioritas = $data['prioritas'];
            }

            if ($file && $file->getError() === UPLOAD_ERR_OK) {
                $relativePath = Upload::storeImage($file, 'outlets');
                $outlet->gambar = $relativePath;
            }

            $outlet->save();
            return JsonResponder::success($response, $outlet, 'Outlet berhasil diperbarui');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }
    /**
     * Update gambar outlet saja, hapus file lama jika ada, upload file baru
     * @param Response $response
     * @param int $id
     * @param UploadedFileInterface $file
     * @return Response
     */
    public static function updateOutletImage(Response $response, $id, UploadedFileInterface $file)
    {
        $outlet = Outlet::find($id);
        if (!$outlet) {
            return JsonResponder::error($response, 'Outlet tidak ditemukan', 404);
        }
        if (!$file || $file->getError() !== UPLOAD_ERR_OK) {
            return JsonResponder::error($response, 'File tidak valid', 400);
        }

        // Hapus file lama jika ada
        if ($outlet->gambar) {
            Upload::deleteImage($outlet->gambar);
        }
        // Upload file baru
        $relativePath = Upload::storeImage($file, 'outlets');
        $outlet->gambar = $relativePath;
        $outlet->save();

        return JsonResponder::success($response, $outlet, 'Gambar outlet berhasil diupdate');
    }


    /**
     * Hapus outlet beserta gambar jika ada
     */
    public static function deleteOutlet(Response $response, $id)
    {
        $outlet = Outlet::find($id);
        if (!$outlet) {
            return JsonResponder::error($response, 'Outlet tidak ditemukan', 404);
        }
        // Hapus gambar jika ada
        if ($outlet->gambar) {
            Upload::deleteImage($outlet->gambar);
        }
        $outlet->delete();
        return JsonResponder::success($response, null, 'Outlet berhasil dihapus');
    }

    /**
     * List semua outlet (duplikat dari listOutlets, untuk konsistensi permintaan)
     */
    public static function listOutletPriority(Response $response)
    {
        $outlets = Outlet::orderBy('prioritas', 'DESC')->get();
        return JsonResponder::success($response, $outlets, 'Daftar outlet berhasil diambil');
    }

    /**
     * Ambil outlet berdasarkan id (duplikat dari getOutlet, untuk konsistensi permintaan)
     */
    public static function getOutletById(Response $response, $id)
    {
        $outlet = Outlet::find($id);
        return JsonResponder::success($response, $outlet, 'Detail outlet berhasil diambil');
    }
}

?>