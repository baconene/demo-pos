<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('initial_admin_setup', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->timestamp('completed_at')->nullable();
        });

        // A pre-existing row ensures MySQL's SELECT ... FOR UPDATE can protect
        // concurrent first-admin requests before any user is created.
        DB::table('initial_admin_setup')->insert([
            'id' => 1,
            'completed_at' => null,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('initial_admin_setup');
    }
};
