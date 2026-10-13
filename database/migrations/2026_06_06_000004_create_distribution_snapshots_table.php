<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distribution_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('period_start');
            $table->date('period_end');
            $table->enum('distribution_basis', ['sales', 'profit']);

            // Computed amounts (historical record only — source data stays in existing tables)
            $table->decimal('gross_amount', 14, 2)->default(0);
            $table->decimal('refunds_amount', 14, 2)->default(0);
            $table->decimal('cogs_amount', 14, 2)->default(0);
            $table->decimal('expenses_amount', 14, 2)->default(0);
            $table->decimal('royalty_amount', 14, 2)->default(0);
            $table->decimal('distributable_amount', 14, 2)->default(0);
            $table->decimal('members_amount', 14, 2)->default(0);
            $table->decimal('company_amount', 14, 2)->default(0);

            $table->json('filters_applied')->nullable();   // category/product/shareholder filters used
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['period_start', 'period_end']);
        });

        Schema::create('distribution_snapshot_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('snapshot_id')->constrained('distribution_snapshots')->cascadeOnDelete();
            $table->enum('recipient_type', ['shareholder', 'royalty', 'company']);
            $table->foreignId('shareholder_id')->nullable()->constrained('shareholders')->nullOnDelete();
            $table->string('recipient_name');
            $table->decimal('percentage', 6, 2)->default(0);
            $table->decimal('amount', 14, 2)->default(0);
            $table->timestamps();

            $table->index('snapshot_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distribution_snapshot_details');
        Schema::dropIfExists('distribution_snapshots');
    }
};
