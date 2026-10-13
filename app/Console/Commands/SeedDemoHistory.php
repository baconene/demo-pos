<?php

namespace App\Console\Commands;

use App\Services\DemoHistoryGenerator;
use Illuminate\Console\Command;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/** Deliberately separate from db:seed, because these are cash + inventory transactions. */
class SeedDemoHistory extends Command
{
    protected $signature = 'demo:seed-history
        {--months= : Calendar months, including the current month to yesterday (default 4)}
        {--orders-per-day= : Base completed/pending order count per day (default 3)}
        {--seed= : Fixed numeric variation seed (default 20261013)}
        {--dry-run : Show plan without writing anything}
        {--confirm-demo : Acknowledge this is disposable fictional demo data}';

    protected $description = 'Build fictional order, payment, inventory and expense history through POS services (demo only)';

    public function handle(DemoHistoryGenerator $generator): int
    {
        try {
            $months = $this->wholeNumber('months', 1, 12, (int) config('demo_data.default_months', 4));
            $orders = $this->wholeNumber('orders-per-day', 1, 20, (int) config('demo_data.default_orders_per_day', 3));
            $seed = $this->wholeNumber('seed', 0, 999999999, (int) config('demo_data.default_seed', 20261013));

            $plan = $generator->plan($months, $orders, $seed);
            $this->table(['Plan', 'Value'], [
                ['Start', $plan['start']], ['End (yesterday)', $plan['end']],
                ['Calendar months', $months], ['Orders per day', $orders],
                ['Variation seed', $seed], ['Estimated orders', $plan['orders']],
            ]);

            if ($this->option('dry-run')) {
                $this->warn('DRY RUN ONLY: no orders, payments, inventory or expenses were changed.');

                return self::SUCCESS;
            }

            if (! $this->option('confirm-demo') || config('demo_data.allow_history_seed') !== true) {
                $this->error('Denied: set DEMO_POS_ALLOW_HISTORY_SEED=true in this demo site AND provide --confirm-demo.');

                return self::FAILURE;
            }

            $stats = $generator->generate($months, $orders, $seed);
            $this->info(sprintf(
                'Demo history created: %d orders, %d payments, %d sample overhead expenses, %d raw stock purchases, %d kitchen prep batches.',
                $stats['orders'], $stats['payments'], $stats['overhead_expenses'], $stats['purchases'], $stats['productions'],
            ));
            $this->warn('FICTIONAL DATA. Do not use this ledger as actual customer sales or accounting history.');

            return self::SUCCESS;
        } catch (InvalidArgumentException | RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        } catch (Throwable $e) {
            // Laravel/Forge already captures the exception and will roll back the generator's database transaction.
            $this->error('Demo history could not be created; no partial history should remain: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function wholeNumber(string $option, int $minimum, int $maximum, int $fallback): int
    {
        $text = $this->option($option);
        if ($text === null) {
            return $fallback;
        }
        if (! is_string($text) || ! ctype_digit($text)) {
            throw new InvalidArgumentException("--{$option} must be a whole number.");
        }
        $number = (int) $text;
        if ($number < $minimum || $number > $maximum) {
            throw new InvalidArgumentException("--{$option} must be between {$minimum} and {$maximum}.");
        }

        return $number;
    }
}
