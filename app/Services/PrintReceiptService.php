<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PrintServiceSetting;
use Illuminate\Support\Facades\Http;

class PrintReceiptService
{
    public function print(Order $order): array
    {
        try {
            $settings = PrintServiceSetting::getSetting();

            if (! $settings->print_enabled) {
                return ['success' => false, 'error' => 'Print service is disabled'];
            }

            $order->loadMissing(['items.product', 'payments.tender', 'queueNumber']);

            $payment    = $order->payments->where('status', 'completed')->first()
                       ?? $order->payments->first();
            $tenderName = $payment?->tender?->name ?? $payment?->method ?? null;
            $amountTendered = (float) ($payment?->amount ?? 0);
            $change     = max(0, $amountTendered - (float) $order->total_amount);

            $payload = [
                'paperWidth'      => $settings->print_paper_width,
                'storeName'       => $settings->print_store_name,
                'storeAddress'    => $settings->print_store_address,
                'storePhone'      => $settings->print_store_phone,
                'footer'          => $settings->print_footer,
                'orderId'         => $order->id,
                'queueNumber'     => $order->queueNumber?->number,
                'orderType'       => $order->order_type,
                'tableNumber'     => $order->table_number,
                'customerName'    => $order->customer_name,
                'customerContact' => $order->customer_contact,
                'notes'           => $order->notes,
                'items'           => $order->items->map(fn ($item) => [
                    'name'      => $item->product?->name ?? "Item #{$item->product_id}",
                    'quantity'  => $item->quantity,
                    'unitPrice' => (float) $item->unit_price,
                    'subtotal'  => (float) $item->subtotal,
                ])->values()->all(),
                'subtotal'        => (float) $order->subtotal,
                'discount'        => (float) $order->discount_amount,
                'total'           => (float) $order->total_amount,
                'tenderName'      => $tenderName,
                'amountTendered'  => $amountTendered,
                'change'          => $change,
            ];

            $response = Http::timeout(5)->post(
                rtrim($settings->print_service_url, '/') . '/print',
                $payload
            );

            if ($response->successful()) {
                return ['success' => true];
            }

            return ['success' => false, 'error' => 'Printer returned HTTP ' . $response->status()];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
