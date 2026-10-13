<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('print_service_settings', function (Blueprint $table) {
            $table->string('print_channel')->default('orders')->after('print_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('print_service_settings', function (Blueprint $table) {
            $table->dropColumn('print_channel');
        });
    }
};
