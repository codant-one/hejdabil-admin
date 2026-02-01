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
        Schema::table('agreements', function (Blueprint $table) {
             $table->decimal("iva_purchase_amount", 10, 2)->nullable()->after('iva_sale_exclusive')->comment("IVA amount");
            $table->decimal("iva_purchase_exclusive", 10, 2)->nullable()->after('iva_purchase_amount')->comment("Purchase without iva");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('iva_purchase_amount');
            $table->dropColumn('iva_purchase_exclusive');
        });
    }
};
