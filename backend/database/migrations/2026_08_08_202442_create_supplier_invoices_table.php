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
        Schema::create('supplier_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('state_id')->default(4);
            $table->unsignedBigInteger('credit_id')->nullable();
            $table->unsignedBigInteger('invoice_id');
            $table->tinyInteger('is_sent')->default(0);
            $table->tinyInteger('is_credit')->default(0);
            $table->longText('detail');
            $table->date('invoice_date');
            $table->date('due_date');
            $table->string('payment_terms');
            $table->longText('terms_and_conditions');
            $table->tinyInteger('rabatt')->default(0);
            $table->integer('discount')->default(0);
            $table->double('amount_discount', 10, 2);
            $table->double('amount_tax', 10, 2)->nullable();
            $table->double('subtotal', 10, 2);
            $table->integer('tax');
            $table->double('total', 10, 2);
            $table->string('file')->nullable();
            $table->string('reminder')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            $table->foreign('state_id')->references('id')->on('states')->onDelete('cascade');
            $table->foreign('credit_id')->references('id')->on('invoices')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_invoices');
    }
};
