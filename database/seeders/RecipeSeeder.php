<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Recipe;
use Illuminate\Database\Seeder;
use RuntimeException;

class RecipeSeeder extends Seeder
{
    public function run(): void
    {
        $items = Ingredient::query()->whereIn('name', array_merge(
            array_keys(DemoCatalogSamples::FOOD_COMPONENTS),
            array_column(DemoCatalogSamples::INGREDIENTS, 'name'),
        ))->get()->keyBy('name');

        foreach (DemoCatalogSamples::FOOD_COMPONENTS as $foodName => $components) {
            $food = $items->get($foodName);
            if (! $food || ! $food->isFood()) {
                throw new RuntimeException('Missing prepared Food: '.$foodName);
            }

            foreach ($components as $ingredientName => $quantity) {
                $ingredient = $items->get($ingredientName);
                if (! $ingredient || $ingredient->isFood()) {
                    throw new RuntimeException('Missing raw ingredient: '.$ingredientName);
                }

                Recipe::firstOrCreate([
                    'food_id' => $food->id,
                    'product_id' => null,
                    'ingredient_id' => $ingredient->id,
                ], [
                    'quantity' => $quantity,
                    'unit' => $ingredient->unit,
                ]);
            }
        }

        foreach (DemoCatalogSamples::PRODUCTS as $data) {
            $product = Product::query()->where('sku', $data['sku'])->first();
            if (! $product) {
                throw new RuntimeException('Run ProductSeeder before RecipeSeeder: '.$data['sku']);
            }

            foreach ($data['ingredients'] as $ingredientName => $quantity) {
                $ingredient = $items->get($ingredientName);
                if (! $ingredient) {
                    throw new RuntimeException('Missing inventory item for recipe: '.$ingredientName);
                }

                Recipe::firstOrCreate([
                    'product_id' => $product->id,
                    'food_id' => null,
                    'ingredient_id' => $ingredient->id,
                ], [
                    'quantity' => $quantity,
                    'unit' => $ingredient->unit,
                ]);
            }
        }
    }
}
