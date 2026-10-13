<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->decimal('running_balance', 14, 2)->default(0)->after('transacted_at');
        });

        // Backfill running balance for existing records ordered chronologically
        $balance = 0.0;
        DB::table('financial_transactions')
            ->orderBy('transacted_at')
            ->orderBy('id')
            ->get(['id', 'type', 'amount'])
            ->each(function ($tx) use (&$balance) {
                if ($tx->type === 'payment' || $tx->type === 'income_adjustment') {
                    $balance += (float) $tx->amount;
                } elseif ($tx->type === 'expense') {
                    $balance -= (float) $tx->amount;
                }
                DB::table('financial_transactions')
                    ->where('id', $tx->id)
                    ->update(['running_balance' => $balance]);
            });
    }

    public function down(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropColumn('running_balance');
        });
    }
};
