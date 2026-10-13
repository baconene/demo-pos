<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->enum('trigger', ['new_order', 'status_change']);
            $table->string('trigger_status')->nullable(); // which status triggered it (for status_change)
            $table->enum('status', ['pending', 'sent', 'printed', 'failed'])->default('pending');
            $table->json('receipt_data');                // snapshot of the order at time of job creation
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('failed_reason')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('printed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_jobs');
    }
};
