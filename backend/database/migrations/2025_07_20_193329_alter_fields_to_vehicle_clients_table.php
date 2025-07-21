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
        Schema::table('vehicle_clients', function (Blueprint $table) {
            $table->tinyInteger('type')->default(1)->after('client_id')->comment("TYPE (1: SALE, 2: PURCHASE)");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_clients', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
