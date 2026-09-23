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
        Schema::create('archived_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('original_user_id');
            $table->unsignedBigInteger('plan_id');
            $table->unsignedBigInteger('avatar_id')->default(5);
            $table->string('full_name');
            $table->string('email');
            $table->string('avatar');
            $table->string('company')->nullable();
            $table->string('organization_number')->nullable();
            $table->string('phone')->nullable();
            $table->string('landline')->nullable();
            $table->string('link')->nullable();
            $table->string('bank')->nullable();
            $table->string('iban')->nullable();
            $table->string('account_number')->nullable();
            $table->string('iban_number')->nullable();
            $table->string('bin')->nullable();
            $table->string('plus_spin')->nullable();
            $table->string('swish')->nullable();
            $table->string('vat')->nullable();
            $table->string('logo')->nullable();
            $table->string('img_signature')->nullable();
            $table->string('address')->nullable();
            $table->string('street')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('personal_phone')->nullable();
            $table->string('personal_landline')->nullable();
            $table->string('personal_address')->nullable();
            $table->string('payout_number')->nullable();
            $table->timestamp('account_created_at');
            $table->timestamp('deleted_at');
            $table->timestamp('retention_until')->nullable();
            $table->timestamps();

            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archived_contacts');
    }
};
