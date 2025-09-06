<?php
namespace App\Services;
use App\Models\Order;
use App\Supports\JsonResponder;
use Psr\Http\Message\ResponseInterface as Response;
use Carbon\Carbon;

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
            return JsonResponder::error($response,'Data tidak lengkap', 400);
        }

        // Buat order baru
        try {
            $order = Order::create([
                'no_order' => (new self())->nextCustomerCode(),
                'product_id' => $data['product_id'],
                'outlet_id' => $data['outlet_id'],
                'quantity' => $data['quantity'],
                'pic'=> $data['pic'] ?? null,
                'tanggal'=> $data['tanggal'] ?? $now,
            ]);
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }

        return JsonResponder::success($response, $order, 'Order berhasil dibuat');
    }

    public static function listOrders(Response $response)
    {
        try {
            $orders = Order::all();
            return JsonResponder::success($response, $orders, 'Daftar order berhasil diambil');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }

    public static function getOrder(Response $response, $id)
    {
        try {
            $order = Order::find($id);
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
}