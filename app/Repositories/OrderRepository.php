<?php

namespace App\Repositories;

use App\Models\Order;
use Illuminate\Pagination\Paginator;

class OrderRepository
{
    public function getActiveOrders()
    {
        return Order::whereIn('status', ['pending', 'preparing', 'ready'])
            ->with(['user', 'items.product', 'queueNumber'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function getByStatus(string $status): Paginator
    {
        return Order::where('status', $status)
            ->with(['user', 'items.product', 'payments'])
            ->paginate(20);
    }

    public function getWithItems(int $orderId)
    {
        return Order::with(['items.product', 'items.modifiers.modifier', 'user', 'queueNumber'])
            ->findOrFail($orderId);
    }

    public function getQueueOrders()
    {
        return Order::with('queueNumber')
            ->whereNotNull('queue_number_id')
            ->whereIn('status', ['pending', 'preparing', 'ready'])
            ->orderBy('created_at')
            ->get();
    }
}
