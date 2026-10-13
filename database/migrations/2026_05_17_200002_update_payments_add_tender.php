<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('payment_tender_id')->nullable()->after('method')->constrained('payment_tenders')->nullOnDelete();
            $table->string('method')->nullable()->change();
        });
    }
    public function down(): void {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_tender_id');
            $table->string('method')->nullable(false)->change();
        });
    }
};
