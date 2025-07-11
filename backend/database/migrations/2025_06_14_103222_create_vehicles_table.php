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
            $table->unsignedBigInteger("user_id");
            $table->unsignedBigInteger("model_id")->nullable()->comment("Model a car, example Corolla");
            $table->unsignedBigInteger("car_body_id")->nullable();
            $table->unsignedBigInteger('gearbox_id')->nullable();
            $table->unsignedBigInteger("iva_purchase_id")->nullable()->comment("IVA (0: VMB, 1: TINA)");
            $table->unsignedBigInteger("iva_sale_id")->nullable()->comment("IVA (0: VMB, 1: TINA)");
            $table->unsignedBigInteger("currency_purchase_id")->nullable();
            $table->unsignedBigInteger("currency_sale_id")->nullable();
            $table->unsignedBigInteger('fuel_id')->nullable();
            $table->unsignedBigInteger('state_id')->default(10);
            
            $table->string("reg_num")->comment("Register number");
            $table->string("mileage")->nullable()->comment("Kilometers traveled");
            $table->string("generation")->nullable()->comment("Generation car");
            $table->year("year")->nullable()->comment("Year of car (YYYY), example 2025");
            $table->date("control_inspection")->nullable()->comment("Control inspection is valid until");
            $table->string('color')->nullable()->comment("Car color");
            $table->decimal("purchase_price", 10, 2)->nullable()->comment("Purchase price");
            $table->date("purchase_date")->nullable()->comment("Purchase date");
            $table->decimal("sale_price", 10, 2)->nullable()->comment("Bellow sale price");
            $table->date("sale_date")->nullable()->comment("Sale date");
            $table->integer('number_keys')->nullable()->comment("Number of keys");
            $table->tinyInteger('service_book')->default(0)->comment("There is a Book Service? (0: No, 1: Yes)");
            $table->tinyInteger('summer_tire')->default(0)->comment("There are Summer Tire? (0: No, 1: Yes)");
            $table->tinyInteger('winter_tire')->default(0)->comment("There are Winter Tire? (0: No, 1: Yes)");
            $table->string("last_service")->nullable()->comment("Miles or Date (DD-MM-YYYY) of last service");
            $table->tinyInteger('dist_belt')->default(0)->comment("Replace distribution belt? (0: No, 1: Yes, 2: No Available)");
            $table->string("last_dist_belt")->nullable()->comment("Miles or Date (DD-MM-YYYY) of last service distribution belt replace");
            $table->string("comments")->nullable();
            $table->string("sale_comments")->nullable();
            $table->decimal("iva_sale_amount", 10, 2)->nullable()->comment("IVA amount");
            $table->decimal("iva_sale_exclusive", 10, 2)->nullable()->comment("Sale without iva");
            $table->decimal("discount", 10, 2)->nullable()->comment("Discount");
            $table->decimal("registration_fee", 10, 2)->nullable()->comment("Registration fee");
            $table->decimal("total_sale", 10, 2)->nullable()->comment("Total sale");
            $table->string("file")->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('model_id')->references('id')->on('models')->onDelete('cascade');
            $table->foreign('car_body_id')->references('id')->on('car_bodies')->onDelete('cascade');
            $table->foreign('gearbox_id')->references('id')->on('gearboxes')->onDelete('cascade');
            $table->foreign('iva_purchase_id')->references('id')->on('ivas')->onDelete('cascade');
            $table->foreign('iva_sale_id')->references('id')->on('ivas')->onDelete('cascade');
            $table->foreign('currency_purchase_id')->references('id')->on('currencies')->onDelete('cascade');
            $table->foreign('currency_sale_id')->references('id')->on('currencies')->onDelete('cascade');
            $table->foreign('fuel_id')->references('id')->on('fuels')->onDelete('cascade');
            $table->foreign('state_id')->references('id')->on('states')->onDelete('cascade');
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
