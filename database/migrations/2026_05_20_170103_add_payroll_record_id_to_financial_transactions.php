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
            $table->foreignId('payroll_record_id')
                ->nullable()
                ->after('payment_tender_id')
                ->constrained('payroll_records')
                ->nullOnDelete();
        });

        // Re-type existing payroll-linked expense records to 'payroll'
        DB::table('financial_transactions')
            ->whereNotNull('payroll_record_id')
            ->update(['type' => 'payroll']);
    }

    public function down(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropForeign(['payroll_record_id']);
            $table->dropColumn('payroll_record_id');
        });
    }
};
