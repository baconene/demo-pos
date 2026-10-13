<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RoyaltyRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Restores the controller required by existing royalty-rules routes. Admin only. */
class RoyaltyRuleController extends Controller
{
    public function index(): JsonResponse
    {
        $this->adminOnly();

        return response()->json(RoyaltyRule::query()
            ->with(['product:id,name', 'category:id,name', 'shareholder:id,name'])
            ->orderByDesc('effective_date')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $this->adminOnly();
        $rule = RoyaltyRule::create($this->validatedData($request));

        return response()->json($rule, 201);
    }

    public function update(Request $request, RoyaltyRule $royaltyRule): JsonResponse
    {
        $this->adminOnly();
        $royaltyRule->update($this->validatedData($request));

        return response()->json($royaltyRule);
    }

    public function destroy(RoyaltyRule $royaltyRule): JsonResponse
    {
        $this->adminOnly();
        $royaltyRule->delete();

        return response()->json(['ok' => true]);
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'scope' => ['required', Rule::in(['product', 'category'])],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'shareholder_id' => ['nullable', 'integer', 'exists:shareholders,id'],
            'royalty_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'effective_date' => ['required', 'date'],
            'expiration_date' => ['nullable', 'date', 'after_or_equal:effective_date'],
            'is_active' => ['boolean'],
        ]);

        if ($data['scope'] === 'product' && ! ($data['product_id'] ?? null)) {
            throw ValidationException::withMessages(['product_id' => 'Choose a product for a product royalty.']);
        }
        if ($data['scope'] === 'category' && ! ($data['category_id'] ?? null)) {
            throw ValidationException::withMessages(['category_id' => 'Choose a category for a category royalty.']);
        }
        $data[$data['scope'] === 'product' ? 'category_id' : 'product_id'] = null;

        return $data;
    }

    private function adminOnly(): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);
    }
}
