<?php

namespace App\Services;

use App\Models\Receive;
use App\Models\ReceiveItem;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Supports\JsonResponder;
use Psr\Http\Message\ResponseInterface as Response;
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;

class ReceiveService
{
    private function nextReceiveCode(): string
    {
        $prefix = 'REC-';

        // Get all receive numbers with the prefix
        $receives = Receive::where('no_rec', 'like', $prefix . '%')
            ->pluck('no_rec')
            ->toArray();

        $maxNumber = 0;
        foreach ($receives as $receive) {
            $num = (int)substr($receive, strlen($prefix));
            if ($num > $maxNumber) {
                $maxNumber = $num;
            }
        }

        $next = $maxNumber + 1;

        return $prefix . str_pad((string)$next, 5, '0', STR_PAD_LEFT);
    }

    public static function createReceive(Response $response, $data)
    {
        $now = Carbon::now();
        // Validasi data
        if (!isset($data['delivery_order_id']) || !isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
            return JsonResponder::error($response, 'Data delivery_order_id dan items tidak lengkap', 400);
        }

        // Cek apakah delivery_order_id valid
        $deliveryOrder = DeliveryOrder::find($data['delivery_order_id']);
        if (!$deliveryOrder) {
            return JsonResponder::error($response, 'Delivery order tidak ditemukan', 404);
        }

        // Buat receive baru
        try {
            DB::beginTransaction();

            $receive = Receive::create([
                'no_rec' => (new self())->nextReceiveCode(),
                'pic' => $data['pic'] ?? null,
                'tanggal' => $data['tanggal'] ?? $now,
                'delivery_order_id' => $data['delivery_order_id'],
            ]);

            // Buat receive items
            foreach ($data['items'] as $item) {
                if (!isset($item['delivery_order_items_id']) || !isset($item['quantity'])) {
                    return JsonResponder::error($response, 'Data item tidak lengkap', 400);
                }

                // Cek apakah delivery_order_items_id valid dan milik delivery_order_id yang diberikan
                $deliveryOrderItem = DeliveryOrderItem::where('id', $item['delivery_order_items_id'])
                    ->where('delivery_order_id', $data['delivery_order_id'])
                    ->first();
                if (!$deliveryOrderItem) {
                    return JsonResponder::error($response, 'Delivery order item tidak valid', 400);
                }

                ReceiveItem::create([
                    'receive_id' => $receive->id,
                    'delivery_order_items_id' => $item['delivery_order_items_id'],
                    'quantity' => $item['quantity'],
                    'pic' => $item['pic'] ?? null,
                    'tanggal' => $item['tanggal'] ?? $now,
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return JsonResponder::error($response, $e->getMessage(), 500);
        }

        return JsonResponder::success($response, $receive->load('receiveItems'), 'Receive berhasil dibuat');
    }

    public static function listReceives(Response $response)
    {
        try {
            $receives = Receive::with(['receiveItems.deliveryOrderItem.provider', 'deliveryOrder'])->get();
            return JsonResponder::success($response, $receives, 'Daftar receive berhasil diambil');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }

    public static function getReceive(Response $response, $id)
    {
        try {
            $receive = Receive::with(['receiveItems.deliveryOrderItem.provider', 'deliveryOrder'])->find($id);

            if (!$receive) {
                return JsonResponder::error($response, 'Receive tidak ditemukan', 404);
            }
            return JsonResponder::success($response, $receive, 'Detail receive berhasil diambil');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }

    public static function updateReceive(Response $response, $id, array $data)
    {
        try {
            $receive = Receive::find($id);
            if (!$receive) {
                return JsonResponder::error($response, 'Receive tidak ditemukan', 404);
            }

            // Update receive fields
            $updateData = [];
            if (isset($data['no_rec'])) {
                $updateData['no_rec'] = $data['no_rec'];
            }
            if (isset($data['pic'])) {
                $updateData['pic'] = $data['pic'];
            }
            if (isset($data['tanggal'])) {
                $updateData['tanggal'] = $data['tanggal'];
            }
            if (isset($data['delivery_order_id'])) {
                $deliveryOrder = DeliveryOrder::find($data['delivery_order_id']);
                if (!$deliveryOrder) {
                    return JsonResponder::error($response, 'Delivery order tidak ditemukan', 404);
                }
                $updateData['delivery_order_id'] = $data['delivery_order_id'];
            }

            if (!empty($updateData)) {
                $receive->update($updateData);
            }

            // Handle items update if provided
            if (isset($data['items']) && is_array($data['items'])) {
                // Get existing item IDs
                $existingItemIds = $receive->receiveItems->pluck('id')->toArray();
                $updatedItemIds = [];

                foreach ($data['items'] as $item) {
                    if (!isset($item['delivery_order_items_id']) || !isset($item['quantity'])) {
                        return JsonResponder::error($response, 'Data item tidak lengkap', 400);
                    }

                    // Cek apakah delivery_order_items_id valid dan milik delivery_order_id yang diberikan
                    $deliveryOrderId = $data['delivery_order_id'] ?? $receive->delivery_order_id;
                    $deliveryOrderItem = DeliveryOrderItem::where('id', $item['delivery_order_items_id'])
                        ->where('delivery_order_id', $deliveryOrderId)
                        ->first();
                    if (!$deliveryOrderItem) {
                        return JsonResponder::error($response, 'Delivery order item tidak valid', 400);
                    }

                    if (isset($item['id']) && in_array($item['id'], $existingItemIds)) {
                        // Update existing item
                        $receiveItem = ReceiveItem::find($item['id']);
                        if ($receiveItem) {
                            $receiveItem->update([
                                'delivery_order_items_id' => $item['delivery_order_items_id'],
                                'quantity' => $item['quantity'],
                                'pic' => $item['pic'] ?? $receiveItem->pic,
                                'tanggal' => $item['tanggal'] ?? $receiveItem->tanggal,
                            ]);
                            $updatedItemIds[] = $item['id'];
                        }
                    } else {
                        // Create new item
                        $newItem = ReceiveItem::create([
                            'receive_id' => $receive->id,
                            'delivery_order_items_id' => $item['delivery_order_items_id'],
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
                    ReceiveItem::whereIn('id', $itemsToDelete)->delete();
                }
            }

            $receive->save();
            return JsonResponder::success($response, $receive->load('receiveItems'), 'Receive berhasil diperbarui');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }

    public static function deleteReceive(Response $response, $id)
    {
        try {
            $receive = Receive::find($id);
            if (!$receive) {
                return JsonResponder::error($response, 'Receive tidak ditemukan', 404);
            }
            $receive->delete();
            return JsonResponder::success($response, null, 'Receive berhasil dihapus');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }
}
