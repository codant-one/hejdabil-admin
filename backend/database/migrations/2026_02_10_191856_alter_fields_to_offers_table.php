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
        Schema::table('offers', function (Blueprint $table) {
            $table->unsignedBigInteger("car_body_id")->nullable()->after('model_id');
            $table->unsignedBigInteger('gearbox_id')->nullable()->after('car_body_id');
            $table->unsignedBigInteger("currency_id")->nullable()->after('gearbox_id');
            $table->unsignedBigInteger('fuel_id')->nullable()->after('currency_id');
             
            $table->string("generation")->nullable()->comment("Generation car")->after('mileage');
            $table->year("year")->nullable()->comment("Year of car (YYYY), example 2025")->after('generation');
            $table->date("control_inspection")->nullable()->comment("Control inspection is valid until")->after('year');
            $table->string('color')->nullable()->comment("Car color")->after('control_inspection');
            $table->date("date")->nullable()->comment("Date")->after('color');
            $table->integer('number_keys')->nullable()->comment("Number of keys")->after('date');
            $table->tinyInteger('service_book')->default(0)->comment("There is a Book Service? (0: No, 1: Yes)")->after('number_keys');
            $table->tinyInteger('summer_tire')->default(0)->comment("There are Summer Tire? (0: No, 1: Yes)")->after('service_book');
            $table->tinyInteger('winter_tire')->default(0)->comment("There are Winter Tire? (0: No, 1: Yes)")->after('summer_tire');
            $table->string("last_service")->nullable()->comment("Miles or Date (DD-MM-YYYY) of last service")->after('winter_tire');
            $table->date('last_service_date')->nullable()->after('last_service');
            $table->tinyInteger('dist_belt')->default(0)->comment("Replace distribution belt? (0: No, 1: Yes, 2: No Available)")->after('last_service_date');
            $table->string("last_dist_belt")->nullable()->comment("Miles or Date (DD-MM-YYYY) of last service distribution belt replace")->after('dist_belt');
            $table->date('last_dist_belt_date')->nullable()->after('last_dist_belt');
            $table->string("chassis")->nullable()->after('last_dist_belt_date');

            $table->foreign('car_body_id')->references('id')->on('car_bodies')->onDelete('cascade');
            $table->foreign('gearbox_id')->references('id')->on('gearboxes')->onDelete('cascade');
            $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('cascade');
            $table->foreign('fuel_id')->references('id')->on('fuels')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn('car_body_id');
            $table->dropColumn('gearbox_id');
            $table->dropColumn('currency_id');
            $table->dropColumn('fuel_id');
            $table->dropColumn('generation');
            $table->dropColumn('year');
            $table->dropColumn('control_inspection');
            $table->dropColumn('color');
            $table->dropColumn('date');
            $table->dropColumn('number_keys');
            $table->dropColumn('service_book');
            $table->dropColumn('summer_tire');
            $table->dropColumn('winter_tire');
            $table->dropColumn('last_service');
            $table->dropColumn('last_service_date');
            $table->dropColumn('dist_belt');
            $table->dropColumn('last_dist_belt');
            $table->dropColumn('last_dist_belt_date');
            $table->dropColumn('chassis');
        });
    }
};
