<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class QueueMonitorController extends Controller
{
    public function index(): Response
    {
        $orders = $this->getActiveOrders();

        $products = Product::where('is_active', true)
            ->with('category')
            ->orderBy('name')
            ->get()
            ->map(fn($p) => [
                'id'    => $p->id,
                'name'  => $p->name,
                'price' => (float) $p->price,
                'category' => $p->category?->name,
            ]);

        return Inertia::render('KitchenMonitor', [
            'initialOrders' => $orders,
            'products'      => $products,
        ]);
    }

    public static function formatOrder(Order $order): array
    {
        return [
            'id'             => $order->id,
            'queue_number'   => $order->queueNumber?->number,
            'order_type'     => $order->order_type,
            'status'         => $order->status,
            'payment_status' => $order->payment_status,
            'table_number'    => $order->table_number,
            'customer_name'   => $order->customer_name,
            'customer_contact' => $order->customer_contact,
            'customer_address' => $order->customer_address,
            'notes'           => $order->notes,
            'total_amount'   => (float) $order->total_amount,
            'created_at'     => $order->created_at?->toDateTimeString(),
            'items'          => $order->items->map(fn ($item) => [
                'id'                   => $item->id,
                'quantity'             => $item->quantity,
                'unit_price'           => (float) $item->unit_price,
                'special_instructions' => $item->special_instructions,
                'product' => [
                    'id'    => $item->product->id,
                    'name'  => $item->product->name,
                    'price' => (float) $item->product->price,
                ],
            ]),
        ];
    }

    private function getActiveOrders(): array
    {
        return Order::with(['items.product', 'queueNumber'])
            ->whereIn('status', ['pending', 'preparing', 'ready'])
            ->orderBy('created_at')
            ->get()
            ->map(fn (Order $order) => self::formatOrder($order))
            ->toArray();
    }
}
