<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('royalty_rules', function (Blueprint $table) {
            $table->id();
            $table->enum('scope', ['product', 'category']);
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('recipient_name');                              // dynamic recipient
            $table->foreignId('shareholder_id')->nullable()                // optional link to a member
                ->constrained('shareholders')->nullOnDelete();
            $table->decimal('royalty_percentage', 5, 2);
            $table->date('effective_date');
            $table->date('expiration_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['scope', 'is_active']);
            $table->index(['product_id', 'effective_date']);
            $table->index(['category_id', 'effective_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('royalty_rules');
    }
};
