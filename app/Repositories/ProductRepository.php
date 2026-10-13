<?php

namespace App\Repositories;

use App\Models\Product;

class ProductRepository
{
    public function getActive()
    {
        return Product::where('is_active', true)
            ->with('category', 'modifiers')
            ->orderBy('display_order')
            ->get();
    }

    public function getByCategoryId(int $categoryId)
    {
        return Product::where('category_id', $categoryId)
            ->where('is_active', true)
            ->with('modifiers')
            ->orderBy('display_order')
            ->get();
    }

    public function search(string $query)
    {
        return Product::where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->with('category', 'modifiers')
            ->get();
    }
}
