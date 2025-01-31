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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('company')->nullable();
            $table->string('organization_number')->nullable();
            $table->longText('address');
            $table->string('street');
            $table->string('postal_code');
            $table->string('phone');
            $table->string('link')->nullable();
            $table->string('reference')->nullable();
            $table->string('bank');
            $table->string('iban')->nullable();
            $table->string('compensation_number')->nullable();
            $table->string('account_number');
            $table->string('iban_number')->nullable();
            $table->string('bic')->nullable();
            $table->string('bank_transfer')->nullable();
            $table->string('plus_spin')->nullable();
            $table->string('whistle')->nullable();
            $table->string('registration_fee')->nullable();
            $table->string('insurance_company')->nullable();
            $table->string('financial_company')->nullable();
            $table->string('interest')->nullable();
            $table->string('avi_fee')->nullable();
            $table->string('installation_fee')->nullable();
            $table->string('logo')->nullable();
            $table->timestamps();

            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
