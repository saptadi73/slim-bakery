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
                'status' => 'open',
            ]);

            // Buat delivery order items
            foreach ($data['items'] as $item) {
                if (!isset($item['provider_id']) || $item['provider_id'] === null || !isset($item['quantity']) || !isset($item['product_id']) || !isset($item['outlet_id'])) {
                    return JsonResponder::error($response, 'Data item tidak lengkap', 400);
                }
                DeliveryOrderItem::create([
                    'delivery_order_id' => $deliveryOrder->id,
                    'provider_id' => $item['provider_id'],
                    'product_id' => $item['product_id'],
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
            $deliveryOrders = DeliveryOrder::with(['deliveryOrderItems.provider.orderItem.order', 'deliveryOrderItems.provider.orderItem.outlet', 'receives'])->get();

            // Add outlet_name to each delivery order item
            foreach ($deliveryOrders as $deliveryOrder) {
                foreach ($deliveryOrder->deliveryOrderItems as $item) {
                    $item->outlet_name = $item->provider->orderItem->outlet->nama ?? null;
                }
                // Add receives_id if exists (first receive by min id)
                if ($deliveryOrder->receives && $deliveryOrder->receives->count() > 0) {
                    $deliveryOrder->receives_id = $deliveryOrder->receives->min('id');
                } else {
                    $deliveryOrder->receives_id = null;
                }
            }

            return JsonResponder::success($response, $deliveryOrders, 'Daftar delivery order berhasil diambil');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }

    public static function listDeliveryOrdersByOutlet(Response $response, $outlet_id)
    {
        try {
            $deliveryOrders = DeliveryOrder::with(['deliveryOrderItems.provider.orderItem.order', 'deliveryOrderItems.provider.orderItem.outlet', 'receives'])
                ->whereHas('deliveryOrderItems.provider.orderItem', function ($query) use ($outlet_id) {
                    $query->where('outlet_id', $outlet_id);
                })
                ->get();

            // Add outlet_name to each delivery order item
            foreach ($deliveryOrders as $deliveryOrder) {
                foreach ($deliveryOrder->deliveryOrderItems as $item) {
                    $item->outlet_name = $item->provider->orderItem->outlet->nama ?? null;
                }
                // Add receives_id if exists (first receive by min id)
                if ($deliveryOrder->receives && $deliveryOrder->receives->count() > 0) {
                    $deliveryOrder->receives_id = $deliveryOrder->receives->min('id');
                } else {
                    $deliveryOrder->receives_id = null;
                }
            }

            return JsonResponder::success($response, $deliveryOrders, 'Daftar delivery order berdasarkan outlet berhasil diambil');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }

    public static function getDeliveryOrder(Response $response, $id)
    {
        try {
            $deliveryOrder = DeliveryOrder::with([
                'deliveryOrderItems.provider.orderItem.product.category',
                'deliveryOrderItems.provider.orderItem.order',
                'receives.receiveItems'
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
            if (isset($data['status'])) {
                $updateData['status'] = $data['status'];
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
                    if (!isset($item['provider_id'])) {
                        return JsonResponder::error($response, 'Data item tidak lengkap', 400);
                    }

                    if (!isset($item['quantity'])) {
                        $item['quantity'] = 0;
                    }

                    if (isset($item['id']) && in_array($item['id'], $existingItemIds)) {
                        // Update existing item
                        $deliveryOrderItem = DeliveryOrderItem::find($item['id']);
                        if ($deliveryOrderItem) {
                            $deliveryOrderItem->update([
                                'provider_id' => $item['provider_id'],
                                'product_id' => $item['product_id'],
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
                            'product_id' => $item['product_id'],
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

    public static function closeDeliveryOrder(Response $response, $id)
    {
        try {
            $deliveryOrder = DeliveryOrder::with('deliveryOrderItems.provider.orderItem.order')->find($id);
            if (!$deliveryOrder) {
                return JsonResponder::error($response, 'Delivery order tidak ditemukan', 404);
            }

            if ($deliveryOrder->status === 'closed') {
                return JsonResponder::error($response, 'Delivery order sudah ditutup', 400);
            }

            // Update delivery order status to closed
            $deliveryOrder->update(['status' => 'closed']);

            // Find the associated order and update its status to completed
            $orderIds = [];
            foreach ($deliveryOrder->deliveryOrderItems as $item) {
                if ($item->provider && $item->provider->orderItem && $item->provider->orderItem->order) {
                    $orderIds[] = $item->provider->orderItem->order->id;
                }
            }

            $orderIds = array_unique($orderIds);
            foreach ($orderIds as $orderId) {
                $order = Order::find($orderId);
                if ($order) {
                    $order->update(['status_order' => 'completed']);
                }
            }

            return JsonResponder::success($response, $deliveryOrder->load('deliveryOrderItems'), 'Delivery order berhasil ditutup');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }
}
