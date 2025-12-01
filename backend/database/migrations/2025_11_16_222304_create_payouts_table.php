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
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('payout_state_id')->default(1);
            $table->string('swish_id')->nullable();
            $table->string('reference')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('payer_alias')->nullable();
            $table->string('payee_alias')->nullable();
            $table->string('payee_ssn')->nullable();
            $table->string('currency', 3)->default('SEK');
            $table->string('payout_type')->default('PAYOUT');
            $table->string('instruction_date')->nullable();
            $table->string('payout_instruction_uuid')->nullable();
            $table->text('message')->nullable();
            $table->string('signing_certificate_serial_number')->nullable();
            $table->string('location_url')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('payout_state_id')->references('id')->on('payout_states')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
