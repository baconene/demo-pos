<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use RuntimeException;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DemoCatalogSamples::PRODUCTS as $i => $data) {
            $category = Category::query()->where('slug', $data['category'])->first();
            if (! $category) {
                throw new RuntimeException('Run CategorySeeder before ProductSeeder: '.$data['category']);
            }

            Product::firstOrCreate(['sku' => $data['sku']], [
                'category_id' => $category->id,
                'name' => $data['name'],
                'description' => 'Sample POS training product. Replace price, stock and cost before real sales.',
                'price' => $data['price'],
                'cost' => $data['cost'],
                'is_active' => true,
                'display_order' => $i + 1,
            ]);
        }
    }
}
