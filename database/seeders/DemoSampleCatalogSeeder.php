<?php

namespace Database\\Seeders;

use App\\Models\\Category;
use App\\Models\\Ingredient;
use App\\Models\\Modifier;
use App\\Models\\Product;
use App\\Models\\Recipe;
use App\\Models\\Supplier;
use App\\Models\\User;
use Illuminate\\Database\\Seeder;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schema;

/**
 * Seed a self-contained sample catalog only when the business catalog and
 * operational ledgers are empty. A completed initial-admin setup is allowed:
 * creating the administrator must not permanently prevent demo onboarding.
 */
class DemoSampleCatalogSeeder extends Seeder
{
    /** @var list<string> */
    private const OPERATIONAL_TABLES = [
        'orders',
        'order_items',
        'payments',
        'refunds',
        'financial_transactions',
        'inventory_transactions',
        'inventory_cost_entries',
        'purchase_orders',
        'purchase_order_items',
        'inventory_catalog_imports',
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            // Lock the real singleton guard row where available. The row exists
            // after the initial-admin migration and serializes concurrent seeds.
            $setup = DB::table('initial_admin_setup')->where('id', 1)->lockForUpdate()->first();
            $hasUsers = User::query()->exists();
            $setupMatchesUsers = $setup
                && (($setup->completed_at !== null) === $hasUsers);

            if (! $setupMatchesUsers
                || Category::query()->exists()
                || Product::query()->exists()
                || Ingredient::withTrashed()->exists()
                || Modifier::query()->exists()
                || Recipe::query()->exists()
                || Supplier::query()->exists()
                || $this->hasOperationalRecords()) {
                $this->command?->warn('Sample catalog skipped: setup, catalog, or transaction data already exists. Existing records were not modified.');

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

            $this->command?->info('Added fictional DEMO POS training items (not production stock or prices). Existing users were preserved; no transactions or user accounts were created.');
        }, attempts: 3);
    }

    private function hasOperationalRecords(): bool
    {
        foreach (self::OPERATIONAL_TABLES as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                return true;
            }
        }

        return false;
    }
}
