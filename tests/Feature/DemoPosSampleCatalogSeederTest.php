<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Modifier;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoCatalogSamples;
use Database\Seeders\UserSeeder;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class DemoPosSampleCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_seed_provides_training_catalog_without_creating_any_users(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, User::count());
        $this->assertSame(count(DemoCatalogSamples::CATEGORIES), Category::count());
        $this->assertSame(count(DemoCatalogSamples::PRODUCTS), Product::count());
        $this->assertSame(count(DemoCatalogSamples::INGREDIENTS) + count(DemoCatalogSamples::FOODS), Ingredient::count());
        $this->assertSame(count(DemoCatalogSamples::FOODS), Ingredient::food()->count());
        $this->assertSame(count(DemoCatalogSamples::MODIFIERS), Modifier::count());
        $this->assertSame(count(DemoCatalogSamples::SUPPLIERS), Supplier::count());
        $this->assertSame(5, DB::table('payment_tenders')->count());
        $this->assertTrue(DB::table('initial_admin_setup')->where('id', 1)->whereNull('completed_at')->exists());
    }

    public function test_food_components_and_product_recipes_are_linked_separately(): void
    {
        $this->seed(DatabaseSeeder::class);

        $food = Ingredient::where('name', 'Demo Grilled Chicken')->firstOrFail();
        $product = Product::where('sku', 'DEMO-MEAL-01')->firstOrFail();

        $this->assertTrue($food->isFood());
        $this->assertSame(4, $food->components()->count());
        $this->assertSame(0, Recipe::where('food_id', $food->id)->whereNotNull('product_id')->count());
        $this->assertSame(2, $product->recipes()->count());
        $this->assertSame(1, $product->recipes()->where('ingredient_id', $food->id)->count());
        $this->assertTrue($product->modifiers()->where('name', 'Extra Rice')->exists());
        $this->assertSame(0, DB::table('orders')->count());
        $this->assertSame(0, DB::table('inventory_transactions')->count());
    }

    public function test_second_seed_does_not_duplicate_or_reset_catalog_or_stock(): void
    {
        $this->seed(DatabaseSeeder::class);
        $counts = [Category::count(), Product::count(), Ingredient::count(), Recipe::count(), Modifier::count(), Supplier::count()];
        $item = Ingredient::where('name', 'Coffee Beans')->firstOrFail();
        $item->update(['current_quantity' => 0.75]);
        $product = Product::where('sku', 'DEMO-BURGER-01')->firstOrFail();
        $product->update(['price' => 350]);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($counts, [Category::count(), Product::count(), Ingredient::count(), Recipe::count(), Modifier::count(), Supplier::count()]);
        $this->assertEquals(0.75, Ingredient::findOrFail($item->id)->current_quantity);
        $this->assertEquals(350, Product::findOrFail($product->id)->price);
    }

    public function test_existing_customer_catalog_is_never_mixed_with_demo_items(): void
    {
        Category::create(['name' => 'Live Catalog', 'slug' => 'live-catalog']);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, Category::count());
        $this->assertSame(0, Product::count());
        $this->assertSame(0, Ingredient::count());
    }

    public function test_existing_users_block_automatic_demo_catalog(): void
    {
        User::factory()->create();
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, Category::count());
        $this->assertSame(0, Product::count());
    }

    public function test_completed_admin_setup_allows_training_catalog_when_business_ledgers_are_empty(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole(Role::findByName('admin', 'web'));
        DB::table('initial_admin_setup')->where('id', 1)->update(['completed_at' => now()]);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, User::count());
        $this->assertSame(count(DemoCatalogSamples::CATEGORIES), Category::count());
        $this->assertSame(count(DemoCatalogSamples::PRODUCTS), Product::count());
        $this->assertSame(count(DemoCatalogSamples::INGREDIENTS) + count(DemoCatalogSamples::FOODS), Ingredient::count());
        $this->assertSame(0, DB::table('orders')->count());
        $this->assertSame(0, DB::table('financial_transactions')->count());
        $this->assertSame(0, DB::table('inventory_transactions')->count());
    }

    public function test_existing_financial_ledger_prevents_catalog_install_after_admin_setup(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole(Role::findByName('admin', 'web'));
        DB::table('initial_admin_setup')->where('id', 1)->update(['completed_at' => now()]);
        \App\Models\FinancialTransaction::create([
            'type' => 'income_adjustment',
            'amount' => 25,
            'description' => 'Existing business ledger record',
            'user_id' => $admin->id,
            'transacted_at' => now(),
        ]);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, Category::count());
        $this->assertSame(0, Product::count());
        $this->assertSame(1, \App\Models\FinancialTransaction::count());
        $this->assertSame(1, User::count());
    }

    public function test_optional_staff_seeder_creates_random_password_staff_only_after_admin_setup(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole(Role::findByName('admin', 'web'));
        DB::table('initial_admin_setup')->where('id', 1)->update(['completed_at' => now()]);

        $this->seed(UserSeeder::class);
        $this->assertSame(4, User::count());
        $this->assertSame(1, User::role('admin')->count());
        $this->assertSame(1, User::role('cashier')->count());
        $this->assertSame(1, User::role('kitchen')->count());
        $this->assertSame(1, User::role('auditor')->count());

        $cashier = User::where('email', 'cashier@demo.invalid')->firstOrFail();
        $hashedPassword = $cashier->getRawOriginal('password');
        $this->assertNotSame('password', $hashedPassword);
        $this->assertNotEmpty($cashier->email_verified_at);

        $this->seed(UserSeeder::class);
        $this->assertSame(4, User::count());
        $this->assertSame($hashedPassword, $cashier->fresh()->getRawOriginal('password'));
    }

    public function test_optional_staff_seeder_refuses_to_run_before_admin_setup(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->expectException(RuntimeException::class);
        $this->seed(UserSeeder::class);
    }
}
