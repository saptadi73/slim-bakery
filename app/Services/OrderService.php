<?php
namespace App\Services;
use App\Models\Order;
use App\Supports\JsonResponder;
use Psr\Http\Message\ResponseInterface as Response;

class OrderService
{
    public static function createOrder(Response $response, $data)
    {
        // Validasi data (bisa ditambahkan sesuai kebutuhan)
        if (!isset($data['product_id']) || !isset($data['outlet_id']) || !isset($data['quantity'])) {
            return JsonResponder::error($response,'Data tidak lengkap', 400);
        }

        // Buat order baru
        try {
            $order = Order::create([
                'product_id' => $data['product_id'],
                'outlet_id' => $data['outlet_id'],
                'quantity' => $data['quantity'],
                'status' => 'pending', // Status awal
            ]);
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }

        return JsonResponder::success($response, $order, 'Order berhasil dibuat');
    }
}