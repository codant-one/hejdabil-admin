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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('company');
            $table->string('organization_number');
            $table->longText('address');
            $table->string('street');
            $table->string('postal_code');
            $table->string('phone');
            $table->string('link')->nullable();
            $table->string('bank');
            $table->string('iban')->nullable();
            $table->string('account_number');
            $table->string('iban_number')->nullable();
            $table->string('bic')->nullable();
            $table->string('plus_spin')->nullable();
            $table->string('swish')->nullable();
            $table->string('vat')->nullable();
            $table->string('logo')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
