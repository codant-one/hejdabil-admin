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
        Schema::create('agreement_clients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("agreement_id")->nullable();
            $table->unsignedBigInteger("client_type_id")->nullable();
            $table->unsignedBigInteger("identification_id")->nullable();
            $table->unsignedBigInteger("client_id")->nullable();
            $table->string('fullname')->nullable();
            $table->string('email')->nullable();
            $table->string('organization_number')->nullable();
            $table->longText('address')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('phone')->nullable();
            $table->string('reference')->nullable();
            $table->string('street')->nullable();
            $table->timestamps();

            $table->foreign('agreement_id')->references('id')->on('agreements')->onDelete('cascade');
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
        Schema::dropIfExists('agreement_clients');
    }
};
