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
            $table->string('billing_period', 7)->nullable()->after('credit_id'); // formato: '2026-08'
            $table->unique(['supplier_id', 'billing_period'], 'unique_supplier_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_invoices', function (Blueprint $table) {
            $table->dropUnique('unique_supplier_period');
            $table->dropColumn('billing_period');
        });
    }
};
