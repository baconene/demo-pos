<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (DemoCatalogSamples::CATEGORIES as $data) {
            Category::firstOrCreate(['slug' => $data['slug']], [
                'name' => $data['name'],
                'description' => 'Sample category for DEMO POS training.',
                'display_order' => $data['display_order'],
                'is_active' => true,
            ]);
        }
    }
}
