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
        Schema::table('suppliers', function (Blueprint $table) {
            $table->tinyInteger('is_yearly')->default(0)->after('position');
            $table->date("start_date")->nullable()->comment("Start date of plan")->after('is_yearly');
            $table->date("end_date")->nullable()->comment("End date of plan")->after('start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('is_yearly');
            $table->dropColumn('start_date');
            $table->dropColumn('end_date');
        });
    }
};
