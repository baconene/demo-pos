<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_service_settings', function (Blueprint $table) {
            $table->id();
            $table->string('print_service_url')->default('');
            $table->unsignedTinyInteger('print_paper_width')->default(32); // 32 or 48 chars/line
            $table->string('print_store_name')->default('');
            $table->string('print_store_address')->default('');
            $table->string('print_store_phone')->default('');
            $table->string('print_footer')->default('');
            $table->boolean('print_auto_print')->default(false);
            $table->boolean('print_enabled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_service_settings');
    }
};
