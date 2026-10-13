<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $paymentService) {}

    public function store(StorePaymentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $order = \App\Models\Order::findOrFail($data['order_id']);

        $payment = $this->paymentService->processPayment($order, $data);

        return response()->json(new PaymentResource($payment), 201);
    }

    public function orderPayments(int $orderId): JsonResponse
    {
        $payments = Payment::where('order_id', $orderId)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(PaymentResource::collection($payments));
    }

    public function refund(Payment $payment): JsonResponse
    {
        $this->authorize('update', $payment);

        $refundData = request()->validate([
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string',
        ]);

        $refund = $this->paymentService->refundPayment($payment, $refundData);

        return response()->json($refund, 201);
    }
}
