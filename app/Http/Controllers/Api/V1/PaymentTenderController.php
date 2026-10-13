<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Models\PaymentTender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentTenderController extends Controller {
    public function index(): JsonResponse {
        return response()->json(
            PaymentTender::where('is_active', true)->orderBy('display_order')->orderBy('name')->get()
        );
    }

    public function all(): JsonResponse {
        return response()->json(
            PaymentTender::orderBy('display_order')->orderBy('name')->get()
        );
    }

    public function store(Request $request): JsonResponse {
        $this->adminOnly();
        $data = $request->validate([
            'name'          => 'required|string|max:100|unique:payment_tenders,name',
            'is_active'     => 'boolean',
            'display_order' => 'nullable|integer|min:0',
        ]);
        return response()->json(PaymentTender::create($data), 201);
    }

    public function update(Request $request, PaymentTender $paymentTender): JsonResponse {
        $this->adminOnly();
        $data = $request->validate([
            'name'          => 'sometimes|string|max:100|unique:payment_tenders,name,' . $paymentTender->id,
            'is_active'     => 'boolean',
            'display_order' => 'nullable|integer|min:0',
        ]);
        $paymentTender->update($data);
        return response()->json($paymentTender->fresh());
    }

    public function destroy(PaymentTender $paymentTender): Response {
        $this->adminOnly();
        $paymentTender->delete();
        return response()->noContent();
    }

    private function adminOnly(): void {
        if (! auth()->user()?->hasAnyRole('admin')) abort(403, 'Admin only');
    }
}
