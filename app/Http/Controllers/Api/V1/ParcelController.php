<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Parcel;
use App\Models\ParcelItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParcelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = Parcel::with(['items' => fn ($q) => $q->orderBy('item_name')])
            ->orderByRaw("FIELD(status, 'in', 'out', 'complete')")
            ->orderBy('parcel_number');

        if ($request->search) {
            $s = '%' . $request->search . '%';
            $q->where(fn ($q) => $q->where('name', 'like', $s)
                ->orWhere('parcel_number', 'like', $s)
                ->orWhere('assigned_personnel', 'like', $s));
        }

        if ($request->status) {
            $q->where('status', $request->status);
        }

        $parcels = $q->get()->map(fn ($p) => $this->format($p));

        return response()->json([
            'data'  => $parcels,
            'stats' => [
                'total'    => Parcel::count(),
                'in'       => Parcel::where('status', 'in')->count(),
                'out'      => Parcel::where('status', 'out')->count(),
                'complete' => Parcel::where('status', 'complete')->count(),
            ],
        ]);
    }

    public function show(Parcel $parcel): JsonResponse
    {
        return response()->json(['data' => $this->format($parcel->load('items'))]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->checkAuth();
        $data = $request->validate([
            'name'                => 'required|string|max:255',
            'parcel_number'       => 'nullable|string|max:50|unique:parcels,parcel_number',
            'assigned_personnel'  => 'nullable|string|max:255',
            'notes'               => 'nullable|string',
        ]);

        $parcel = Parcel::create([
            'name'               => $data['name'],
            'parcel_number'      => $data['parcel_number'] ?? Parcel::nextNumber(),
            'assigned_personnel' => $data['assigned_personnel'] ?? null,
            'notes'              => $data['notes'] ?? null,
            'status'             => 'in',
        ]);

        return response()->json(['data' => $this->format($parcel->load('items'))], 201);
    }

    public function update(Request $request, Parcel $parcel): JsonResponse
    {
        $this->checkAuth();
        $data = $request->validate([
            'name'               => 'sometimes|string|max:255',
            'parcel_number'      => 'sometimes|string|max:50|unique:parcels,parcel_number,' . $parcel->id,
            'assigned_personnel' => 'nullable|string|max:255',
            'notes'              => 'nullable|string',
        ]);

        $parcel->update($data);
        return response()->json(['data' => $this->format($parcel->fresh()->load('items'))]);
    }

    public function destroy(Parcel $parcel): JsonResponse
    {
        $this->checkAuth();
        $parcel->delete();
        return response()->json(null, 204);
    }

    // ── Items ─────────────────────────────────────────────────────────────────

    public function storeItem(Request $request, Parcel $parcel): JsonResponse
    {
        $this->checkAuth();
        $data = $request->validate([
            'item_name' => 'required|string|max:255',
            'quantity'  => 'required|integer|min:1',
            'status'    => 'in:in,out',
        ]);

        $item = ParcelItem::create([
            'parcel_id'         => $parcel->id,
            'item_name'         => $data['item_name'],
            'quantity'          => $data['quantity'],
            'status'            => $data['status'] ?? 'in',
            'status_updated_at' => now(),
        ]);

        $parcel->syncStatus();
        return response()->json(['data' => $this->format($parcel->fresh()->load('items'))], 201);
    }

    public function updateItem(Request $request, Parcel $parcel, ParcelItem $item): JsonResponse
    {
        $this->checkAuth();
        if ($item->parcel_id !== $parcel->id) abort(404);

        $data = $request->validate([
            'item_name' => 'sometimes|string|max:255',
            'quantity'  => 'sometimes|integer|min:1',
        ]);

        $item->update($data);
        return response()->json(['data' => $this->format($parcel->fresh()->load('items'))]);
    }

    public function destroyItem(Parcel $parcel, ParcelItem $item): JsonResponse
    {
        $this->checkAuth();
        if ($item->parcel_id !== $parcel->id) abort(404);

        $item->delete();
        $parcel->syncStatus();
        return response()->json(['data' => $this->format($parcel->fresh()->load('items'))]);
    }

    public function toggleItem(Parcel $parcel, ParcelItem $item): JsonResponse
    {
        if ($item->parcel_id !== $parcel->id) abort(404);

        $item->update([
            'status'            => $item->status === 'in' ? 'out' : 'in',
            'status_updated_at' => now(),
        ]);

        $parcel->syncStatus();
        return response()->json(['data' => $this->format($parcel->fresh()->load('items'))]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function format(Parcel $p): array
    {
        return [
            'id'                 => $p->id,
            'parcel_number'      => $p->parcel_number,
            'name'               => $p->name,
            'assigned_personnel' => $p->assigned_personnel,
            'status'             => $p->status,
            'notes'              => $p->notes,
            'updated_at'         => $p->updated_at?->toDateTimeString(),
            'items'              => $p->items->map(fn ($i) => [
                'id'                => $i->id,
                'item_name'         => $i->item_name,
                'quantity'          => $i->quantity,
                'status'            => $i->status,
                'status_updated_at' => $i->status_updated_at?->toDateTimeString(),
            ])->values(),
            'items_count'  => $p->items->count(),
            'items_in'     => $p->items->where('status', 'in')->count(),
            'items_out'    => $p->items->where('status', 'out')->count(),
        ];
    }

    private function checkAuth(): void
    {
        if (! auth()->user()?->hasAnyRole('admin', 'auditor')) abort(403);
    }
}
