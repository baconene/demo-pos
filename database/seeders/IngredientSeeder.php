<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use Illuminate\Database\Seeder;

class IngredientSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DemoCatalogSamples::INGREDIENTS as $data) {
            Ingredient::firstOrCreate(['name' => $data['name']], array_merge($data, [
                'item_type' => 'ingredient',
                'track_inventory' => true,
                'is_active' => true,
            ]));
        }

        foreach (DemoCatalogSamples::FOODS as $data) {
            Ingredient::firstOrCreate(['name' => $data['name']], array_merge($data, [
                'item_type' => Ingredient::TYPE_FOOD,
                'track_inventory' => true,
                'is_active' => true,
            ]));
        }
    }
}
