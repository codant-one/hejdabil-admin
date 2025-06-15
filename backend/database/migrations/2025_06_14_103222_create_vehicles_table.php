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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string("reg_num")->comment("Register number ");
            $table->string("mileage")->nullable()->comment("Kilometers traveled ");
            $table->string("generation")->nullable()->comment("Generation car");
            $table->string("year")->nullable()->comment("Year of car (YYYY), example 2025");
            $table->date("first_insc")->nullable()->comment("First inscription (YYYY-MM-DD), example 2025-01-01");
            // $table->string("body")->nullable()->comment("Body car, example, Sedan");
            // $table->string("gearbox")->nullable()->comment("Gearbox, example Manual (Manuell)");
            $table->decimal("purchase_price", 10, 2)->nullable()->comment("Purchase price");
            $table->date("purchase_date")->nullable()->comment("Purchase date");
            $table->decimal("sale_price", 10, 2)->nullable()->comment("Bellow Sale price");
            $table->date("sale_date")->nullable()->comment("Sale date");
            $table->tinyInteger('number_keys')->nullable()->comment("Number of keys");
            $table->tinyInteger('service_book')->nullable()->comment("There is a Book Service? (0: No, 1: Yes)");
            $table->tinyInteger('summer_tire')->nullable()->comment("There are Summer Tire? (0: No, 1: Yes)");
            $table->tinyInteger('winter_tire')->nullable()->comment("There are Winter Tire? (0: No, 1: Yes)");
            $table->string("last_service")->nullable()->comment("Miles or Date (DD-MM-YYYY) of last service");
            $table->tinyInteger('dist_belt')->nullable()->comment("Replace distribution belt? (0: No, 1: Yes, 2: No Available)");
            $table->string("last_dist_belt")->nullable()->comment("Miles or Date (DD-MM-YYYY) of last service distribution belt replace");
            $table->string("comments")->nullable();

            $table->unsignedBigInteger("model_id")->nullable()->comment("Model a Car, example Corolla");
            $table->unsignedBigInteger("bodys_car_id")->nullable();
            $table->unsignedBigInteger('gearbox_id')->nullable();
            $table->unsignedBigInteger('equipment_id')->nullable();
            $table->unsignedBigInteger("iva_id")->nullable()->comment("IVA (0: VMB, 1: TINA)");
            $table->unsignedBigInteger('state_id')->default(4);
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('model_id')->references('id')->on('models')->onDelete('cascade');
            $table->foreign('bodys_car_id')->references('id')->on('bodys_car')->onDelete('cascade');
            $table->foreign('gearbox_id')->references('id')->on('gearboxes')->onDelete('cascade');
            $table->foreign('equipment_id')->references('id')->on('vehicle_equipments_list')->onDelete('cascade');
            $table->foreign('iva_id')->references('id')->on('ivas')->onDelete('cascade');
            $table->foreign('state_id')->references('id')->on('states')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
