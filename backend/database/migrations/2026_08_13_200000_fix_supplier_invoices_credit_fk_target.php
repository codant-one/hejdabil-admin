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
        Schema::table('supplier_invoices', function (Blueprint $table) {
            $table->dropForeign(['credit_id']);
            $table->foreign('credit_id')
                ->references('id')
                ->on('supplier_invoices')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_invoices', function (Blueprint $table) {
            $table->dropForeign(['credit_id']);
            $table->foreign('credit_id')
                ->references('id')
                ->on('invoices')
                ->onDelete('cascade');
        });
    }
};
