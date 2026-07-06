<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFeaturePlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('feature_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('feature_id')->nullable();
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->timestamps();

            $table->foreign('feature_id')->references('id')->on('features')->nullOnDelete();
            $table->foreign('plan_id')->references('id')->on('plans')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('feature_plans');
    }
}
