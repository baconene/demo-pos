<?php

namespace App\Jobs;

use App\Models\Ingredient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckLowStockJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $lowStockItems = Ingredient::whereColumn('current_quantity', '<=', 'min_quantity')
            ->where('is_active', true)
            ->get();

        foreach ($lowStockItems as $item) {
            event(new \App\Events\LowStockAlert($item));
        }
    }
}
