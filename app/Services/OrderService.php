<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
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

    public static function getOrder(Response $response, $id)
    {
        try {
            $order = Order::with(['orderItems.product', 'orderItems.outlet'])->find($id);

            if (!$order) {
                return JsonResponder::error($response, 'Order tidak ditemukan', 404);
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
            $order->update($data);
            $order->save();
            return JsonResponder::success($response, $order, 'Status order berhasil diperbarui');
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
                ->selectRaw('SUM(order_items.quantity) as total_quantity,otl.gambar, otl.id as outlet_id, otl.nama as outlet_name,otl.prioritas as outlet_priority')
                ->where('pro.id', $id)
                ->groupBy('otl.id', 'otl.nama', 'otl.gambar', 'otl.prioritas')
                ->orderBy('otl.prioritas', 'desc')
                ->get();
            return JsonResponder::success($response, $query, 'Jumlah order per produk berhasil diambil');
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
}
