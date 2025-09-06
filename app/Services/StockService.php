<?php
namespace App\Services;
use App\Models\Order;
use App\Models\Inventory;
use App\Models\ProductMoving;
use App\Supports\JsonResponder;
use Psr\Http\Message\ResponseInterface as Response;
use Carbon\Carbon;

class StockService
{
    public static function createIncomeProductMove(Response $response, $data)
    {
        // Validasi data (bisa ditambahkan sesuai kebutuhan)
        if (!isset($data['product_id']) || !isset($data['outlet_id']) || !isset($data['quantity']) || !isset($data['type'])) {
            return JsonResponder::error($response,'Data tidak lengkap', 400);
        }
        $now=Carbon::now();

        // Buat product moving baru
        try {
            $productMoving = ProductMoving::create([
                'product_id' => $data['product_id'],
                'terminal' => $data['outlet_id'],
                'quantity' => $data['quantity'],
                'type' => 'in', // 'in' atau 'out'
                'tanggal' => $data['tanggal'] ?? $now,
                'pic' => $data['pic'] ?? null,
                'keterangan' => $data['keterangan'] ?? null,
            ]);
            $productMoving->save();

            $inventory = Inventory::where('product_id', $data['product_id'])
                ->orderBy('id', 'desc')
                ->first();
            $id_invetory = $inventory ? $inventory->id : null;
            $current_stock = $inventory ? $inventory->stock : 0;
            $new_stock = $current_stock + $data['quantity'];
            $inventory = Inventory::find($id_invetory);
            if ($inventory) {
                // Update stok di inventory
                $inventory->stock = $new_stock;
                $inventory->tanggal = $now;
                $inventory->save();
            } else {
                // Jika inventory belum ada, buat baru
                Inventory::create([
                    'product_id' => $data['product_id'],
                    'stock' => $data['quantity'],
                    'tanggal' => $now,
                ]);
            }   
            JsonResponder::success($response, $inventory, 'Inventory berhasil diperbarui, Stock Bertambah');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }

        return JsonResponder::success($response, $productMoving, 'Product move berhasil dibuat');
    }

    public static function createManualProductMoving(Response $response, $data, $id)
    {
        // Validasi data
        if (!isset($data['product_id']) || !isset($data['outlet_id']) || !isset($data['quantity'])) {
            return JsonResponder::error($response, 'Data tidak lengkap', 400);
        }

        $productId = $data['product_id'];
        $outletId = $data['outlet_id'];
        $quantity = $data['quantity'];
        $type = $data['type']; // Default ke 'in
        $now=Carbon::now();
        $tanggal = $data['tanggal'] ?? $now;
        $pic = $data['pic'] ?? null;
        if ($type !== 'in' && $type !== 'out') {
            return JsonResponder::error($response, 'Tipe harus "in" atau "out"', 400);
        }
        try {
            $productMoving = ProductMoving::create([
                'product_id' => $productId,
                'terminal' => $outletId,
                'quantity' => $quantity,
                'type' => $type,
                'tanggal' => $tanggal,
                'pic' => $pic,
                'keterangan' => $data['keterangan'] ?? null,
            ]);
            $productMoving->save();

            $inventory = Inventory::where('product_id', $productId)
                ->orderBy('id', 'desc')
                ->first();
            $id_invetory = $inventory ? $inventory->id : null;
            $current_stock = $inventory ? $inventory->stock : 0;

            if ($type === 'in') {
                // Tambah stok
                $new_stock = $current_stock + $quantity;
            } else {
                // Kurangi stok, pastikan tidak negatif
                $new_stock = $current_stock - $quantity;
                if ($new_stock < 0) {
                    return JsonResponder::error($response, 'Stok tidak mencukupi untuk pengeluaran ini', 400);
                }
            }

            if ($inventory) {
                // Update stok di inventory
                $inventory->stock = $new_stock;
                $inventory->tanggal = $now;
                $inventory->save();
            } else {
                // Jika inventory belum ada, buat baru (hanya untuk tipe 'in')
                if ($type === 'in') {
                    Inventory::create([
                        'product_id' => $productId,
                        'stock' => $quantity,
                        'tanggal' => $now,
                    ]);
                } else {
                    return JsonResponder::error($response, 'Inventory tidak ditemukan untuk produk ini', 404);
                }
            }
            JsonResponder::success($response, $inventory, 'Inventory berhasil diperbarui');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }


    }

    public static function createOutcomeProductMove(Response $response, $data)
    {
        // Validasi data (bisa ditambahkan sesuai kebutuhan)
        if (!isset($data['product_id']) || !isset($data['outlet_id']) || !isset($data['quantity']) || !isset($data['type'])) {
            return JsonResponder::error($response,'Data tidak lengkap', 400);
        }
        $now=Carbon::now();

        // Buat product moving baru
        try {
            $productMoving = ProductMoving::create([
                'product_id' => $data['product_id'],
                'terminal' => $data['outlet_id'],
                'quantity' => $data['quantity'],
                'type' => 'out', // 'in' atau 'out'
                'tanggal' => $data['tanggal'] ?? $now,
                'pic' => $data['pic'] ?? null,
                'keterangan' => $data['keterangan'] ?? null,
            ]);
            $productMoving->save();

            $inventory = Inventory::where('product_id', $data['product_id'])
                ->orderBy('id', 'desc')
                ->first();
            $id_invetory = $inventory ? $inventory->id : null;
            $current_stock = $inventory ? $inventory->stock : 0;
            $new_stock = $current_stock - $data['quantity'];
            if ($new_stock < 0) {
                return JsonResponder::error($response, 'Stok tidak mencukupi untuk pengeluaran ini', 400);
            }
            $inventory = Inventory::find($id_invetory);
            if ($inventory) {
                // Update stok di inventory
                $inventory->stock = $new_stock;
                $inventory->tanggal = $now;
                $inventory->save();
            } else {
                return JsonResponder::error($response, 'Inventory tidak ditemukan untuk produk ini', 404);
            }
            JsonResponder::success($response, $inventory, 'Inventory berhasil diperbarui, Stock Berkurang');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }

        return JsonResponder::success($response, $productMoving, 'Product move berhasil dibuat');
    }
}
