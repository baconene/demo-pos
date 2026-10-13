<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DemoCatalogSamples::SUPPLIERS as $data) {
            Supplier::firstOrCreate(['name' => $data['name']], [
                'is_active' => true,
            ]);
        }
    }
}
