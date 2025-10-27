<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Provider;
use App\Supports\JsonResponder;
use Psr\Http\Message\ResponseInterface as Response;
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;

class OrderService
{
    private function nextCustomerCode(): string
    {
        $prefix = 'ORDER-';

        // Get all order numbers with the prefix
        $orders = Order::where('no_order', 'like', $prefix . '%')
            ->pluck('no_order')
            ->toArray();

        $maxNumber = 0;
        foreach ($orders as $order) {
            $num = (int)substr($order, strlen($prefix));
            if ($num > $maxNumber) {
                $maxNumber = $num;
            }
        }

        $next = $maxNumber + 1;

        return $prefix . str_pad((string)$next, 5, '0', STR_PAD_LEFT);
    }

    public static function createOrder(Response $response, $data)
    {
        $now = Carbon::now();
        // Validasi data (bisa ditambahkan sesuai kebutuhan)
        if (!isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
            return JsonResponder::error($response, 'Data items tidak lengkap', 400);
        }

        // Buat order baru
        try {
            $order = Order::create([
                'no_order' => (new self())->nextCustomerCode(),
                'outlet_name' => $data['outlet_name'] ?? null,
                'pic_name' => $data['pic_name'] ?? null,
                'tanggal' => $data['tanggal'] ?? null,
                'status_order' => 'new',
                'keterangan' => $data['keterangan'] ?? null,
            ]);

            // Buat order items
            foreach ($data['items'] as $item) {
                if (!isset($item['product_id']) || !isset($item['outlet_id']) || !isset($item['quantity'])) {
                    return JsonResponder::error($response, 'Data item tidak lengkap', 400);
                }
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'outlet_id' => $item['outlet_id'],
                    'quantity' => $item['quantity'],
                    'pic' => $item['pic'] ?? null,
                    'tanggal' => $item['tanggal'] ?? $now,
                    'status' => $item['status'] ?? 'open',
                ]);
            }
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }

        return JsonResponder::success($response, $order->load('orderItems'), 'Order berhasil dibuat');
    }

    public static function listOrders(Response $response)
    {
        try {
            $orders = Order::with(['orderItems.product', 'orderItems.outlet'])->get();
            return JsonResponder::success($response, $orders, 'Daftar order berhasil diambil');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }

    public static function listAllOrders(Response $response)
    {
        try {
            $orders = Order::with(['orderItems.product', 'orderItems.outlet'])->get();
            return JsonResponder::success($response, $orders, 'Daftar semua order berhasil diambil');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }

    public static function listOrdersByOutlet(Response $response, $outlet_id)
    {
        try {
            $orders = Order::whereHas('orderItems', function ($query) use ($outlet_id) {
                $query->where('outlet_id', $outlet_id);
            })->with(['orderItems.product', 'orderItems.outlet'])->get();

            return JsonResponder::success($response, $orders, 'Daftar order berdasarkan outlet berhasil diambil');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }

    public static function getOrder(Response $response, $id)
    {
        try {
            $order = Order::with(['orderItems.product', 'orderItems.outlet'])->find($id);

            if (!$order) {
                return JsonResponder::error($response, 'Order tidak ditemukan', 404);
            }

            // Load providers for all order items to avoid N+1 queries
            $orderItemIds = $order->orderItems->pluck('id');
            $providers = Provider::whereIn('order_items_id', $orderItemIds)->get()->groupBy('order_items_id');

            // Add provider quantity and provider_id for each order item
            foreach ($order->orderItems as $item) {
                $itemProviders = $providers[$item->id] ?? collect();
                $providerQuantity = $itemProviders->sum('quantity');
                $item->quantity_order = $item->quantity;
                $item->quantity_provider = $providerQuantity;
                $item->provider_id = $itemProviders->first()->id ?? null;
            }

            return JsonResponder::success($response, $order, 'Detail order berhasil diambil');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }

    public static function updateOrder(Response $response, $id, array $data)
    {
        try {
            $order = Order::find($id);
            if (!$order) {
                return JsonResponder::error($response, 'Order tidak ditemukan', 404);
            }

            // Update order fields
            $order->update($data);

            // Handle items update if provided
            if (isset($data['items']) && is_array($data['items'])) {
                // Get existing item IDs
                $existingItemIds = $order->orderItems->pluck('id')->toArray();
                $updatedItemIds = [];

                foreach ($data['items'] as $item) {
                    if (!isset($item['product_id']) || !isset($item['outlet_id']) || !isset($item['quantity'])) {
                        return JsonResponder::error($response, 'Data item tidak lengkap', 400);
                    }

                    if (isset($item['id']) && in_array($item['id'], $existingItemIds)) {
                        // Update existing item
                        $orderItem = OrderItem::find($item['id']);
                        if ($orderItem) {
                            $orderItem->update([
                                'product_id' => $item['product_id'],
                                'outlet_id' => $item['outlet_id'],
                                'quantity' => $item['quantity'],
                                'pic' => $item['pic'] ?? $orderItem->pic,
                                'tanggal' => $item['tanggal'] ?? $orderItem->tanggal,
                                'status' => $item['status'] ?? $orderItem->status,
                            ]);
                            $updatedItemIds[] = $item['id'];
                        }
                    } else {
                        // Create new item
                        $newItem = OrderItem::create([
                            'order_id' => $order->id,
                            'product_id' => $item['product_id'],
                            'outlet_id' => $item['outlet_id'],
                            'quantity' => $item['quantity'],
                            'pic' => $item['pic'] ?? null,
                            'tanggal' => $item['tanggal'] ?? Carbon::now(),
                            'status' => $item['status'] ?? 'open',
                        ]);
                        $updatedItemIds[] = $newItem->id;
                    }
                }

                // Delete items not in the update list
                $itemsToDelete = array_diff($existingItemIds, $updatedItemIds);
                if (!empty($itemsToDelete)) {
                    OrderItem::whereIn('id', $itemsToDelete)->delete();
                }
            }

            $order->save();
            return JsonResponder::success($response, $order->load('orderItems'), 'Order berhasil diperbarui');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }

    public static function deleteOrder(Response $response, $id)
    {
        try {
            $order = Order::find($id);
            if (!$order) {
                return JsonResponder::error($response, 'Order tidak ditemukan', 404);
            }
            $order->delete();
            return JsonResponder::success($response, null, 'Order berhasil dihapus');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }

    public static function SumOrdersGroupsAllByProduct(Response $response)
    {
        try {
            $query = OrderItem::join('products as pro', 'order_items.product_id', '=', 'pro.id')
                ->where('order_items.status', 'open')
                ->selectRaw('SUM(order_items.quantity) as quantity,pro.kode,pro.gambar, pro.nama as product_name, pro.id as product_id')
                ->groupBy('order_items.product_id', 'pro.nama', 'pro.kode', 'pro.gambar', 'pro.id')
                ->get();


            return JsonResponder::success($response, $query, 'Jumlah order per produk berhasil diambil');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }

    public static function SumOrdersGroupsByOutlet(Response $response, $id)
    {

        try {
            $query = OrderItem::join('products as pro', 'order_items.product_id', '=', 'pro.id')
                ->join('outlets as otl', 'order_items.outlet_id', '=', 'otl.id')
                ->selectRaw('SUM(order_items.quantity) as total_quantity, pro.nama as product_name, pro.id as product_id')
                ->where('otl.id', $id)
                ->groupBy('pro.id', 'pro.nama')
                ->get();
            return JsonResponder::success($response, $query, 'Jumlah order per produk di outlet berhasil diambil');
            //code...
        } catch (\Throwable $th) {
            //throw $th;
            return JsonResponder::error($response, $th->getMessage(), 500);
        }

    }

    public static function SumOrdersGroupsByProduct(Response $response, $id)
    {
        try {
            $query = OrderItem::join('products as pro', 'order_items.product_id', '=', 'pro.id')
                ->join('outlets as otl', 'order_items.outlet_id', '=', 'otl.id')
                ->select('order_items.id as order_items_id', 'order_items.quantity', 'order_items.status', 'otl.gambar', 'otl.id as outlet_id', 'otl.nama as outlet_name', 'otl.prioritas as outlet_priority')
                ->where('pro.id', $id)
                ->where('order_items.status', 'open')
                ->orderBy('otl.prioritas', 'desc')
                ->get();
            return JsonResponder::success($response, $query, 'Detail order items per produk berhasil diambil');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }

    public static function OrdersOutletGroup(Response $response)
    {
        try {
            $query = OrderItem::join('outlets as otl', 'order_items.outlet_id', '=', 'otl.id')
                ->selectRaw('SUM(order_items.quantity) as total_quantity,otl.kode, otl.nama as outlet_name, otl.id as outlet_id,otl.gambar')
                ->groupBy('otl.id', 'otl.nama', 'otl.kode', 'otl.gambar')
                ->get();
            return JsonResponder::success($response, $query, 'Jumlah order per produk berdasarkan status berhasil diambil');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }

    public static function leftJoinProductOrders(Response $response, $id)
    {
        try {
            $leftJoinProducts = DB::table('products')
            ->leftJoin('order_items','order_items.product_id','=','products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('order_items.outlet_id', '=', $id)
            ->select('products.id as product_id', 'products.nama', 'products.kode as product_kode', 'order_items.quantity', 'order_items.id as order_item_id', 'categories.nama as category_name')
            ->get();
            return JsonResponder::success($response, $leftJoinProducts,'Semua Product untuk diorders');
        } catch (\Throwable $th) {
            return JsonResponder::error($response, $th->getMessage(), 500);
        }
    }

    public static function createProviders(Response $response, $data)
    {
        // Validasi data
        if (!isset($data['providers']) || !is_array($data['providers']) || empty($data['providers'])) {
            return JsonResponder::error($response, 'Data providers tidak lengkap', 400);
        }

        $createdProviders = [];
        $updatedOrderItems = [];

        try {
            DB::beginTransaction();

            foreach ($data['providers'] as $providerData) {
                // Validasi setiap provider data
                if (!isset($providerData['order_items_id']) || !isset($providerData['quantity'])) {
                    return JsonResponder::error($response, 'Data provider tidak lengkap: order_items_id dan quantity diperlukan', 400);
                }

                $orderItemId = $providerData['order_items_id'];
                $quantity = $providerData['quantity'];

                // Cek apakah order_item ada
                $orderItem = OrderItem::find($orderItemId);
                if (!$orderItem) {
                    return JsonResponder::error($response, "Order item dengan ID {$orderItemId} tidak ditemukan", 404);
                }

                // Cek apakah quantity provider tidak melebihi quantity order
                if ($quantity > $orderItem->quantity) {
                    return JsonResponder::error($response, "Quantity provider ({$quantity}) tidak boleh melebihi quantity order ({$orderItem->quantity}) untuk order item ID {$orderItemId}", 400);
                }

                // Buat provider
                $provider = Provider::create([
                    'order_items_id' => $orderItemId,
                    'quantity' => $quantity,
                    'tanggal' => $providerData['tanggal'] ?? Carbon::now(),
                    'pic' => $providerData['pic'] ?? null,
                ]);

                $createdProviders[] = $provider;

                // Update status order item menjadi 'provided'
                $orderItem->status = 'provided';
                $orderItem->save();

                $updatedOrderItems[] = $orderItem;
            }

            DB::commit();

            return JsonResponder::success($response, [
                'providers' => $createdProviders,
                'updated_order_items' => $updatedOrderItems
            ], 'Provider berhasil dibuat dan status order item diperbarui menjadi provided');

        } catch (\Exception $e) {
            DB::rollback();
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }

    public static function getOrderWithProductId(Response $response, $id)
    {
        try {
            $order = Order::with(['orderItems' => function ($query) {
                $query->select('id', 'order_id', 'product_id', 'outlet_id', 'quantity', 'pic', 'tanggal', 'status', 'created_at', 'updated_at');
            }, 'orderItems.outlet'])->find($id);

            if (!$order) {
                return JsonResponder::error($response, 'Order tidak ditemukan', 404);
            }

            // Load providers for all order items to avoid N+1 queries
            $orderItemIds = $order->orderItems->pluck('id');
            $providers = Provider::whereIn('order_items_id', $orderItemIds)->get()->groupBy('order_items_id');

            // Add provider quantity and provider_id for each order item, including those without providers
            foreach ($order->orderItems as $item) {
                $itemProviders = $providers[$item->id] ?? collect();
                $providerQuantity = $itemProviders->sum('quantity');
                $item->quantity_order = $item->quantity;
                $item->quantity_provider = $providerQuantity;
                $item->provider_id = $itemProviders->first()->id ?? null;
            }

            return JsonResponder::success($response, $order, 'Detail order dengan product_id berhasil diambil');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }

    public static function updateProvider(Response $response, $id, $data)
    {
        try {
            $provider = Provider::find($id);
            if (!$provider) {
                return JsonResponder::error($response, 'Provider tidak ditemukan', 404);
            }

            // Validasi data update
            $updateData = [];

            if (isset($data['quantity'])) {
                $quantity = $data['quantity'];
                // Cek apakah quantity provider tidak melebihi quantity order
                $orderItem = OrderItem::find($provider->order_items_id);
                if ($orderItem && $quantity > $orderItem->quantity) {
                    return JsonResponder::error($response, "Quantity provider ({$quantity}) tidak boleh melebihi quantity order ({$orderItem->quantity})", 400);
                }
                $updateData['quantity'] = $quantity;
            }

            if (isset($data['tanggal'])) {
                $updateData['tanggal'] = $data['tanggal'];
            }

            if (isset($data['pic'])) {
                $updateData['pic'] = $data['pic'];
            }

            if (empty($updateData)) {
                return JsonResponder::error($response, 'Tidak ada data yang akan diupdate', 400);
            }

            $provider->update($updateData);

            return JsonResponder::success($response, $provider, 'Provider berhasil diperbarui');

        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }
}
