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
        Schema::table('billings', function (Blueprint $table) {
            $table->tinyInteger('rabatt')->default(0)->after('reference');
            $table->integer('discount')->default(0)->after('rabatt');
            $table->double('amount_discount', 10, 2)->nullable()->after('discount');
            $table->double('amount_tax', 10, 2)->nullable()->after('amount_discount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billings', function (Blueprint $table) {
            $table->dropColumn('rabatt');
            $table->dropColumn('discount');
            $table->dropColumn('amount_discount');
            $table->dropColumn('amount_tax');
        });
    }
};
