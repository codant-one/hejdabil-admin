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
        Schema::create('vehicle_clients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("vehicle_id");
            $table->unsignedBigInteger("client_type_id")->nullable();
            $table->unsignedBigInteger("identification_id")->nullable();
            $table->unsignedBigInteger("client_id")->nullable();
            $table->string('fullname');
            $table->string('email');
            $table->string('organization_number')->nullable();
            $table->longText('address');
            $table->string('postal_code');
            $table->string('phone');
            $table->timestamps();

            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('cascade');
            $table->foreign('client_type_id')->references('id')->on('client_types')->onDelete('cascade');
            $table->foreign('identification_id')->references('id')->on('identifications')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_clients');
    }
};
