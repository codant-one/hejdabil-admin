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
        Schema::create('archived_contact_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('archived_contact_id');
            $table->unsignedBigInteger('state_id')->nullable();
            $table->unsignedBigInteger('payout_state_id')->nullable();
            $table->string('signature_status')->nullable();
            $table->string('name');
            $table->string('type');
            $table->string('file')->nullable();
            $table->timestamps();

            $table->foreign('archived_contact_id')->references('id')->on('archived_contacts')->onDelete('cascade');
            $table->foreign('state_id')->references('id')->on('states')->onDelete('cascade');
            $table->foreign('payout_state_id')->references('id')->on('payout_states')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archived_contact_files');
    }
};
