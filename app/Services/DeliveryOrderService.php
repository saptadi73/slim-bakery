<?php

namespace App\Services;

use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Provider;
use App\Models\ProductMoving;
use App\Models\Order;
use App\Supports\JsonResponder;
use Psr\Http\Message\ResponseInterface as Response;
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;

class DeliveryOrderService
{
    private function nextDeliveryOrderCode(): string
    {
        $prefix = 'DO-';

        // Get all delivery order numbers with the prefix
        $deliveryOrders = DeliveryOrder::where('no_do', 'like', $prefix . '%')
            ->pluck('no_do')
            ->toArray();

        $maxNumber = 0;
        foreach ($deliveryOrders as $deliveryOrder) {
            $num = (int)substr($deliveryOrder, strlen($prefix));
            if ($num > $maxNumber) {
                $maxNumber = $num;
            }
        }

        $next = $maxNumber + 1;

        return $prefix . str_pad((string)$next, 5, '0', STR_PAD_LEFT);
    }

    public static function createDeliveryOrder(Response $response, $data)
    {
        $now = Carbon::now();
        // Validasi data
        if (!isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
            return JsonResponder::error($response, 'Data items tidak lengkap', 400);
        }

        // Buat delivery order baru
        try {
            DB::beginTransaction();

            $deliveryOrder = DeliveryOrder::create([
                'no_do' => (new self())->nextDeliveryOrderCode(),
                'pic' => $data['pic'] ?? null,
                'tanggal' => $data['tanggal'] ?? $now,
            ]);

            // Buat delivery order items
            foreach ($data['items'] as $item) {
                if (!isset($item['provider_id']) || !isset($item['quantity']) || !isset($item['product_id']) || !isset($item['outlet_id'])) {
                    return JsonResponder::error($response, 'Data item tidak lengkap', 400);
                }
                DeliveryOrderItem::create([
                    'delivery_order_id' => $deliveryOrder->id,
                    'provider_id' => $item['provider_id'],
                    'quantity' => $item['quantity'],
                    'pic' => $item['pic'] ?? null,
                    'tanggal' => $item['tanggal'] ?? $now,
                ]);
            }

            // Automatically create ProductMoving with type 'outcome'
            foreach ($data['items'] as $item) {
                ProductMoving::create([
                    'product_id' => $item['product_id'],
                    'type' => 'outcome',
                    'outlet_id' => $item['outlet_id'],
                    'quantity' => $item['quantity'],
                    'tanggal' => $item['tanggal'] ?? $now,
                    'pic' => $item['pic'] ?? null,
                    'keterangan' => $deliveryOrder->no_do,
                ]);
            }

            // Update order status to 'delivered' if order_id is provided
            if (isset($data['order_id'])) {
                $order = Order::find($data['order_id']);
                if ($order) {
                    $order->update(['status_order' => 'delivered']);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return JsonResponder::error($response, $e->getMessage(), 500);
        }

        return JsonResponder::success($response, $deliveryOrder->load('deliveryOrderItems'), 'Delivery order berhasil dibuat');
    }

    public static function listDeliveryOrders(Response $response)
    {
        try {
            $deliveryOrders = DeliveryOrder::with(['deliveryOrderItems.provider'])->get();
            return JsonResponder::success($response, $deliveryOrders, 'Daftar delivery order berhasil diambil');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }

    public static function getDeliveryOrder(Response $response, $id)
    {
        try {
            $deliveryOrder = DeliveryOrder::with([
                'deliveryOrderItems.provider.orderItem.product.category',
                'deliveryOrderItems.provider.orderItem.order'
            ])->find($id);

            if (!$deliveryOrder) {
                return JsonResponder::error($response, 'Delivery order tidak ditemukan', 404);
            }
            return JsonResponder::success($response, $deliveryOrder, 'Detail delivery order berhasil diambil');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }

    public static function updateDeliveryOrder(Response $response, $id, array $data)
    {
        try {
            $deliveryOrder = DeliveryOrder::find($id);
            if (!$deliveryOrder) {
                return JsonResponder::error($response, 'Delivery order tidak ditemukan', 404);
            }

            // Update delivery order fields
            $updateData = [];
            if (isset($data['no_do'])) {
                $updateData['no_do'] = $data['no_do'];
            }
            if (isset($data['pic'])) {
                $updateData['pic'] = $data['pic'];
            }
            if (isset($data['tanggal'])) {
                $updateData['tanggal'] = $data['tanggal'];
            }

            if (!empty($updateData)) {
                $deliveryOrder->update($updateData);
            }

            // Handle items update if provided
            if (isset($data['items']) && is_array($data['items'])) {
                // Get existing item IDs
                $existingItemIds = $deliveryOrder->deliveryOrderItems->pluck('id')->toArray();
                $updatedItemIds = [];

                foreach ($data['items'] as $item) {
                    if (!isset($item['provider_id']) || !isset($item['quantity'])) {
                        return JsonResponder::error($response, 'Data item tidak lengkap', 400);
                    }

                    if (isset($item['id']) && in_array($item['id'], $existingItemIds)) {
                        // Update existing item
                        $deliveryOrderItem = DeliveryOrderItem::find($item['id']);
                        if ($deliveryOrderItem) {
                            $deliveryOrderItem->update([
                                'provider_id' => $item['provider_id'],
                                'quantity' => $item['quantity'],
                                'pic' => $item['pic'] ?? $deliveryOrderItem->pic,
                                'tanggal' => $item['tanggal'] ?? $deliveryOrderItem->tanggal,
                            ]);
                            $updatedItemIds[] = $item['id'];
                        }
                    } else {
                        // Create new item
                        $newItem = DeliveryOrderItem::create([
                            'delivery_order_id' => $deliveryOrder->id,
                            'provider_id' => $item['provider_id'],
                            'quantity' => $item['quantity'],
                            'pic' => $item['pic'] ?? null,
                            'tanggal' => $item['tanggal'] ?? Carbon::now(),
                        ]);
                        $updatedItemIds[] = $newItem->id;
                    }
                }

                // Delete items not in the update list
                $itemsToDelete = array_diff($existingItemIds, $updatedItemIds);
                if (!empty($itemsToDelete)) {
                    DeliveryOrderItem::whereIn('id', $itemsToDelete)->delete();
                }
            }

            $deliveryOrder->save();
            return JsonResponder::success($response, $deliveryOrder->load('deliveryOrderItems'), 'Delivery order berhasil diperbarui');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }

    public static function deleteDeliveryOrder(Response $response, $id)
    {
        try {
            $deliveryOrder = DeliveryOrder::find($id);
            if (!$deliveryOrder) {
                return JsonResponder::error($response, 'Delivery order tidak ditemukan', 404);
            }
            $deliveryOrder->delete();
            return JsonResponder::success($response, null, 'Delivery order berhasil dihapus');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }
}
