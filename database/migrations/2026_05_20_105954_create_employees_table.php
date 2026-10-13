<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('position')->nullable();
            $table->enum('employment_type', ['full_time', 'part_time', 'contractual'])->default('full_time');
            $table->enum('salary_type', ['monthly', 'daily', 'hourly'])->default('monthly');
            $table->decimal('base_rate', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->date('hired_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
