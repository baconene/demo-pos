<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['order', 'expense', 'payment']);
            $table->decimal('amount', 12, 2);
            $table->string('description');
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_tender_id')->nullable()->constrained('payment_tenders')->nullOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->text('notes')->nullable();
            $table->timestamp('transacted_at');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('financial_transactions'); }
};
