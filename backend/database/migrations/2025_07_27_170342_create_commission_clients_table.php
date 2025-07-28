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
        Schema::create('commission_clients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('commision_id');
            $table->unsignedBigInteger('client_type_id');
            $table->unsignedBigInteger("identification_id")->nullable();
            $table->string('fullname');
            $table->string('email');
            $table->string('organization_number')->nullable();
            $table->longText('address');
            $table->string('street');
            $table->string('postal_code');
            $table->string('phone');
            $table->timestamps();
            $table->foreign('commision_id')->references('id')->on('commissions')->onDelete('cascade');
            $table->foreign('client_type_id')->references('id')->on('client_types')->onDelete('cascade');
            $table->foreign('identification_id')->references('id')->on('identifications')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_clients');
    }
};
