<?php

namespace App\Services;

use App\Models\Order;
use App\Supports\JsonResponder;
use Psr\Http\Message\ResponseInterface as Response;
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;

class OrderService
{
    private function nextCustomerCode(): string
    {
        $prefix = 'ORDER-';

        $last = Order::where('no_order', 'like', $prefix . '%')
            ->orderBy('no_order')   // aman jika 5 digit zero-pad
            ->value('no_order');

        $next = 1;
        if ($last && preg_match('/^' . preg_quote($prefix, '/') . '(\d{5})$/', $last, $m)) {
            $next = ((int)$m[1]) + 1;
        }

        return $prefix . str_pad((string)$next, 5, '0', STR_PAD_LEFT);
    }

    public static function createOrder(Response $response, $data)
    {
        $now = Carbon::now();
        // Validasi data (bisa ditambahkan sesuai kebutuhan)
        if (!isset($data['product_id']) || !isset($data['outlet_id']) || !isset($data['quantity'])) {
            return JsonResponder::error($response, 'Data tidak lengkap', 400);
        }

        // Buat order baru
        try {
            $order = Order::create([
                'no_order' => (new self())->nextCustomerCode(),
                'product_id' => $data['product_id'],
                'outlet_id' => $data['outlet_id'],
                'quantity' => $data['quantity'],
                'pic' => $data['pic'] ?? null,
                'tanggal' => $data['tanggal'] ?? $now,
            ]);
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }

        return JsonResponder::success($response, $order, 'Order berhasil dibuat');
    }

    public static function listOrders(Response $response)
    {
        try {
            $orders = Order::join('products', 'orders.product_id', '=', 'products.id')
                ->join('outlets', 'orders.outlet_id', '=', 'outlets.id')
                ->select('orders.*','products.gambar', 'products.nama as product_name', 'outlets.nama as outlet_name')
                ->get();
            return JsonResponder::success($response, $orders, 'Daftar order berhasil diambil');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }

    public static function getOrder(Response $response, $id)
    {
        try {
            $order = Order::find($id)
            ->join('products', 'orders.product_id', '=', 'products.id')
            ->join('outlets', 'orders.outlet_id', '=', 'outlets.id')
            ->select('orders.*', 'products.nama as product_name', 'outlets.nama as outlet_name')
            ->first();

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
            $query = Order::join('products as pro', 'orders.product_id', '=', 'pro.id')
                ->selectRaw('SUM(orders.quantity) as quantity,pro.kode,pro.gambar, pro.nama as product_name, pro.id as product_id')
                ->groupBy('orders.product_id', 'pro.nama')
                ->get();


            return JsonResponder::success($response, $query, 'Jumlah order per produk berhasil diambil');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }

    public static function SumOrdersGroupsByOutlet(Response $response, $id)
    {
     
        try {
            $query = Order::join('products as pro', 'orders.product_id', '=', 'pro.id')
                ->join('outlets as otl', 'orders.outlet_id', '=', 'otl.id')
                ->selectRaw('SUM(orders.quantity) as total_quantity, pro.nama as product_name, pro.id as product_id')
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
            $query = Order::join('products as pro', 'orders.product_id', '=', 'pro.id')
                ->join('outlets as otl', 'orders.outlet_id', '=', 'otl.id')
                ->selectRaw('SUM(orders.quantity) as total_quantity,otl.gambar, otl.id as outlet_id, otl.nama as outlet_name,otl.prioritas as outlet_priority')
                ->where('pro.id', $id)
                ->groupBy('otl.id', 'otl.nama')
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
            $query = Order::join('outlets as otl', 'orders.outlet_id', '=', 'otl.id')
                ->selectRaw('SUM(orders.quantity) as total_quantity,otl.kode, otl.nama as outlet_name, otl.id as outlet_id,otl.gambar')
                ->groupBy('otl.id', 'otl.nama')
                ->get();
            return JsonResponder::success($response, $query, 'Jumlah order per produk berdasarkan status berhasil diambil');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }
}
