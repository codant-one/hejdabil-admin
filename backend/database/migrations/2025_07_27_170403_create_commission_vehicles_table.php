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
        Schema::create('commission_vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('commision_id');
            $table->unsignedBigInteger('model_id')->comment("Model a car, example Corolla");
            $table->unsignedBigInteger('fuel_id')->nullable();
            $table->unsignedBigInteger('gearbox_id')->nullable();
            $table->year('year')->nullable()->comment("Year of car (YYYY), example 2025");
            $table->string('color');
            $table->string('Chassinummer');
            $table->string('mileage')->nullable()->comment("Kilometers traveled");
            $table->integer('number_keys')->nullable()->comment("Number of keys");
            $table->tinyInteger('service_book')->default(0)->comment("There is a Book Service? (0: No, 1: Yes)");
            $table->tinyInteger('summer_tire')->default(0)->comment("There are Summer Tire? (0: No, 1: Yes)");
            $table->tinyInteger('winter_tire')->default(0)->comment("There are Winter Tire? (0: No, 1: Yes)");
            $table->longtext('comment')->nullable();
            $table->timestamps();
            $table->foreign('commision_id')->references('id')->on('commissions')->onDelete('cascade');
            $table->foreign('model_id')->references('id')->on('models')->onDelete('cascade');
            $table->foreign('fuel_id')->references('id')->on('fuels')->onDelete('cascade');
            $table->foreign('gearbox_id')->references('id')->on('gearboxes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_vehicles');
    }
};
