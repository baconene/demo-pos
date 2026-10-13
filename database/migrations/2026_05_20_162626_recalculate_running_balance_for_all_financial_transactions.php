<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $balance = 0.0;

        DB::table('financial_transactions')
            ->orderBy('transacted_at')
            ->orderBy('id')
            ->get(['id', 'type', 'amount'])
            ->each(function ($tx) use (&$balance) {
                $signed = match ($tx->type) {
                    'payment', 'income_adjustment' => (float) $tx->amount,
                    'expense', 'order'             => -(float) $tx->amount,
                    default                        => 0.0,
                };
                $balance = round($balance + $signed, 2);
                DB::table('financial_transactions')
                    ->where('id', $tx->id)
                    ->update(['running_balance' => $balance]);
            });
    }

    public function down(): void
    {
        // Non-reversible data fix
    }
};
