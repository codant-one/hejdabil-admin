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
        Schema::table('commission_vehicles', function (Blueprint $table) {
            $table->string("engine")->nullable()->comment("Engine car")->after('chassis');
            $table->string("car_name")->nullable()->comment("Car name")->after('engine');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commission_vehicles', function (Blueprint $table) {
            $table->dropColumn('engine');
            $table->dropColumn('car_name');
        });
    }
};
