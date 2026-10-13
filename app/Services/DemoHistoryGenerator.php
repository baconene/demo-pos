<?php

namespace App\Services;

use App\Enums\InventoryTransactionType;
use App\Models\FinancialTransaction;
use App\Models\Ingredient;
use App\Models\InventoryCostEntry;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentTender;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoCatalogSamples;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Explicitly opted-in FICTIONAL four-calendar-month cash and POS simulator.
 * The POS OrderService, PaymentService, FoodProductionService, InventoryService and
 * inventory cost ledger do the actual work. No hand-built payment/order totals.
 *
 * A single transaction and strict virgin-ledger preflight mean reruns/partial
 * failures cannot silently duplicate or corrupt real business transactions.
 */
class DemoHistoryGenerator
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly PaymentService $payments,
        private readonly InventoryService $inventory,
        private readonly FoodProductionService $kitchen,
    ) {}

    /** @return array{start:string,end:string,orders:int} */
    public function plan(int $months, int $ordersPerDay, int $seed): array
    {
        if ($months < 1 || $months > 12 || $ordersPerDay < 1 || $ordersPerDay > 20 || $seed < 0) {
            throw new RuntimeException('Requested demo-history volume is outside the allowed limits.');
        }
        $today = CarbonImmutable::now(config('app.timezone', 'Asia/Manila'))->startOfDay();
        $start = $today->startOfMonth()->subMonthsNoOverflow($months - 1);
        $end = $today->subDay(); // Historical activity cannot be backfilled into the future.
        $count = 0;
        for ($date = $start; $date->lessThanOrEqualTo($end); $date = $date->addDay()) {
            $count += $this->volumeForDay($date, $ordersPerDay, $seed);
        }

        return ['start' => $start->toDateString(), 'end' => $end->toDateString(), 'orders' => $count];
    }

    /** @return array{orders:int,payments:int,overhead_expenses:int,purchases:int,productions:int} */
    public function generate(int $months, int $ordersPerDay, int $seed): array
    {
        if (config('demo_data.allow_history_seed') !== true) {
            throw new RuntimeException('DEMO_POS_ALLOW_HISTORY_SEED must be explicitly enabled.');
        }
        $plan = $this->plan($months, $ordersPerDay, $seed);
        $formerUser = Auth::user();
        $formerTime = Carbon::getTestNow();

        try {
            return DB::transaction(function () use ($plan, $ordersPerDay, $seed): array {
                // Serialize with initial setup (and other attempts at generating demo history).
                $setup = DB::table('initial_admin_setup')->where('id', 1)->lockForUpdate()->first();
                if (! $setup || $setup->completed_at === null) {
                    throw new RuntimeException('Create the initial administrator at /setup/admin before generating history.');
                }
                $admin = User::role('admin')->orderBy('id')->first();
                if (! $admin) {
                    throw new RuntimeException('No administrator available to own fictional orders and payments.');
                }
                $this->assertVirginLedger();

                $expectedSkus = array_column(DemoCatalogSamples::PRODUCTS, 'sku');
                $products = Product::with('recipes.ingredient')->whereIn('sku', $expectedSkus)->get()->keyBy('sku');
                if ($products->count() !== count($expectedSkus) || Product::whereNotIn('sku', $expectedSkus)->exists()) {
                    throw new RuntimeException('Requires the unmodified DEMO POS training product catalog. No customer catalog is allowed.');
                }
                $tenders = PaymentTender::where('is_active', true)->orderBy('id')->get();
                if ($tenders->isEmpty()) {
                    throw new RuntimeException('Run db:seed to create payment tenders before demo history.');
                }

                Auth::onceUsingId($admin->id); // Follow the same auth user_id paths as normal POS orders.
                $metrics = ['orders' => 0, 'payments' => 0, 'overhead_expenses' => 0, 'purchases' => 0, 'productions' => 0];
                $dateIndex = 0;
                $catalog = array_values($expectedSkus);
                $start = CarbonImmutable::parse($plan['start']);
                $end = CarbonImmutable::parse($plan['end']);
                for ($date = $start; $date->lessThanOrEqualTo($end); $date = $date->addDay(), $dateIndex++) {
                    // Never overwrite server time globally beyond this dedicated CLI process.
                    Carbon::setTestNow(Carbon::parse($date->toDateString().' 08:15:00'));
                    if ($date->day === 1) {
                        $this->addMonthlyExpenses($tenders->first()->id, $dateIndex, $metrics);
                    }
                    $count = $this->volumeForDay($date, $ordersPerDay, $seed);
                    for ($i = 0; $i < $count; $i++) {
                        $hour = 11 + (($i * 3 + $dateIndex) % 10);
                        $minute = ($dateIndex * 11 + $i * 17) % 60;
                        Carbon::setTestNow(Carbon::parse(sprintf('%s %02d:%02d:00', $date->toDateString(), $hour, $minute)));
                        $selector = ($dateIndex * 7 + $i * 3 + $seed) % count($catalog);
                        $product = $products->get($catalog[$selector]);
                        $qty = (($dateIndex + $i + $seed) % 11 === 0) ? 2 : 1;
                        $this->prepareForOrder($product, $qty, $metrics);
                        $type = match (($i + $dateIndex + $seed) % 10) {
                            0 => 'delivery', 1, 2, 3 => 'takeout', default => 'dine_in',
                        };
                        $customer = 'DEMO Customer '.($dateIndex * 100 + $i + 1);
                        $data = [
                            'order_type' => $type,
                            'table_number' => $type === 'dine_in' ? 'T'.(1 + (($i + $dateIndex) % 12)) : null,
                            'customer_name' => $type === 'dine_in' ? null : $customer,
                            'customer_contact' => $type === 'delivery' ? '09000000000' : null,
                            'customer_address' => $type === 'delivery' ? 'Fictional delivery address - training only' : null,
                            'items' => [['product_id' => $product->id, 'quantity' => $qty]],
                            'notes' => '[DEMO-HISTORY] Synthetic POS order; not a real sale.',
                        ];
                        // Uses the standard StoreOrderRequest field matrix, not a relaxed seeder-specific schema.
                        \Illuminate\Support\Facades\Validator::make($data, (new \App\Http\Requests\StoreOrderRequest())->rules())->validate();
                        $order = $this->orders->createOrder($data);
                        $metrics['orders']++;

                        // A few intentionally pending orders demonstrate paid/unpaid reporting.
                        if (($dateIndex + $i + $seed) % 13 !== 0) {
                            $tender = $tenders[($dateIndex + $i + $seed) % $tenders->count()];
                            $this->payments->processPayment($order, [
                                'amount' => $order->fresh()->total_amount,
                                'payment_tender_id' => $tender->id,
                                'notes' => '[DEMO-HISTORY] Synthetic training payment.',
                            ]);
                            $metrics['payments']++;

                            // Do not call updateOrderStatus(COMPLETED): it can trigger external
                            // receipt printing, which must never occur for synthetic history.
                            $order->update(['status' => 'completed', 'completed_at' => now()]);
                            $order->items()->update(['status' => 'completed']);
                            if ($order->queue_number_id) {
                                $order->queueNumber()->update(['status' => 'served', 'completed_at' => now()]);
                            }
                        }
                    }
                }

                return $metrics;
            }, attempts: 1);
        } finally {
            Carbon::setTestNow($formerTime);
            if ($formerUser) {
                Auth::guard('web')->setUser($formerUser);
            } else {
                Auth::guard('web')->forgetUser();
            }
        }
    }

    private function assertVirginLedger(): void
    {
        if (Order::exists() || Payment::exists() || FinancialTransaction::exists()
            || InventoryTransaction::exists() || InventoryCostEntry::exists()) {
            throw new RuntimeException('History seeding refused: this database already contains sales, financial or inventory movements. Nothing was changed.');
        }
    }

    /** @param array<string,int> $metrics */
    private function prepareForOrder(Product $product, int $quantity, array &$metrics): void
    {
        $product->load('recipes.ingredient');
        foreach ($product->recipes as $line) {
            $item = $line->ingredient;
            if (! $item || ! $item->track_inventory) {
                continue;
            }
            $required = round((float) $line->quantity * $quantity, 3);
            if ($item->isFood()) {
                if ((float) $item->fresh()->current_quantity < $required + 4) {
                    $batch = max(15, (int) ceil($required + 12 - (float) $item->fresh()->current_quantity));
                    foreach ($item->components()->with('ingredient')->get() as $component) {
                        $this->purchaseIfNeeded($component->ingredient, (float) $component->quantity * $batch + 1, $metrics);
                    }
                    $this->kitchen->produce($item, $batch, notes: '[DEMO-HISTORY] Training kitchen production');
                    $metrics['productions']++;
                }
            } else {
                $this->purchaseIfNeeded($item, $required + 2, $metrics);
            }
        }
    }

    /** @param array<string,int> $metrics */
    private function purchaseIfNeeded(Ingredient $item, float $minimum, array &$metrics): void
    {
        $item->refresh();
        if (! $item->track_inventory || (float) $item->current_quantity >= $minimum) {
            return;
        }
        $quantity = round(max($minimum - (float) $item->current_quantity + 5, (float) $item->min_quantity * 3, 5), 3);
        $this->inventory->recordTransaction(
            $item, $quantity, InventoryTransactionType::STOCK_IN,
            '[DEMO-HISTORY] Raw supply replenishment', 'Fictional raw ingredient purchase',
            recordCost: true, unitCost: (float) $item->cost_per_unit,
        );
        FinancialTransaction::create([
            'type' => 'expense',
            'amount' => max(0.01, round($quantity * (float) $item->cost_per_unit, 2)),
            'description' => '[DEMO-HISTORY] Inventory purchase: '.$item->name,
            'payment_tender_id' => PaymentTender::where('name', 'Cash')->value('id'),
            'notes' => 'Fictional sample stock purchase; inventory value tracked by InventoryCostService. Not additional COGS.',
            'user_id' => Auth::id(),
            'transacted_at' => now(),
        ]);
        $metrics['purchases']++;
    }

    /** @param array<string,int> $metrics */
    private function addMonthlyExpenses(int $tenderId, int $monthOffset, array &$metrics): void
    {
        foreach (config('demo_data.monthly_expenses', []) as $description => $baseAmount) {
            FinancialTransaction::create([
                'type' => 'expense',
                'amount' => round((float) $baseAmount * (1 + ($monthOffset % 4) * 0.02), 2),
                'description' => '[DEMO-HISTORY] '.$description,
                'payment_tender_id' => $tenderId,
                'user_id' => Auth::id(),
                'notes' => 'Fictional operating expense for report demonstrations.',
                'transacted_at' => now(),
            ]);
            $metrics['overhead_expenses']++;
        }
    }

    private function volumeForDay(CarbonImmutable $date, int $base, int $seed): int
    {
        return $base + ($date->isWeekend() ? 2 : 0) + ((($date->day + $seed) % 17 === 0) ? 1 : 0);
    }
}
