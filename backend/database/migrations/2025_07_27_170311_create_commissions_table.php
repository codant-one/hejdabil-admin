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
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('commision_type_id');
            $table->decimal("commission_fee", 10, 2);
            $table->date('start_date');
            $table->date('end_date');
            $table->tinyInteger('outstanding_debt')->default(0)->comment("Does the vehicle have outstanding debt? (0: No, 1: Yes)");
            $table->decimal('remaining_debt',10,2)->default(0.00)->nullable();
            $table->tinyInteger('residual_debt')->default(0)->nullable()->comment("Residual debt is resolved by? (0: Kund, 1: bilhandlare)");
            $table->decimal('paid_bank',10,2)->default(0.00)->nullable();
            $table->decimal('selling_price',10,2)->default(0.00)->nullable();
            $table->unsignedBigInteger('payment_days');
            $table->string('payment_description')->nullable();
            $table->timestamps();
            $table->foreign('commision_type_id')->references('id')->on('commission_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
