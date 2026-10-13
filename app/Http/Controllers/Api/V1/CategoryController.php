<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::where('is_active', true)
            ->orderBy('display_order')
            ->get();

        return response()->json(CategoryResource::collection($categories));
    }

    public function show(Category $category): JsonResponse
    {
        return response()->json(new CategoryResource($category));
    }

    public function store(Request $request): JsonResponse
    {
        if (! auth()->user()?->hasAnyRole('admin')) {
            abort(403, 'Only admins can manage categories');
        }

        $data = $request->validate([
            'name'          => 'required|string|max:255|unique:categories,name',
            'description'   => 'nullable|string',
            'display_order' => 'nullable|integer|min:0',
        ]);

        $category = Category::create([
            'name'          => $data['name'],
            'slug'          => Str::slug($data['name']),
            'description'   => $data['description'] ?? null,
            'display_order' => $data['display_order'] ?? 0,
            'is_active'     => true,
        ]);

        return response()->json(new CategoryResource($category), 201);
    }

    public function destroy(Category $category): Response
    {
        if (! auth()->user()?->hasAnyRole('admin')) {
            abort(403, 'Only admins can manage categories');
        }

        if ($category->products()->exists()) {
            abort(422, 'Cannot delete a category that has products.');
        }

        $category->delete();

        return response()->noContent();
    }
}
