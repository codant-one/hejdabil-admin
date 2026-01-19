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
        Schema::table('vehicle_tasks', function (Blueprint $table) {
            $table->tinyInteger('is_cost')->default(0)->after('vehicle_id')->comment("1. Costo 2. Plan");
            $table->date('start_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_tasks', function (Blueprint $table) {
            $table->dropColumn('is_cost');
            $table->dropColumn('start_date');
        });
    }
};
