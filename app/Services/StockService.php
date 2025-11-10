<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Inventory;
use App\Models\ProductMoving;
use App\Supports\JsonResponder;
use Psr\Http\Message\ResponseInterface as Response;
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;

class StockService
{
    public static function createIncomeProductMove(Response $response, $data)
    {
        // Validasi data (bisa ditambahkan sesuai kebutuhan)
        if (!isset($data['product_id']) || !isset($data['outlet_id']) || !isset($data['quantity']) || !isset($data['type'])) {
            return JsonResponder::error($response, 'Data tidak lengkap', 400);
        }
        $now = Carbon::now();

        // Buat product moving baru
        try {
            $productMoving = ProductMoving::create([
                'product_id' => $data['product_id'],
                'outlet_id' => $data['outlet_id'],
                'quantity' => $data['quantity'],
                'type' => 'income', // 'income' or 'outcome'
                'tanggal' => $data['tanggal'] ?? $now,
                'pic' => $data['pic'] ?? null,
                'keterangan' => $data['keterangan'] ?? null,
            ]);
            $productMoving->save();

            $inventory = Inventory::where('product_id', $data['product_id'])
                ->orderBy('id', 'desc')
                ->first();
            $id_invetory = $inventory ? $inventory->id : null;
            $current_stock = $inventory ? $inventory->quantity : 0;
            $new_stock = $current_stock + $data['quantity'];
            $inventory = Inventory::find($id_invetory);
            if ($inventory) {
                // Update stok di inventory
                $inventory->quantity = $new_stock;
                $inventory->tanggal = $now;
                $inventory->save();
            } else {
                // Jika inventory belum ada, buat baru
                Inventory::create([
                    'product_id' => $data['product_id'],
                    'quantity' => $data['quantity'],
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
        $type = $data['type']; // Default ke 'income'
        $now = Carbon::now();
        $tanggal = $data['tanggal'] ?? $now;
        $pic = $data['pic'] ?? null;
        if ($type !== 'income' && $type !== 'outcome') {
            return JsonResponder::error($response, 'Tipe harus "income" atau "outcome"', 400);
        }
        try {
            $productMoving = ProductMoving::create([
                'product_id' => $productId,
                'outlet_id' => $outletId,
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
            $current_stock = $inventory ? $inventory->quantity : 0;

            if ($type === 'income') {
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
                $inventory->quantity = $new_stock;
                $inventory->tanggal = $now;
                $inventory->save();
            } else {
                // Jika inventory belum ada, buat baru (hanya untuk tipe 'income')
                if ($type === 'income') {
                    Inventory::create([
                        'product_id' => $productId,
                        'quantity' => $quantity,
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
            return JsonResponder::error($response, 'Data tidak lengkap', 400);
        }
        $now = Carbon::now();

        // Buat product moving baru
        try {
            $productMoving = ProductMoving::create([
                'product_id' => $data['product_id'],
                'outlet_id' => $data['outlet_id'],
                'quantity' => $data['quantity'],
                'type' => 'outcome', // 'income' or 'outcome'
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

    public static function createProductMoving(Response $response, $data)
    {
        // Validasi data
        if (!isset($data['product_id']) || !isset($data['type']) || !isset($data['quantity']) || !isset($data['outlet_id'])) {
            return JsonResponder::error($response, 'Data tidak lengkap: product_id, type, quantity, outlet_id diperlukan', 400);
        }

        $outletId = $data['outlet_id'];
        if (!is_numeric($outletId) || $outletId <= 0) {
            return JsonResponder::error($response, 'outlet_id harus angka positif', 400);
        }

        $type = $data['type'];
        if ($type !== 'income' && $type !== 'outcome') {
            return JsonResponder::error($response, 'Type harus "income" atau "outcome"', 400);
        }

        $quantity = $data['quantity'];
        if (!is_numeric($quantity) || $quantity <= 0) {
            return JsonResponder::error($response, 'Quantity harus angka positif', 400);
        }

        // Set quantity: positive for income, negative for outcome
        $movingQuantity = $type === 'income' ? $quantity : -$quantity;

        $now = Carbon::now();

        try {
            // Create ProductMoving record
            $productMoving = ProductMoving::create([
                'product_id' => $data['product_id'],
                'type' => $type,
                'outlet_id' => $outletId,
                'quantity' => $movingQuantity,
                'tanggal' => $data['tanggal'] ?? $now,
                'pic' => $data['pic'] ?? null,
                'keterangan' => $data['keterangan'] ?? null,
            ]);

            // Calculate sum of quantities from all ProductMoving for this product_id and outlet_id
            $totalQuantity = ProductMoving::where('product_id', $data['product_id'])->where('outlet_id', $outletId)->sum('quantity');

            // Update or create Inventory
            $inventory = Inventory::where('product_id', $data['product_id'])->where('outlet_id', $outletId)->first();
            if ($inventory) {
                $inventory->quantity = $totalQuantity;
                $inventory->save();
            } else {
                Inventory::create([
                    'product_id' => $data['product_id'],
                    'outlet_id' => $outletId,
                    'quantity' => $totalQuantity,
                ]);
            }

            return JsonResponder::success($response, [
                'product_moving' => $productMoving,
                'inventory' => $inventory ?? Inventory::where('product_id', $data['product_id'])->where('outlet_id', $outletId)->first(),
            ], 'Product moving berhasil dibuat dan inventory diperbarui');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }

    public static function getInventoryByProductId(Response $response, $productId)
    {
        $now = Carbon::now();
        try {
            $inventory = Inventory::where('product_id', $productId)->first();
            if (!$inventory) {
                $data = [
                    "id" => 1,
                    "product_id" => $productId,
                    "quantity" => 0,
                    "tanggal" => $now,
                    "created_at" => $now,
                    "updated_at" => $now
                ];

                return JsonResponder::success($response, $data, 'Inventory berhasil diambil');
            }
            return JsonResponder::success($response, $inventory, 'Inventory berhasil diambil');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }

    public static function createMultiIncomeProductMove(Response $response, $data)
    {
        // Validasi data utama
        if (!isset($data['products']) || !is_array($data['products']) || empty($data['products'])) {
            return JsonResponder::error($response, 'Data tidak lengkap: products (array) diperlukan', 400);
        }

        $products = $data['products'];
        $now = Carbon::now();

        $productMovings = [];
        $inventories = [];

        try {
            DB::transaction(function () use ($products, $now, &$productMovings, &$inventories) {
                foreach ($products as $productData) {
                    // Validasi per produk
                    if (!isset($productData['product_id']) || !isset($productData['outlet_id']) || !isset($productData['quantity'])) {
                        throw new \Exception('Setiap produk harus memiliki product_id, outlet_id, dan quantity');
                    }
                    $productId = $productData['product_id'];
                    $outletId = $productData['outlet_id'];
                    $quantity = $productData['quantity'];
                    if (!is_numeric($quantity) || $quantity <= 0) {
                        throw new \Exception('Quantity harus angka positif');
                    }

                    // Buat ProductMoving
                    $productMoving = ProductMoving::create([
                        'product_id' => $productId,
                        'outlet_id' => $outletId,
                        'quantity' => $quantity,
                        'type' => 'income',
                        'tanggal' => $productData['tanggal'] ?? $now,
                        'pic' => $productData['pic'] ?? null,
                        'keterangan' => $productData['keterangan'] ?? null,
                    ]);
                    $productMovings[] = $productMoving;

                    // Update Inventory per product per outlet
                    $inventory = Inventory::where('product_id', $productId)->where('outlet_id', $outletId)->first();
                    $current_stock = $inventory ? $inventory->quantity : 0;
                    $new_stock = $current_stock + $quantity;

                    if ($inventory) {
                        $inventory->quantity = $new_stock;
                        $inventory->save();
                    } else {
                        $inventory = Inventory::create([
                            'product_id' => $productId,
                            'outlet_id' => $outletId,
                            'quantity' => $quantity,
                        ]);
                    }
                    $inventories[] = $inventory;
                }
            });

            return JsonResponder::success($response, [
                'product_movings' => $productMovings,
                'inventories' => $inventories,
            ], 'Multi income product move berhasil dibuat');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }

    public static function multiCreateProductMoving(Response $response, $data)
    {
        // Validasi data utama
        if (!isset($data['products']) || !is_array($data['products']) || empty($data['products'])) {
            return JsonResponder::error($response, 'Data tidak lengkap: products (array) diperlukan', 400);
        }

        $products = $data['products'];
        $now = Carbon::now();

        $productMovings = [];
        $inventories = [];

        try {
            DB::transaction(function () use ($products, $now, &$productMovings, &$inventories) {
                foreach ($products as $productData) {
                    // Validasi per produk
                    if (!isset($productData['product_id']) || !isset($productData['outlet_id']) || !isset($productData['quantity']) || !isset($productData['type'])) {
                        throw new \Exception('Setiap produk harus memiliki product_id, outlet_id, quantity, dan type');
                    }
                    $productId = $productData['product_id'];
                    $outletId = $productData['outlet_id'];
                    $quantity = $productData['quantity'];
                    $type = $productData['type'];
                    if (!is_numeric($quantity) || $quantity <= 0) {
                        throw new \Exception('Quantity harus angka positif');
                    }
                    if ($type !== 'income' && $type !== 'outcome') {
                        throw new \Exception('Type harus "income" atau "outcome"');
                    }

                    // Set quantity for ProductMoving: positive for income, negative for outcome
                    $movingQuantity = $type === 'income' ? $quantity : -$quantity;

                    // Buat ProductMoving
                    $productMoving = ProductMoving::create([
                        'product_id' => $productId,
                        'outlet_id' => $outletId,
                        'quantity' => $movingQuantity,
                        'type' => $type,
                        'tanggal' => $productData['tanggal'] ?? $now,
                        'pic' => $productData['pic'] ?? null,
                        'keterangan' => $productData['keterangan'] ?? null,
                    ]);
                    $productMovings[] = $productMoving;

                    // Update Inventory per product per outlet
                    $inventory = Inventory::where('product_id', $productId)->where('outlet_id', $outletId)->first();
                    $current_stock = $inventory ? $inventory->quantity : 0;
                    $new_stock = $current_stock + $movingQuantity;

                    if ($type === 'outcome' && $new_stock < 0) {
                        throw new \Exception('Stok tidak mencukupi untuk pengeluaran produk ' . $productId);
                    }

                    if ($inventory) {
                        $inventory->quantity = $new_stock;
                        $inventory->tanggal = $now;
                        $inventory->save();
                    } else {
                        if ($type === 'outcome') {
                            throw new \Exception('Inventory tidak ditemukan untuk produk ' . $productId . ' pada outcome');
                        }
                        $inventory = Inventory::create([
                            'product_id' => $productId,
                            'outlet_id' => $outletId,
                            'quantity' => $new_stock,
                            'tanggal' => $now,
                        ]);
                    }
                    $inventories[] = $inventory;
                }
            });

            return JsonResponder::success($response, [
                'product_movings' => $productMovings,
                'inventories' => $inventories,
            ], 'Multi product moving berhasil dibuat');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }
}
