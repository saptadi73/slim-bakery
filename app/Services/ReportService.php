<?php

namespace App\Services;

use App\Models\Order;
use App\Models\DeliveryOrder;
use App\Models\Receive;
use App\Supports\JsonResponder;
use Psr\Http\Message\ResponseInterface as Response;

class ReportService
{
    public static function getOrderReport(Response $response)
    {
        try {
            // Fetch all orders with related data
            $orders = Order::with([
                'outlet',
                'user',
                'orderItems.product.category',
                'orderItems.providers.deliveryOrderItems.deliveryOrder',
                'orderItems.providers.deliveryOrderItems.receiveItems.receive'
            ])->get();

            $summary = [
                'total_orders' => $orders->count(),
                'orders_delivered' => 0,
                'orders_closed' => 0,
                'orders_with_discrepancies' => 0,
                'total_order_items' => 0,
                'items_provided' => 0,
                'items_delivered' => 0,
                'items_received' => 0,
                'items_with_discrepancies' => 0,
            ];

            $details = [];

            foreach ($orders as $order) {
                $orderDetail = [
                    'order_id' => $order->id,
                    'no_order' => $order->no_order,
                    'outlet_name' => $order->outlet->nama ?? null,
                    'pic_name' => $order->user->name ?? null,
                    'tanggal' => $order->tanggal,
                    'status' => $order->status,
                    'keterangan' => $order->keterangan,
                    'total_ordered_quantity' => 0,
                    'total_delivered_quantity' => 0,
                    'total_received_quantity' => 0,
                    'items' => [],
                    'has_discrepancies' => false,
                ];

                $orderDelivered = $order->status === 'delivered' || $order->status === 'closed';
                $orderClosed = $order->status === 'closed';

                if ($orderDelivered) {
                    $summary['orders_delivered']++;
                }
                if ($orderClosed) {
                    $summary['orders_closed']++;
                }

                foreach ($order->orderItems as $item) {
                    $summary['total_order_items']++;

                    $itemDetail = [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->nama ?? 'Unknown',
                        'quantity_ordered' => $item->quantity,
                        'status' => $item->status,
                        'provided' => false,
                        'delivered' => false,
                        'received' => false,
                        'discrepancy' => false,
                        'providers' => $item->providers->map(function ($provider) {
                            return [
                                'provider_id' => $provider->id,
                                'provider_name' => $provider->nama ?? 'Unknown',
                                'quantity_provided' => $provider->quantity,
                            ];
                        })->toArray(),
                    ];

                    $orderDetail['total_ordered_quantity'] += $item->quantity;

                    // Check if provided (has providers with sufficient quantity or status is provided)
                    $totalProvidedQuantity = $item->providers->sum('quantity');
                    if ($item->status === 'provided' || $totalProvidedQuantity >= $item->quantity) {
                        $itemDetail['provided'] = true;
                        $summary['items_provided']++;
                    }

                    // Check if delivered (has delivery order item)
                    $deliveredQuantity = 0;
                    foreach ($item->providers as $provider) {
                        foreach ($provider->deliveryOrderItems as $deliveryOrderItem) {
                            if ($deliveryOrderItem->deliveryOrder) {
                                $deliveredQuantity += $deliveryOrderItem->quantity;
                            }
                        }
                    }
                    $orderDetail['total_delivered_quantity'] += $deliveredQuantity;
                    if ($deliveredQuantity >= $item->quantity) {
                        $itemDetail['delivered'] = true;
                        $summary['items_delivered']++;
                    }

                    // Check if received (has receive item through delivery order items)
                    $receivedQuantity = 0;
                    foreach ($item->providers as $provider) {
                        foreach ($provider->deliveryOrderItems as $deliveryOrderItem) {
                            foreach ($deliveryOrderItem->receiveItems as $receiveItem) {
                                if ($receiveItem->receive) {
                                    $receivedQuantity += $receiveItem->quantity;
                                }
                            }
                        }
                    }
                    $orderDetail['total_received_quantity'] += $receivedQuantity;
                    if ($receivedQuantity >= $item->quantity) {
                        $itemDetail['received'] = true;
                        $summary['items_received']++;
                    }

                    // Check for discrepancies
                    if (!$itemDetail['provided'] || !$itemDetail['delivered'] || !$itemDetail['received']) {
                        $itemDetail['discrepancy'] = true;
                        $summary['items_with_discrepancies']++;
                        $orderDetail['has_discrepancies'] = true;
                    }

                    $orderDetail['items'][] = $itemDetail;
                }

                if ($orderDetail['has_discrepancies']) {
                    $summary['orders_with_discrepancies']++;
                }

                $details[] = $orderDetail;
            }

            $report = [
                'summary' => $summary,
                'details' => $details,
            ];

            return JsonResponder::success($response, $report, 'Laporan order berhasil diambil');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }

    public static function getOrderReportById(Response $response, $orderId)
    {
        try {
            // Fetch specific order with related data
            $order = Order::with([
                'outlet',
                'user',
                'orderItems.product.category',
                'orderItems.providers',
                'orderItems.providers.deliveryOrderItems.deliveryOrder',
                'orderItems.providers.deliveryOrderItems.receiveItems.receive'
            ])->find($orderId);

            if (!$order) {
                return JsonResponder::error($response, 'Order tidak ditemukan', 404);
            }

            $report = [
                'order_id' => $order->id,
                'no_order' => $order->no_order,
                'outlet_name' => $order->outlet->nama ?? null,
                'pic_name' => $order->user->name ?? null,
                'tanggal' => $order->tanggal,
                'status' => $order->status,
                'keterangan' => $order->keterangan,
                'updated_at' => $order->updated_at,
                'items' => [],
            ];

            foreach ($order->orderItems as $item) {
                $itemReport = [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->nama ?? 'Unknown',
                    'category' => $item->product->category->nama ?? null,
                    'segments' => [
                        'ordered' => [
                            'quantity' => $item->quantity,
                            'pic' => $item->pic,
                            'tanggal' => $item->tanggal,
                            'updated_at' => $item->updated_at,
                            'keterangan' => $item->keterangan ?? null,
                        ],
                        'provided' => [
                            'total_quantity' => $item->providers->sum('quantity'),
                            'providers' => $item->providers->map(function ($provider) {
                                return [
                                    'provider_id' => $provider->id,
                                    'nama' => $provider->nama ?? 'Unknown',
                                    'quantity' => $provider->quantity,
                                    'pic' => $provider->pic,
                                    'tanggal' => $provider->tanggal,
                                    'updated_at' => $provider->updated_at,
                                    'keterangan' => $provider->keterangan ?? null,
                                ];
                            })->toArray(),
                        ],
                        'delivered' => [
                            'total_quantity' => 0,
                            'deliveries' => [],
                        ],
                        'received' => [
                            'total_quantity' => 0,
                            'receives' => [],
                        ],
                    ],
                ];

                // Collect deliveries
                $deliveredQuantity = 0;
                foreach ($item->providers as $provider) {
                    foreach ($provider->deliveryOrderItems as $deliveryOrderItem) {
                        if ($deliveryOrderItem->deliveryOrder) {
                            $deliveredQuantity += $deliveryOrderItem->quantity;
                            $itemReport['segments']['delivered']['deliveries'][] = [
                                'delivery_order_id' => $deliveryOrderItem->deliveryOrder->id,
                                'no_do' => $deliveryOrderItem->deliveryOrder->no_do,
                                'quantity' => $deliveryOrderItem->quantity,
                                'pic' => $deliveryOrderItem->pic,
                                'tanggal' => $deliveryOrderItem->tanggal,
                                'updated_at' => $deliveryOrderItem->updated_at,
                                'keterangan' => $deliveryOrderItem->deliveryOrder->keterangan ?? null,
                            ];
                        }
                    }
                }
                $itemReport['segments']['delivered']['total_quantity'] = $deliveredQuantity;

                // Collect receives
                $receivedQuantity = 0;
                foreach ($item->providers as $provider) {
                    foreach ($provider->deliveryOrderItems as $deliveryOrderItem) {
                        foreach ($deliveryOrderItem->receiveItems as $receiveItem) {
                            if ($receiveItem->receive) {
                                $receivedQuantity += $receiveItem->quantity;
                                $itemReport['segments']['received']['receives'][] = [
                                    'receive_id' => $receiveItem->receive->id,
                                    'no_rec' => $receiveItem->receive->no_rec,
                                    'quantity' => $receiveItem->quantity,
                                    'pic' => $receiveItem->pic,
                                    'tanggal' => $receiveItem->tanggal,
                                    'updated_at' => $receiveItem->updated_at,
                                    'keterangan' => $receiveItem->receive->keterangan ?? null,
                                ];
                            }
                        }
                    }
                }
                $itemReport['segments']['received']['total_quantity'] = $receivedQuantity;

                $report['items'][] = $itemReport;
            }

            return JsonResponder::success($response, $report, 'Laporan order berhasil diambil');
        } catch (\Exception $e) {
            return JsonResponder::error($response, $e->getMessage(), 500);
        }
    }
}
