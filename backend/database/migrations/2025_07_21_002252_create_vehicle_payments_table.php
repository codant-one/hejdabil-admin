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
        Schema::create('vehicle_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("vehicle_id");
            $table->unsignedBigInteger('payment_type_id')->nullable();
            $table->tinyInteger('is_loan')->default(0)->comment("Loan (0: No, 1: Yes)");
            $table->decimal("loan_amount", 10, 2)->nullable()->comment("Loan amount");
            $table->string("lessor")->nullable(); 
            $table->decimal("remaining_amount", 10, 2)->nullable()->comment("Remaining amount");
            $table->tinyInteger('settled_by')->default(0)->comment("Settled by (0: Car dealership, 1: Client)");
            $table->string("bank")->nullable(); 
            $table->string("account")->nullable(); 
            $table->string("description")->nullable(); 
            $table->string("payment_type")->nullable(); 
            $table->timestamps();

            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('cascade');
            $table->foreign('payment_type_id')->references('id')->on('payment_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_payments');
    }
};
