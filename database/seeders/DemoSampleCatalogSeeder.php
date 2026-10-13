<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seed a self-contained sample catalog only on a genuinely empty client.
 * Running `db:seed` on an active client never appends demo items.
 */
class DemoSampleCatalogSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            // Lock the real singleton guard row where available. The row exists
            // after the initial-admin migration and serializes simultaneous seeds.
            $setup = DB::table('initial_admin_setup')->where('id', 1)->lockForUpdate()->first();

            if (! $setup || $setup->completed_at !== null
                || User::query()->exists()
                || Category::query()->exists()
                || Product::query()->exists()
                || Ingredient::withTrashed()->exists()) {
                $this->command?->warn('Sample catalog skipped: database has already been initialized. Existing records were not modified.');

                return;
            }

            $this->call([
                CategorySeeder::class,
                IngredientSeeder::class,
                ProductSeeder::class,
                RecipeSeeder::class,
                ModifierSeeder::class,
                SupplierSeeder::class,
            ]);

            $this->command?->info('Added fictional DEMO POS training items (not production stock or prices). No user accounts created.');
        }, attempts: 3);
    }
}
