<?php

namespace Database\Seeders;

use App\Models\Modifier;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ModifierSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DemoCatalogSamples::MODIFIERS as $data) {
            Modifier::firstOrCreate(['name' => $data['name']], [
                'price' => $data['price'],
                'is_active' => true,
            ]);
        }

        foreach (DemoCatalogSamples::PRODUCTS as $data) {
            if (! $data['modifiers']) {
                continue;
            }

            $product = Product::query()->where('sku', $data['sku'])->first();
            if (! $product) {
                continue;
            }

            $ids = Modifier::query()->whereIn('name', $data['modifiers'])->pluck('id')->all();
            $product->modifiers()->syncWithoutDetaching($ids);
        }
    }
}
