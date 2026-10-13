<?php

namespace App\Repositories;

use App\Models\Ingredient;

class InventoryRepository
{
    public function getLowStock()
    {
        return Ingredient::whereColumn('current_quantity', '<=', 'min_quantity')
            ->where('is_active', true)
            ->get();
    }

    public function getAll()
    {
        return Ingredient::where('is_active', true)
            ->get();
    }

    public function getTransactions(int $ingredientId, int $limit = 100)
    {
        return \App\Models\InventoryTransaction::where('ingredient_id', $ingredientId)
            ->with('user')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
