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
            $table->unsignedBigInteger("car_body_id")->nullable()->after('model_id');
            $table->unsignedBigInteger("currency_id")->nullable()->after('car_body_id');
             
            $table->string("generation")->nullable()->comment("Generation car")->after('mileage');
            $table->date("control_inspection")->nullable()->comment("Control inspection is valid until")->after('generation');
            $table->date("date")->nullable()->comment("Date")->after('control_inspection');
            $table->string("last_service")->nullable()->comment("Miles or Date (DD-MM-YYYY) of last service")->after('date');
            $table->date('last_service_date')->nullable()->after('last_service');
            $table->tinyInteger('dist_belt')->default(0)->comment("Replace distribution belt? (0: No, 1: Yes, 2: No Available)")->after('last_service_date');
            $table->string("last_dist_belt")->nullable()->comment("Miles or Date (DD-MM-YYYY) of last service distribution belt replace")->after('dist_belt');
            $table->date('last_dist_belt_date')->nullable()->after('last_dist_belt');

            $table->foreign('car_body_id')->references('id')->on('car_bodies')->onDelete('cascade');
            $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commission_vehicles', function (Blueprint $table) {
            $table->dropColumn('car_body_id');
            $table->dropColumn('currency_id');
            $table->dropColumn('generation');
            $table->dropColumn('control_inspection');
            $table->dropColumn('date');
            $table->dropColumn('last_service');
            $table->dropColumn('last_service_date');
            $table->dropColumn('dist_belt');
            $table->dropColumn('last_dist_belt');
            $table->dropColumn('last_dist_belt_date');
        });
    }
};
