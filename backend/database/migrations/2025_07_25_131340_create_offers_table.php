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
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('model_id')->nullable()->comment("Model a car, example Corolla");
            $table->unsignedBigInteger('offer_id');
            $table->string('reg_num')->comment('Register number');
            $table->string('mileage')->nullable()->comment("Kilometers traveled");
            $table->longtext('comment');
            $table->decimal("price", 10, 2)->nullable()->comment("Agreement price");
            $table->longText("terms_other_conditions")->nullable()->comment("Terms other conditions");
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('model_id')->references('id')->on('models')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
