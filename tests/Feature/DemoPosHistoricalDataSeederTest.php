<?php

namespace Tests\Feature;

use App\Models\FinancialTransaction;
use App\Models\Ingredient;
use App\Models\InventoryCostEntry;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\DemoHistoryGenerator;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DemoPosHistoricalDataSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('demo_data.allow_history_seed', true);
        Carbon::setTestNow('2026-10-13 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function ready(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole(Role::findByName('admin', 'web'));
        DB::table('initial_admin_setup')->where('id', 1)->update(['completed_at' => now()]);
    }

    public function test_four_months_are_created_through_real_pos_services_and_ledger_matches(): void
    {
        $this->ready();
        $stats = app(DemoHistoryGenerator::class)->generate(4, 1, 20261013);
        $this->assertGreaterThan(80, $stats['orders']);
        $this->assertSame($stats['orders'], Order::count());
        $this->assertSame($stats['payments'], Payment::count());
        $this->assertSame($stats['payments'], FinancialTransaction::where('type', 'payment')->count());
        $this->assertSame($stats['orders'], FinancialTransaction::where('type', 'order')->count());
        $this->assertSame($stats['overhead_expenses'] + $stats['purchases'], FinancialTransaction::where('type', 'expense')->count());
        $this->assertSame(12, $stats['overhead_expenses']);
        $this->assertGreaterThan(0, InventoryTransaction::count());
        $this->assertGreaterThan(0, InventoryCostEntry::where('kind', 'consumption')->count());
        $this->assertEqualsWithDelta((float) Payment::sum('amount'), (float) FinancialTransaction::where('type', 'payment')->sum('amount'), 0.01);
        $this->assertEqualsWithDelta((float) Order::sum('total_amount'), (float) FinancialTransaction::where('type', 'order')->sum('amount'), 0.01);

        $dates = Order::orderBy('created_at')->pluck('created_at');
        $this->assertStringStartsWith('2026-07-', (string) $dates->first());
        $this->assertStringStartsWith('2026-10-', (string) $dates->last());
        $this->assertFalse(Order::whereDate('created_at', '>=', '2026-10-13')->exists());
        $this->assertFalse(FinancialTransaction::where('type', 'order')->whereNull('order_id')->exists());
        $this->assertFalse(FinancialTransaction::where('type', 'payment')->whereNull('payment_id')->exists());
        $this->assertSame($stats['orders'], Order::where('notes', 'like', '%[DEMO-HISTORY]%')->count());
        $this->assertSame($stats['payments'], Order::where('payment_status', 'paid')->count());
        $this->assertGreaterThan(0, Order::where('payment_status', 'pending')->count());
        $this->assertGreaterThan(0, Order::where('order_type', 'delivery')->count());
        $this->assertGreaterThan(0, Order::where('order_type', 'dine_in')->count());
        $this->assertGreaterThan(0, Ingredient::food()->where('current_quantity', '>', 0)->count());
        $this->assertSame('2026-10-13 12:00:00', Carbon::getTestNow()->toDateTimeString());
    }

    public function test_command_dry_run_never_modifies_data(): void
    {
        $this->ready();
        $this->artisan('demo:seed-history --months=4 --orders-per-day=2 --dry-run')
            ->assertSuccessful();
        $this->assertSame(0, Order::count());
        $this->assertSame(0, Payment::count());
        $this->assertSame(0, FinancialTransaction::count());
        $this->assertSame(0, InventoryTransaction::count());
    }

    public function test_command_requires_explicit_confirmation_and_server_opt_in(): void
    {
        $this->ready();
        $this->artisan('demo:seed-history --months=1 --orders-per-day=1')
            ->assertFailed();
        config()->set('demo_data.allow_history_seed', false);
        $this->artisan('demo:seed-history --months=1 --orders-per-day=1 --confirm-demo')
            ->assertFailed();
        $this->assertSame(0, Order::count());
    }

    public function test_reruns_and_preexisting_financial_records_are_rejected(): void
    {
        $this->ready();
        app(DemoHistoryGenerator::class)->generate(1, 1, 7);
        $count = Order::count();
        $this->artisan('demo:seed-history --months=1 --orders-per-day=1 --confirm-demo')
            ->assertFailed();
        $this->assertSame($count, Order::count());
    }

    public function test_existing_financial_activity_prevents_any_order_generation(): void
    {
        $this->ready();
        FinancialTransaction::create([
            'type' => 'income_adjustment', 'amount' => 25, 'description' => 'Customer business record',
            'user_id' => User::firstOrFail()->id, 'transacted_at' => now(),
        ]);
        $this->artisan('demo:seed-history --months=1 --orders-per-day=1 --confirm-demo')->assertFailed();
        $this->assertSame(0, Order::count());
        $this->assertSame(1, FinancialTransaction::count());
    }

    public function test_initial_admin_must_be_completed_before_generating_history(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->artisan('demo:seed-history --months=1 --orders-per-day=1 --confirm-demo')
            ->assertFailed();
        $this->assertSame(0, Order::count());
    }
}
