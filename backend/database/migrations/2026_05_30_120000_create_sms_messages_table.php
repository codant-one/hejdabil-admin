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
        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('action_type')->nullable();
            $table->string('provider')->default('twilio');
            $table->string('to_number');
            $table->text('message')->nullable();
            $table->string('sms_sender')->nullable();
            $table->string('from_number')->nullable();
            $table->string('messaging_service_sid')->nullable();
            $table->string('provider_message_sid')->nullable();
            $table->string('status');
            $table->date('billing_month')->nullable();
            $table->unsignedInteger('billable_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('provider_error_code')->nullable();
            $table->text('provider_error_message')->nullable();
            $table->timestamps();

            $table->foreign('supplier_id')->references('id')->on('suppliers')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['supplier_id', 'billing_month']);
            $table->index(['user_id', 'billing_month']);
            $table->index('provider_message_sid');
            $table->index(['source_type', 'source_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_messages');
    }
};