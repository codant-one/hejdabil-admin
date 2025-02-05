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
        Schema::create('billings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('state_id')->default(4);
            $table->unsignedBigInteger('invoice_id');
            $table->longText('detail');
            $table->date('invoice_date');
            $table->date('due_date');
            $table->string('payment_terms');
            $table->string('reference')->nullable();
            $table->double('subtotal', 10, 2);
            $table->integer('tax');
            $table->double('total', 10, 2);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            $table->foreign('state_id')->references('id')->on('states')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billings');
    }
};
