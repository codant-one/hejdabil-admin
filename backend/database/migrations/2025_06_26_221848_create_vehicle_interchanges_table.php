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
        Schema::create('vehicle_interchanges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("model_id")->nullable()->comment("Model a car, example Corolla");
            $table->year("year")->nullable()->comment("Year of car (YYYY), example 2025");
            $table->string("meter_setting")->nullable()->comment("Meter Setting");
            $table->unsignedBigInteger("car_body_id")->nullable();
            $table->string('color')->nullable()->comment("Car color");
            $table->string("reg_num")->nullable()->comment("Register number");
            $table->string("chassis_num")->nullable()->comment("Register number");
            $table->date("first_insc")->nullable()->comment("First inscription (YYYY-MM-DD), example 2025-01-01");
            $table->decimal("tradein_price", 10, 2)->nullable()->comment("Tradein price");
            $table->tinyInteger('residual_debt')->nullable()->default(0)->comment("Has a Residual Debt? (0: No, 1: Yes)");
            $table->decimal("residual_debt_value", 10, 2)->nullable()->comment("Residual Debt amount");
            $table->unsignedBigInteger("iva_id")->nullable()->comment("IVA (0: VMB, 1: TINA)");
            $table->string("residual_debt_paid_to")->nullable()->comment("Who receive the paid to debt");
            $table->tinyInteger('redemption_quote')->nullable()->default(0)->comment("Has a Redemption Quote? (0: No, 1: Yes)");
            $table->unsignedBigInteger('vehicle_id')->nullable();

            $table->timestamps();

            $table->foreign('model_id')->references('id')->on('models')->onDelete('cascade');
            $table->foreign('car_body_id')->references('id')->on('car_bodies')->onDelete('cascade');
            $table->foreign('iva_id')->references('id')->on('ivas')->onDelete('cascade');
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_interchanges');
    }
};
