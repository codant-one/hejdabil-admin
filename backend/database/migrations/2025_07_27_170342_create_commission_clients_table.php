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
            $table->unsignedBigInteger('commission_id');
            $table->unsignedBigInteger('client_type_id');
            $table->unsignedBigInteger("identification_id")->nullable();
            $table->string('fullname')->nullable();
            $table->string('email')->nullable();
            $table->string('organization_number')->nullable();
            $table->longText('address')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('phone')->nullable();
            $table->string('street')->nullable();
            $table->timestamps();

            $table->foreign('commission_id')->references('id')->on('commissions')->onDelete('cascade');
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
